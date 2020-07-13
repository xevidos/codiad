( function( global, $ ) {
	
	// Define core
	let codiad = global.codiad,
	scripts = document.getElementsByTagName( 'script' ),
	path = scripts[scripts.length-1].src.split( '?' )[0],
	curpath = path.split( '/' ).slice( 0, -1 ).join( '/' ) + '/';
	
	codiad.events = {
		
		subscriptions: {},
		
		init: function() {},
		
		publish: function( topic, info ) {
			
			let _this = p.events;
			
			// If the topic doesn't exist, or there's no listeners in queue, just leave
			if( ! _this.topics.hasOwnProperty.call( _this.topics, topic ) ) {
				
				return;
			}
			
			// Cycle through topics queue, fire!
			_this.topics[topic].forEach( function( item ) {
				
				item( info !== undefined ? info : {} );
			});
		},
		
		subscribe: function( topic, listener ) {
			
			let _this = p.events;
			let add = true;
			
			// Create the topic's object if not yet created
			if( ! _this.topics.hasOwnProperty.call( _this.topics, topic ) ) {
				
				_this.topics[topic] = [];
			}
			
			// Add the listener to queue if not already in it.
			for( let i = _this.topics[topic].length;i--; ) {
				
				if( _this.topics[topic][i] == listener ) {
					
					add = false;
					break;
				}
			}
			
			if( add ) {
				
				let index = _this.topics[topic].push( listener ) - 1;
			}
			
			// Provide handle back for removal of topic
			return {
				remove: function() {
					
					delete _this.topics[topic][index];
				}
			};
		},
	};
})( this, jQuery );