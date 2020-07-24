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
			
			let _this = this;
			
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
					
					conditions: $.extend( true, {}, _this.dbconditions ),
					default: "localhost",
					label: "Database Host: ",
					type: "text",
				},
				dbname: {
					
					conditions: $.extend( true, {}, _this.dbconditions ),
					default: "",
					label: "Database Name: ",
					type: "text",
				},
				dbuser: {
					
					conditions: $.extend( true, {}, _this.dbconditions ),
					default: "",
					label: "Database User: ",
					type: "text",
				},
				dbpass: {
					
					conditions: $.extend( true, {}, _this.dbconditions ),
					default: "",
					label: "Database Password: ",
					type: "text",
				},
				dbpass1: {
					
					conditions: $.extend( true, {}, _this.dbconditions ),
					default: "",
					label: "Repeat Password: ",
					type: "text",
				},
			};
			this.form = new codiad.forms({
				data: _this.d,
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
				
				submit.attr( "disabled", true );
				submit.text( "Submitting ..." );
				
				let response = await codiad.common.ajax( "./index.php", "POST", data );
				
				console.log( response );
				
				let r = JSON.parse( response );
				
				if( r.status == "error" ) {
					
					codiad.message.error( r.message );
					$( "#data_status" ).html( "<br><br>Data Status:<br>" + r.value );
				}
				
				submit.text( _this.submit_label );
				submit.attr( "disabled", false );
				_this.saving = false;
			}
		}
	};
})( this, jQuery );