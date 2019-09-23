( function( global, $ ) {
	
	var codiad = global.codiad;
	
	$( function() {
		
		codiad.project.init();
	});
	
	codiad.tasks = {
		
		controller: 'components/tasks/controller.php',
		
		init: function() {},
		
		get_task: function( id ) {
			
			let _this = this;
			
			$.ajax({
				url: _this.controller,
				type: "POST",
				dataType: 'JSON',
				data: {
					"action": 'mypit_email_save_email',
					"data": JSON.stringify( data ),
				},
				success: function( result ) {
					
					if( ! isNaN( result ) ) {
						
						_this.current_id = result;
					}
					console.log( result );
				},
				error: function(jqXHR, textStatus, errorThrown) {
					
					document.getElementById( 'mypit_message' ).style.color = "#a94442";
					document.getElementById( 'mypit_message' ).style.backgroundColor = "#f2dede";
					document.getElementById( 'mypit_message' ).style.borderColor = "#ebccd1";
					document.getElementById( 'mypit_message' ).innerHTML = "<p style='text-align: center;'>Error saving email.  Please contact the system administrator.</p>";
					document.getElementById( 'mypit_message' ).style.display = "block";
					jQuery('html, body').animate( {scrollTop: 0}, 300 );
					console.log('jqXHR:');
					console.log(jqXHR);
					console.log('textStatus:');
					console.log(textStatus);
					console.log('errorThrown:');
					console.log(errorThrown);
					throw "Error sending emails!";
				}
			});
		},
	}
});