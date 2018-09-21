// config variables
verbose = false ;
session_directory = "./sessions"; // it has to exist

/* https specific */
var https = require( 'https' ),
fs = require( 'fs' );

var options = {
	key:    fs.readFileSync( '/etc/letsencrypt/live/local.telaaedifex.com/privkey.pem' ),
	cert:   fs.readFileSync( '/etc/letsencrypt/live/local.telaaedifex.com/fullchain.pem' ),
	ca:     fs.readFileSync( '/etc/letsencrypt/live/local.telaaedifex.com/chain.pem' )
};
var app = https.createServer( options );
io = require( 'socket.io' ).listen( app );     //socket.io server listens to https connections
app.listen( 1337, "0.0.0.0" );

// will use the following for file IO
var fs = require( "fs" ) ;

//io = require('socket.io').listen(2015) ;
if( verbose ) { console.log( "> server launched" ); }

var init = false;
collaborations = [] ;
socket_id_to_session_id = [] ;

io.sockets.on('connection', function(socket) {
	
	init = false
	var file = socket.handshake.query.file;
	//var session_id = socket.handshake.query.session_id;
	var session_id = socket.handshake.query.file;
	socket_id_to_session_id[socket.id] = session_id ;
	
	if( verbose ) { console.log( session_id + "connected on socket" + socket.id ) ; }
	
	
	if( !( session_id in collaborations ) ) {
		// not in memory but is is on the filesystem?
		if( file_exists(session_directory + "/" + session_id) ) {
			if( verbose ) { console.log( "session terminated previously, pulling back from filesystem" ); }
			
			var data = read_file( session_directory + "/" + session_id );
			if( data !== false ) {
				
				init = false;
				collaborations[session_id] = {'cached_instructions':JSON.parse(data), 'participants':[]};
			} else {
				
				// something went wrong, we start from scratch
				init = true;
				collaborations[session_id] = {'cached_instructions':[], 'participants':[]};
			}
		} else {
			if( verbose ) { console.log( "   creating new session" ) ; }
			init = true;
			collaborations[session_id] = {'cached_instructions':[], 'participants':[]};
		}
	}
	
	var i = 0;
	
	for( i=0 ; i<collaborations[session_id]['participants'].length ; i++ ) {
		if( socket.id!=collaborations[session_id]['participants'][i] ) {
			io.sockets.connected[collaborations[session_id]['participants'][i]].emit( "recieve_content" );
		}
	}
	
	collaborations[session_id]['participants'].push( socket.id );
	
	for( i=0 ; i<collaborations[session_id]['participants'].length ; i++ ) {
		if( socket.id!=collaborations[session_id]['participants'][i] ) {
			io.sockets.connected[collaborations[session_id]['participants'][i]].emit( "unlock" );
		}
	}
	
	socket.on('change', function( delta ) {
		if( verbose ) { console.log( "change " + socket_id_to_session_id[socket.id] + " " + delta ) ; }
		if( socket_id_to_session_id[socket.id] in collaborations ) {
			collaborations[socket_id_to_session_id[socket.id]]['cached_instructions'].push( ["change", delta, Date.now()] ) ;
			for( var i=0 ; i<collaborations[session_id]['participants'].length ; i++ ) {
				if( socket.id!=collaborations[session_id]['participants'][i] ) {
					io.sockets.connected[collaborations[session_id]['participants'][i]].emit( "change", delta ) ;
				}
			}
		} else {
		if( verbose ) { console.log( "WARNING: could not tie socket_id to any collaboration" ) ; }
		}
	});
	
	
	socket.on('change_selection', function( selections ) {
		if( verbose ) { console.log( "change_selection " + socket_id_to_session_id[socket.id] + " " + selections ) ; }
		if( socket_id_to_session_id[socket.id] in collaborations ) {
			for( var i=0 ; i<collaborations[session_id]['participants'].length ; i++ ) {
				if( socket.id!=collaborations[session_id]['participants'][i] ) {
					io.sockets.connected[collaborations[session_id]['participants'][i]].emit( "change_selection", selections ) ;
				}
			}
		} else {
			if( verbose ) { console.log( "WARNING: could not tie socket_id to any collaboration" ) ; }
		}
	});
	
	
	socket.on('clear_buffer', function() {
		if( verbose ) { console.log( "clear_buffer " + socket_id_to_session_id[socket.id] ) ; }
		if( socket_id_to_session_id[socket.id] in collaborations ) {
			collaborations[socket_id_to_session_id[socket.id]]['cached_instructions'] = [] ;
			for( var i=0 ; i<collaborations[session_id]['participants'].length ; i++ ) {
				if( socket.id!=collaborations[session_id]['participants'][i] ) {
					io.sockets.connected[collaborations[session_id]['participants'][i]].emit( "clear_buffer" ) ;
				}
			}
		} else {
			if( verbose ) { console.log( "WARNING: could not tie socket_id to any collaboration" ) ; }
		}
	});
	
	
	socket.on('dump_buffer', function() {
		if( verbose ) { console.log( "dump_buffer " + socket_id_to_session_id[socket.id] ) ; }
		
		if( socket_id_to_session_id[socket.id] in collaborations ) {
			for( var i=0 ; i<collaborations[socket_id_to_session_id[socket.id]]['cached_instructions'].length ; i++ ) {
				socket.emit( collaborations[socket_id_to_session_id[socket.id]]['cached_instructions'][i][0], collaborations[socket_id_to_session_id[socket.id]]['cached_instructions'][i][1] ) ;
			}
		} else {
			if( verbose ) { console.log( "WARNING: could not tie socket_id to any collaboration" ) ; }
		}
		socket.emit( "buffer_dumped" ) ;
	});
	
	
	socket.on('disconnect', function () {
		console.log( socket_id_to_session_id[socket.id] + " disconnected" ) ;
		var found_and_removed = false ;
		if( socket_id_to_session_id[socket.id] in collaborations ) {
			//var index = collaborations[socket_id_to_session_id[socket.id]].participants.indexOf( socket.id ) ;
			var index = collaborations[socket_id_to_session_id[socket.id]]['participants'].indexOf( socket.id ) ;
			if( index>-1 ) {
				//collaborations[socket_id_to_session_id[socket.id]].participants.splice( index, 1 ) ;
				collaborations[socket_id_to_session_id[socket.id]]['participants'].splice( index, 1 ) ;
				found_and_removed = true ;
				//if( collaborations[socket_id_to_session_id[socket.id]].participants.length==0 ) {
				if( collaborations[socket_id_to_session_id[socket.id]]['participants'].length==0 ) {
					if( verbose ) { console.log( "last participant in collaboration, committing to disk & removing from memory" ) ; }
						// no one is left in this session, we commit it to disk & remove it from memory
						write_file( session_directory + "/" + socket_id_to_session_id[socket.id], JSON.stringify(collaborations[socket_id_to_session_id[socket.id]]['cached_instructions']) ) ;
						delete collaborations[socket_id_to_session_id[socket.id]] ;
					}
			}
		}
		if( !found_and_removed ) {
		console.log( "WARNING: could not tie socket_id to any collaboration" ) ;
		}
		console.log( collaborations ) ;
	});
	
	socket.on('send_init', function ( delta ) {
		
		let response = {
			"message": "setting initial",
			"initial": init,
			"content": `${delta}`
		}
		socket.emit('recieve_init', JSON.stringify( response ) );
		init = false
	});
});


function write_file( path, data ) {
	try {
		fs.writeFileSync( path, data ) ;
		return true ;
	} catch( e ) {
		return false ;
	}
}


function read_file( path ) {
	try {
		var data = fs.readFileSync( path ) ;
		return data ;
	} catch( e ) {
		return false
	}
}


function file_exists( path ) {
	try {
		stats = fs.lstatSync( path ) ;
		if (stats.isFile()) {
			return true ;
		}
	} catch( e ) {
		return false ;
	}
	// we should not reach that point
	return false ;
}