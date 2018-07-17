/*
 *  Place copyright or other info here...
 */

(function(global, $){
	
	// Define core
	var codiad = global.codiad,
	scripts= document.getElementsByTagName('script'),
	path = scripts[scripts.length-1].src.split('?')[0],
	curpath = path.split('/').slice(0, -1).join('/')+'/';
	
	// Instantiates plugin
	$(function() {
		codiad.tela_auto_save.init();
	});
	
	codiad.tela_auto_save = {
	
		// Allows relative `this.path` linkage
		path: curpath,
		
		init: function() {
		
			// Start your plugin here...
			//let editor = document.getElementsByClassName( 'ace_content' )[0];
			let auto_save_trigger = setInterval( this.auto_save, 500 );
		},
		
		/**
		* 
		* This is where the core functionality goes, any call, references,
		* script-loads, etc...
		* 
		*/
		
		auto_save: function() {
			
			if ( codiad.active.getPath() === null ) {
				
				return;
			}
			let tabs = document.getElementsByClassName( "tab-item" );
			let path = codiad.active.getPath();
			let content = codiad.editor.getContent();
			
			codiad.active.save;
			codiad.filemanager.saveFile(path, content, localStorage.removeItem(path), false);
			var session = codiad.active.sessions[path];
			if(typeof session != 'undefined') {
				session.untainted = content;
				session.serverMTime = session.serverMTime;
				if (session.listThumb) session.listThumb.removeClass('changed');
				if (session.tabThumb) session.tabThumb.removeClass('changed');
			}
		}
	
	};

})(this, jQuery);