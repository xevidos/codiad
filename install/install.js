( function( global, $ ) {
	
	// Define core
	let codiad = global.codiad,
	scripts = document.getElementsByTagName( 'script' ),
	path = scripts[scripts.length-1].src.split( '?' )[0],
	curpath = path.split( '/' ).slice( 0, -1 ).join( '/' ) + '/';
	
	$( document ).ready( function() {
		
		codiad.install.init();
	});
	
	codiad.install = {
		
		data: {},
		dbconditions: {
			
			storage: {
				
				values: [
					{
						action: "hide",
						value: "filesystem",
					},
					{
						action: "show",
						value: "mysql",
					},
					{
						action: "show",
						value: "pgsql",
					}
				],
			},
		},
		d: {},
		form: null,
		
		init: function() {
			
			let _ = this;
			
			this.d = {
				
				storage: {
					
					default: "",
					element: $( '<select></select>' ),
					label: "Data Storage Method: ",
					name: "storage",
					options: {
						"Filesystem": "filesystem",
						"MySQL": "mysql",
						"PostgreSQL": "pgsql",
					},
					required: true,
					type: "select",
				},
				dbhost: {
					
					conditions: $.extend( true, {}, _.dbconditions ),
					default: "localhost",
					label: "Database Host: ",
					type: "text",
				},
				dbname: {
					
					conditions: $.extend( true, {}, _.dbconditions ),
					default: "",
					label: "Database Name: ",
					type: "text",
				},
				dbuser: {
					
					conditions: $.extend( true, {}, _.dbconditions ),
					default: "",
					label: "Database User: ",
					type: "text",
				},
				dbpass: {
					
					conditions: $.extend( true, {}, _.dbconditions ),
					default: "",
					label: "Database Password: ",
					type: "text",
				},
				dbpass1: {
					
					conditions: $.extend( true, {}, _.dbconditions ),
					default: "",
					label: "Repeat Password: ",
					type: "text",
				},
			};
			this.form = new codiad.forms({
				data: _.d,
				container: $( "#installation" ),
				submit_label: "Check Data Storage Method",
			});
			this.form.submit = async function() {
				
				let _this = this;
				let invalid_values;
				
				if( _this.saving ) {
					
					return;
				}
				
				_this.saving = true;
				let data = await _this.m.get_values();
				let submit = _this.v.controls.find( `[type="submit"]` );
				
				_.data = data;
				
				submit.attr( "disabled", true );
				submit.text( "Submitting ..." );
				
				let response = await codiad.common.ajax( "./index.php", "POST", data );
				
				console.log( response );
				
				let r = JSON.parse( response );
				
				if( r.status == "error" ) {
					
					console.log( "Error message", r.message );
					
					let duplicate = false;
					let message = "";
					
					if( r.tables ) {
						
						let total_tables = r.tables.length;
						
						for( let i = 0;i < total_tables;i++ ) {
							
							if( r.tables[i].error_type === "duplicate" ) {
								
								duplicate = true;
								break;
							}
						}
						
						let bypass = window.confirm( "It seems like one or more the tables you are attempting to create already exist.  Are you absolutely sure you would like to overwrite the current tables with the default ones?  This will wipe all information in the tables!" );
						
						if( bypass === true ) {
							
							let bypass2 = window.confirm( "Are you absolutely sure?  This action WILL delete all data from all tables.  Files in the workspace directory WILL NOT be deleted by this action." );
							
							if( bypass2 === true ) {
								
								data.override = "true";
								let response = await codiad.common.ajax( "./index.php", "POST", data );
								console.log( "User is overriding." );
								console.log( response );
								_.user_setup();
							}
						}
					} else {
						
						codiad.message.error( r.message );
						$( "#data_status" ).html( "<br><br>Data Status:<br>" + r.value );
						
						submit.text( _this.submit_label );
						submit.attr( "disabled", false );
						_this.saving = false;
					}
				} else {
					
					_.user_setup();
				}
				
				submit.text( _this.submit_label );
				submit.attr( "disabled", false );
				_this.saving = false;
			}
		},
		
		user_setup: function() {
			
			let _ = codiad.install;
			let _this = this;
			
			this.d = {
				username: {
					
					default: "",
					label: "Username: ",
					type: "text",
				},
				password: {
					
					default: "",
					label: "Password: ",
					type: "text",
				},
				password1: {
					
					default: "",
					label: "Repeat Password: ",
					type: "text",
				},
			};
			this.form = new codiad.forms({
				data: _this.d,
				container: $( "#installation" ),
				submit_label: "Create User",
			});
			this.form.submit = async function() {
				
				let _this = this;
				let invalid_values;
				
				if( _this.saving ) {
					
					return;
				}
				
				_this.saving = true;
				let data = await _this.m.get_values();
				let submit = _this.v.controls.find( `[type="submit"]` );
				
				submit.attr( "disabled", true );
				submit.text( "Submitting ..." );
				
				data.storage = _.data.storage;
				
				if( _.data.dbhost ) {
					
					data.dbhost = _.data.dbhost;
					data.dbname = _.data.dbname;
					data.dbuser = _.data.dbuser;
					data.dbpass = _.data.dbpass;
				}
				
				let response = await codiad.common.ajax( "./index.php", "POST", data );
				
				console.log( response );
				
				let r = JSON.parse( response );
				
				if( r.status == "error" ) {
					
					codiad.message.error( r.message );
					$( "#data_status" ).html( "<br><br>User Status:<br>" + r.value );
				} else {
					
					$( "#data_status" ).html( "<br><br>User Status:<br>Testing User data" );
					window.location.href = "./../";
				}
				
				submit.text( _this.submit_label );
				submit.attr( "disabled", false );
				_this.saving = false;
			}
		},
	};
})( this, jQuery );