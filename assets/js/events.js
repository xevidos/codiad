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
			
			// If the topic doesn't exist, or there's no listeners in queue, just leave
			if( ! this.subscriptions.hasOwnProperty.call( this.subscriptions, topic ) ) {
				
				return;
			}
			
			// Cycle through topics queue, fire!
			this.subscriptions[topic].forEach( function( item ) {
				
				item( info !== undefined ? info : {} );
			});
		},
		
		subscribe: function( topic, listener ) {
			
			let add = true;
			
			// Create the topic's object if not yet created
			if( ! this.subscriptions.hasOwnProperty.call( this.subscriptions, topic ) ) {
				
				this.subscriptions[topic] = [];
			}
			
			// Add the listener to queue if not already in it.
			for( let i = this.subscriptions[topic].length;i--; ) {
				
				if( this.subscriptions[topic][i] == listener ) {
					
					add = false;
					break;
				}
			}
			
			if( add ) {
				
				let index = this.subscriptions[topic].push( listener ) - 1;
			}
			
			// Provide handle back for removal of topic
			return {
				remove: function() {
					
					delete this.subscriptions[topic][index];
				}
			};
		},
	};
})( this, jQuery );