( function( global, $ ) {
	
	$( document ).ready( function() {
		
		let dbconditions = {
			
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
		};
		
		let d = {
			
			permissions: {
				default: "false",
				element: $( '<pre>Checking ...</pre>' ),
				label: "Permission Checks: ",
				name: "permissions",
				type: "custom",
			},
			storage: {
				
				default: "true",
				element: $( '<select></select>' ),
				label: "Data Storage Method: ",
				name: "storage",
				options: {
					"Filesystem": "filesystem",
					"MySQL": "mysql",
					"PostgreSQL": "pgsql",
				},
				type: "select",
			},
			dbhost: {
				
				conditions: $.extend( true, {}, dbconditions ),
				default: "localhost",
				label: "Database Host: ",
				type: "text",
			},
			dbname: {
				
				conditions: $.extend( true, {}, dbconditions ),
				default: "",
				label: "Database Name: ",
				type: "text",
			},
			dbuser: {
				
				conditions: $.extend( true, {}, dbconditions ),
				default: "",
				label: "Database User: ",
				type: "text",
			},
			dbpass: {
				
				conditions: $.extend( true, {}, dbconditions ),
				default: "",
				label: "Database Password: ",
				type: "text",
			},
			dbpass1: {
				
				conditions: $.extend( true, {}, dbconditions ),
				default: "",
				label: "Repeat Password: ",
				type: "text",
			},
		};
		
		let form = new codiad.forms({
			data: d,
			container: $( "#installation" ),
			submit_label: "Check Data Storage Method",
		});
		form.submit = function() {
			
			
			console.log( "Submitted ..." );
		}
	});
})( this, jQuery );