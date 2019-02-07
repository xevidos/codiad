/*
 *  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
 *  as-is and without warranty under the MIT License. See
 *  [root]/license.txt for more. This information must remain intact.
 */

	(function(global, $){
	
		var codiad = global.codiad;
		
		$(window)
		.load(function() {
			codiad.filemanager.init();
		});
	

    codiad.filemanager = {
		
		auto_reload: false,
        clipboard: '',
        controller: 'components/filemanager/controller.php',
        dialog: 'components/filemanager/dialog.php',
        dialogUpload: 'components/filemanager/dialog_upload.php',
		preview: null,
		
        init: async function() {
        	
        	this.noAudio = [
				//Audio
				'aac',
				'aif',
				'mp3',
				'mp4',
				'wav',
				'ogg',
			],
        	this.noFiles = [
				//Files
				'exe',
				'pdf',
				'zip',
				'tar',
				'tar.gz',
			],
			this.noImages = [
				//Images
				'ico',
				'icon',
				'jpg',
				'jpeg',
				'png',
				'gif',
				'bmp',
			],
			
	        
	    	this.noOpen = this.noAudio.concat( this.noFiles, this.noImages ),
	        this.noBrowser = this.noAudio.concat( this.noImages ),
	        
            // Initialize node listener
            this.nodeListener();
            this.auto_reload = ( await codiad.settings.get_option( "codiad.filemanager.auto_reload_preview" ) == "true" );
            
            console.log( this.auto_reload );
            
            amplify.subscribe( 'settings.save', async function() {
            
	            let option = ( await codiad.settings.get_option( "codiad.filemanager.auto_reload_preview" ) == "true" );
	            if( option != codiad.filemanager.auto_reload ) {
	                
	                //codiad.auto_save.reload_interval();
	                window.location.reload();
	            }
			});
            
            /* Subscribe to know when a file become active. */
			amplify.subscribe( 'active.onFocus', async function( path ) {
				
				let _this = codiad.filemanager;
				let editor = codiad.editor.getActive();

				if( _this.auto_reload && editor !== null ) {
                   	
                   _this.preview.addEventListener( "beforeunload", _this.closePreview );
                   codiad.editor.getActive().addEventListener( "change", _this.refreshPreview );
               }
			});
            
            // Load uploader
            $.loadScript("components/filemanager/upload_scripts/jquery.ui.widget.js", true);
            $.loadScript("components/filemanager/upload_scripts/jquery.iframe-transport.js", true);
            $.loadScript("components/filemanager/upload_scripts/jquery.fileupload.js", true);
        },

        //////////////////////////////////////////////////////////////////
        // Listen for dbclick events on nodes
        //////////////////////////////////////////////////////////////////

        nodeListener: function() {
            var _this = this;
            
            $('#file-manager').on('selectstart', false);

            $('#file-manager span')
                .live('click', function() { // Open or Expand
                    if ($(this).parent().children("a").attr('data-type') == 'directory') {
                        _this.index($(this).parent().children("a")
                            .attr('data-path'));
                    } else {
                        _this.openFile($(this).parent().children("a")
                            .attr('data-path'));
                    }
                    if (!$(this).hasClass('none')) {
                        if ($(this).hasClass('plus')) {
                            $(this).removeClass('plus')
                            $(this).addClass('minus');
                        } else {
                            $(this).removeClass('minus')
                            $(this).addClass('plus');
                        }
                    }
                });
            $('#file-manager a')
                .live('dblclick', function() { // Open or Expand
                    if (!codiad.editor.settings.fileManagerTrigger) {
                      if ($(this)
                          .hasClass('directory')) {
                          _this.index($(this)
                              .attr('data-path'));
                      } else {
                          _this.openFile($(this)
                              .attr('data-path'));
                      }
                      if (!$(this).parent().children("span").hasClass('none')) {
                          if ($(this).parent().children("span").hasClass('plus')) {
                              $(this).parent().children("span").removeClass('plus')
                              $(this).parent().children("span").addClass('minus');
                          } else {
                              $(this).parent().children("span").removeClass('minus')
                              $(this).parent().children("span").addClass('plus');
                          }
                      }
                    }
                })
                .live('click', function() { // Open or Expand
                    if (codiad.editor.settings.fileManagerTrigger) {
                      if ($(this)
                          .hasClass('directory')) {
                          _this.index($(this)
                              .attr('data-path'));
                      } else {
                          _this.openFile($(this)
                              .attr('data-path'));
                      }
                      if (!$(this).parent().children("span").hasClass('none')) {
                          if ($(this).parent().children("span").hasClass('plus')) {
                              $(this).parent().children("span").removeClass('plus')
                              $(this).parent().children("span").addClass('minus');
                          } else {
                              $(this).parent().children("span").removeClass('minus')
                              $(this).parent().children("span").addClass('plus');
                          }
                      }
                    }
                })
                .live("contextmenu", function(e) { // Context Menu
                    e.preventDefault();
                    _this.contextMenuShow(e, $(this)
                        .attr('data-path'), $(this)
                        .attr('data-type'), $(this)
                        .html());
                    $(this)
                        .addClass('context-menu-active');
                });
        },

        //////////////////////////////////////////////////////////////////
        // Context Menu
        //////////////////////////////////////////////////////////////////

        contextMenuShow: function(e, path, type, name) {
            var _this = this;
			
			$('#context-menu a, #context-menu hr').hide();
            // Selective options
            switch (type) {
            case 'directory':
                $('#context-menu .directory-only, #context-menu .non-root, #context-menu .both').show();
                break;
            case 'file':
                $('#context-menu .file-only, #context-menu .non-root, #context-menu .both').show();
                break;
            case 'root':
                $('#context-menu .directory-only, #context-menu .root-only').show();
                break;
            case 'editor':
            	$('#context-menu .editor-only').show();
            	break;
            }
            if(codiad.project.isAbsPath($('#file-manager a[data-type="root"]').attr('data-path'))) {
                $('#context-menu .no-external').hide();
            } else if( type == "editor" ) {
            	$('#context-menu .no-external').hide();
            } else {
                $('#context-menu .no-external').show();
            }
            // Show menu
            var top = e.pageY;
            if (top > $(window).height() - $('#context-menu').height()) {
                top -= $('#context-menu').height();
            }
            if (top < 10) {
                top = 10;
            }
            var max = $(window).height() - top - 10;
            
            $('#context-menu')
                .css({
                    'top': top + 'px',
                    'left': e.pageX + 'px',
                    'max-height': max + 'px'
                })
                .fadeIn(200)
                .attr('data-path', path)
                .attr('data-type', type)
                .attr('data-name', name);
            // Show faded 'paste' if nothing in clipboard
            if (this.clipboard === '') {
                $('#context-menu a[content="Paste"]')
                    .addClass('disabled');
            } else {
                $('#context-menu a[data-action="paste"]')
                    .removeClass('disabled');
            }
            // Hide menu
            /**
        	 * make sure that the user has moved their mouse far enough
        	 * away from the context menu to warrant a close.
        	 */
        	$('#file-manager, #editor-region').on( 'mousemove', codiad.filemanager.contextCheckMouse );
        	$('#context-menu, #editor-region').on( 'paste', codiad.editor.paste );
        	
            /* Notify listeners. */
            amplify.publish('context-menu.onShow', {e: e, path: path, type: type});
            // Hide on click
            $('#context-menu a')
                .click(function() {
                    _this.contextMenuHide();
                });
        },
		
		contextCheckMouse: function( e ) {
                		
    		let offset = $('#context-menu').offset();
        	let bottom = offset.top + $('#context-menu').outerHeight( true ) + 20;
        	let left = offset.left - 20;
        	let right =  offset.left + $('#context-menu').outerWidth( true ) + 20;
        	let top = offset.top - 20;
        	
        	if( ( e.clientX > right || e.clientX < left ) || ( e.clientY > bottom || e.clientY < top ) ) {
        		
        		$('#file-manager, #editor-region').off( 'mousemove', codiad.filemanager.contextCheckMouse );
        		$('#context-menu, #editor-region').off( 'paste', codiad.editor.paste );
        		codiad.filemanager.contextMenuHide();
        	}
    	},
		
        contextMenuHide: function() {
            $('#context-menu')
                .fadeOut(200);
            $('#file-manager a')
                .removeClass('context-menu-active');
            /* Notify listeners. */
            amplify.publish('context-menu.onHide');
        },

        //////////////////////////////////////////////////////////////////
        // Return the node name (sans path)
        //////////////////////////////////////////////////////////////////

        getShortName: function(path) {
            return path.split('/')
                .pop();
        },

        //////////////////////////////////////////////////////////////////
        // Return extension
        //////////////////////////////////////////////////////////////////

        getExtension: function(path) {
            return path.split('.')
                .pop();
        },

        //////////////////////////////////////////////////////////////////
        // Return type
        //////////////////////////////////////////////////////////////////

        getType: function(path) {
            return $('#file-manager a[data-path="' + path + '"]')
                .attr('data-type');
        },

        //////////////////////////////////////////////////////////////////
        // Create node in file tree
        //////////////////////////////////////////////////////////////////

        createObject: function(parent, path, type) {
            // NODE FORMAT: <li><a class="{type} {ext-file_extension}" data-type="{type}" data-path="{path}">{short_name}</a></li>
            var parentNode = $('#file-manager a[data-path="' + parent + '"]');
            if (!$('#file-manager a[data-path="' + path + '"]')
                .length) { // Doesn't already exist
                if (parentNode.hasClass('open') && parentNode.hasClass('directory')) { // Only append node if parent is open (and a directory)
                    var shortName = this.getShortName(path);
                    if (type == 'directory') {
                        var appendage = '<li><span class="none"></span><a class="directory" data-type="directory" data-path="' + path + '">' + shortName + '</a></li>';
                    } else {
                        var appendage = '<li><span class="none"></span><a class="file ext-' +
                            this.getExtension(shortName) +
                            '" data-type="file" data-path="' +
                            path + '">' + shortName + '</a></li>';
                    }
                    if (parentNode.siblings('ul')
                        .length) { // UL exists, other children to play with
                        parentNode.siblings('ul')
                            .append(appendage);
                    } else {
                        $('<ul>' + appendage + '</ul>')
                            .insertAfter(parentNode);
                    }
                } else {
                    parentNode.parent().children('span').removeClass('none');  
                    parentNode.parent().children('span').addClass('plus');  
                }
            }
        },

        //////////////////////////////////////////////////////////////////
        // Loop out all files and folders in directory path
        //////////////////////////////////////////////////////////////////

        indexFiles: [],

        index: function(path, rescan) {
            var _this = this;
            if (rescan === undefined) {
                rescan = false;
            }
            node = $('#file-manager a[data-path="' + path + '"]');
            if (node.hasClass('open') && !rescan) {
                node.parent('li')
                    .children('ul')
                    .slideUp(300, function() {
                    $(this)
                        .remove();
                    node.removeClass('open');
                });
            } else {
                node.addClass('loading');
                $.get(this.controller + '?action=index&path=' + encodeURIComponent(path), function(data) {
                    node.addClass('open');
                    var objectsResponse = codiad.jsend.parse(data);
                    if (objectsResponse != 'error') {
                        /* Notify listener */
                        _this.indexFiles = objectsResponse.index;
                        amplify.publish("filemanager.onIndex", {path: path, files: _this.indexFiles});
                        var files = _this.indexFiles;
                        if (files.length > 0) {
                            if (node.parent().children('span').hasClass('plus')) {
                                node.parent().children('span').removeClass('plus').addClass('minus');
                            }
                            var display = 'display:none;';
                            if (rescan) {
                                display = '';
                            }
                            var appendage = '<ul style="' + display + '">';
                            $.each(files, function(index) {
                                var ext = '';
                                var name = files[index].name.replace(path, '');
                                var nodeClass = 'none';
                                name = name.split('/')
                                    .join(' ');
                                if (files[index].type == 'file') {
                                    var ext = ' ext-' + name.split('.')
                                        .pop();
                                }
                                if(files[index].type == 'directory' && files[index].size > 0) {
                                    nodeClass = 'plus';
                                } 
                                appendage += '<li><span class="' + nodeClass + '"></span><a class="' + files[index].type + ext + '" data-type="' + files[index].type + '" data-path="' + files[index].name + '">' + name + '</a></li>';
                            });
                            appendage += '</ul>';
                            if (rescan) {
                                node.parent('li')
                                    .children('ul')
                                    .remove();
                            }
                            $(appendage)
                                .insertAfter(node);
                            if (!rescan) {
                                node.siblings('ul')
                                    .slideDown(300);
                            }
                        }
                    }
                    node.removeClass('loading');
                    if (rescan && _this.rescanChildren.length > _this.rescanCounter) {
                        _this.rescan(_this.rescanChildren[_this.rescanCounter++]);
                    } else {
                        _this.rescanChildren = [];
                        _this.rescanCounter = 0;
                    }
                });
            }
        },

        rescanChildren: [],

        rescanCounter: 0,

        rescan: function(path) {
            var _this = this;
            if (this.rescanCounter === 0) {
                // Create array of open directories
                node = $('#file-manager a[data-path="' + path + '"]');
                node.parent().find('a.open').each(function() {
                    _this.rescanChildren.push($(this).attr('data-path'));
                });
            }
            
            this.index(path, true);
        },

        //////////////////////////////////////////////////////////////////
        // Open File
        //////////////////////////////////////////////////////////////////

        openFile: function(path, focus) {
            if (focus === undefined) {
                focus = true;
            }
            var node = $('#file-manager a[data-path="' + path + '"]');
            var ext = this.getExtension(path);
            if ($.inArray(ext.toLowerCase(), this.noOpen) < 0) {
                node.addClass('loading');
                $.get(this.controller + '?action=open&path=' + encodeURIComponent(path), function(data) {
                    var openResponse = codiad.jsend.parse(data);
                    if (openResponse != 'error') {
                        node.removeClass('loading');
                        codiad.active.open(path, openResponse.content, openResponse.mtime, false, focus);
                    }
                });
            } else {
                if(!codiad.project.isAbsPath(path)) {
                    if ($.inArray(ext.toLowerCase(), this.noBrowser) < 0) {
                        this.download(path);
                    } else {
                        this.openInModal(path);
                    }
                 } else {
                    codiad.message.error(i18n('Unable to open file in Browser while using absolute path.'));
                 }
            }
        },

        //////////////////////////////////////////////////////////////////
        // Open in browser
        //////////////////////////////////////////////////////////////////

        openInBrowser: function(path) {
        	
        	let _this = this;

            $.ajax({
                url: this.controller + '?action=open_in_browser&path=' + encodeURIComponent(path),
                success: function(data) {
                    var openIBResponse = codiad.jsend.parse(data);
                    if (openIBResponse != 'error') {
                    	
                       _this.preview = window.open( openIBResponse.url, '_newtab' );
                       
                       let editor = codiad.editor.getActive();
                       
                       if( _this.auto_reload && editor !== null ) {
	                       	
	                       _this.preview.addEventListener( "beforeunload", _this.closePreview );
	                       codiad.editor.getActive().addEventListener( "change", _this.refreshPreview );
                       }
                       
                       
                    }
                },
                async: false
            });
        },
        
        closePreview: function( event ) {
        	
        	_this = codiad.filemanager;
        	_this.preview = null;
        },
        
        refreshPreview: function( event ) {
        	
        	_this = codiad.filemanager;
        	
        	if( _this.preview == null ) {
        		
        		return;
        	}
        	
        	_this.preview.location.reload();
        },
        
        openInModal: function(path) {
        	
        	let type = "";
        	var ext = this.getExtension(path).toLowerCase();
        	
        	if ( this.noAudio.includes(ext) ) {
        		
        		type = 'music_preview';
        	} else if ( this.noImages.includes(ext) ) {
        		
        		type = 'preview';
        	}
        	
            codiad.modal.load(250, this.dialog, {
                        action: type,
                        path: path
                    });
        },
        saveModifications: function(path, data, callbacks, save=true){
            callbacks = callbacks || {};
            var _this = this, action, data;
            var notifySaveErr = function() {
                codiad.message.error(i18n('File could not be saved'));
                if (typeof callbacks.error === 'function') {
                    var context = callbacks.context || _this;
                    callbacks.error.apply(context, [data]);
                }
            }
            $.post(this.controller + '?action=modify&path=' + encodeURIComponent(path), data, function(resp){
                resp = $.parseJSON(resp);
                if (resp.status == 'success') {
                    if ( save === true ) {
                    	codiad.message.success(i18n('File saved'));
                	}
                    if (typeof callbacks.success === 'function'){
                        var context = callbacks.context || _this;
                        callbacks.success.call(context, resp.data.mtime);
                    }
                } else {
                    if (resp.message == 'Client is out of sync'){
                        var reload = confirm(
                            "Server has a more updated copy of the file. Would "+
                            "you like to refresh the contents ? Pressing no will "+
                            "cause your changes to override the server's copy upon "+
                            "next save."
                        );
                        if (reload) {
                            codiad.active.close(path);
                            codiad.active.removeDraft(path);
                            _this.openFile(path);
                        } else {
                            var session = codiad.editor.getActive().getSession();
                            session.serverMTime = null;
                            session.untainted = null;
                        }
                    } else codiad.message.error(i18n('File could not be saved'));
                    if (typeof callbacks.error === 'function') {
                        var context = callbacks.context || _this;
                        callbacks.error.apply(context, [resp.data]);
                    }
                }
            }).error(notifySaveErr);
        },
        //////////////////////////////////////////////////////////////////
        // Save file
        //////////////////////////////////////////////////////////////////
		
        saveFile: function(path, content, callbacks, save=true) {
            this.saveModifications(path, {content: content}, callbacks, save);
        },
		
        savePatch: function(path, patch, mtime, callbacks, alerts) {
            if (patch.length > 0)
                this.saveModifications(path, {patch: patch, mtime: mtime}, callbacks, alerts);
            else if (typeof callbacks.success === 'function'){
                var context = callbacks.context || this;
                callbacks.success.call(context, mtime);
            }
        },
		
        //////////////////////////////////////////////////////////////////
        // Create Object
        //////////////////////////////////////////////////////////////////
		
        createNode: function(path, type) {
            codiad.modal.load(250, this.dialog, {
                action: 'create',
                type: type,
                path: path
            });
            $('#modal-content form')
                .live('submit', function(e) {
                    e.preventDefault();
                    var shortName = $('#modal-content form input[name="object_name"]')
                        .val();
                    var path = $('#modal-content form input[name="path"]')
                        .val();
                    var type = $('#modal-content form input[name="type"]')
                        .val();
                    var createPath = path + '/' + shortName;
                    $.get(codiad.filemanager.controller + '?action=create&path=' + encodeURIComponent(createPath) + '&type=' + type, function(data) {
                        var createResponse = codiad.jsend.parse(data);
                        if (createResponse != 'error') {
                            codiad.message.success(type.charAt(0)
                                .toUpperCase() + type.slice(1) + ' Created');
                            codiad.modal.unload();
                            // Add new element to filemanager screen
                            codiad.filemanager.createObject(path, createPath, type);
                            if(type == 'file') {
                                codiad.filemanager.openFile(createPath, true);
                            }
                            
                            codiad.filemanager.rescan( path );
                            
                            /* Notify listeners. */
                            amplify.publish('filemanager.onCreate', {createPath: createPath, path: path, shortName: shortName, type: type});
                        }
                    });
                });
        },

        //////////////////////////////////////////////////////////////////
        // Copy to Clipboard
        //////////////////////////////////////////////////////////////////

        copyNode: function(path) {
            this.clipboard = path;
            codiad.message.success(i18n('Copied to Clipboard'));
        },

        //////////////////////////////////////////////////////////////////
        // Paste
        //////////////////////////////////////////////////////////////////

        pasteNode: function(path) {
            var _this = this;
            if (this.clipboard == '') {
                codiad.message.error(i18n('Nothing in Your Clipboard'));
            } else if (path == this.clipboard) {
                codiad.message.error(i18n('Cannot Paste Directory Into Itself'));
            } else {
                var shortName = _this.getShortName(_this.clipboard);
                if ($('#file-manager a[data-path="' + path + '/' + shortName + '"]')
                    .length) { // Confirm overwrite?
                    codiad.modal.load(400, this.dialog, {
                        action: 'overwrite',
                        path: path + '/' + shortName
                    });
                    $('#modal-content form')
                        .live('submit', function(e) {
                        e.preventDefault();
                        var duplicate = false;
                        if($('#modal-content form select[name="or_action"]').val()==1){
                            duplicate=true; console.log('Dup!');
                        }
                        _this.processPasteNode(path,duplicate);
                    });
                } else { // No conflicts; proceed...
                    _this.processPasteNode(path,false);
                }
                
                codiad.filemanager.rescan( path );
            }
        },
		
        processPasteNode: function(path,duplicate) {
            var _this = this;
            var shortName = this.getShortName(this.clipboard);
            var type = this.getType(this.clipboard);
            
            $.get(this.controller + '?action=duplicate&path=' +
                encodeURIComponent(this.clipboard) + '&destination=' +
                encodeURIComponent(path + '/' + shortName) + '&duplicate=' + encodeURIComponent( duplicate ), function(data) {
                    var pasteResponse = codiad.jsend.parse(data);
                    if (pasteResponse != 'error') {
                        _this.createObject(path, path + '/' + shortName, type);
                        codiad.modal.unload();
                        /* Notify listeners. */
                        amplify.publish('filemanager.onPaste', {path: path, shortName: shortName, duplicate: duplicate});
                    }
                });
        },
		
        //////////////////////////////////////////////////////////////////
        // Rename
        //////////////////////////////////////////////////////////////////
		
        renameNode: function(path) {
            var shortName = this.getShortName(path);
            var type = this.getType(path);
            var _this = this;
            codiad.modal.load(250, this.dialog, { action: 'rename', path: path, short_name: shortName, type: type});
            $('#modal-content form')
                .live('submit', function(e) {
                    e.preventDefault();
                    var newName = $('#modal-content form input[name="object_name"]')
                        .val();
                    // Build new path
                    var arr = path.split('/');
                    var temp = new Array();
                    for (i = 0; i < arr.length - 1; i++) {
                        temp.push(arr[i])
                    }
                    var newPath = temp.join('/') + '/' + newName;
                    $.get(_this.controller, { action: 'modify', path: path, new_name: newName} , function(data) {
                        var renameResponse = codiad.jsend.parse(data);
                        if (renameResponse != 'error') {
                            codiad.message.success(type.charAt(0)
                                .toUpperCase() + type.slice(1) + ' Renamed');
                            var node = $('#file-manager a[data-path="' + path + '"]');
                            let parentPath = node.parent().parent().prev().attr('data-path');
                            // Change pathing and name for node
                            node.attr('data-path', newPath)
                                .html(newName);
                            if (type == 'file') { // Change icons for file
                                curExtClass = 'ext-' + _this.getExtension(path);
                                newExtClass = 'ext-' + _this.getExtension(newPath);
                                $('#file-manager a[data-path="' + newPath + '"]')
                                    .removeClass(curExtClass)
                                    .addClass(newExtClass);
                            } else { // Change pathing on any sub-files/directories
                                _this.repathSubs(path, newPath);
                            }
                            // Change any active files
                            codiad.active.rename(path, newPath);
                            codiad.modal.unload();
                            codiad.filemanager.rescan( parentPath );
                            /* Notify listeners. */
                        	amplify.publish('filemanager.onRename', {path: path, newPath: newPath, parentPath: parentPath });
                        }
                    });
                });
        },

        repathSubs: function(oldPath, newPath) {
            $('#file-manager a[data-path="' + newPath + '"]')
                .siblings('ul')
                .find('a')
                .each(function() {
                // Hit the children, hit 'em hard
                var curPath = $(this)
                    .attr('data-path');
                var revisedPath = curPath.replace(oldPath, newPath);
                $(this)
                    .attr('data-path', revisedPath);
            });
        },

        //////////////////////////////////////////////////////////////////
        // Delete
        //////////////////////////////////////////////////////////////////

        deleteNode: function(path) {
            var _this = this;
            codiad.modal.load(400, this.dialog, {
                action: 'delete',
                path: path
            });
            $('#modal-content form')
                .live('submit', function(e) {
                e.preventDefault();
                $.get(_this.controller + '?action=delete&path=' + encodeURIComponent(path), function(data) {
                    var deleteResponse = codiad.jsend.parse(data);
                    if (deleteResponse != 'error') {
                        var node = $('#file-manager a[data-path="' + path + '"]');
                        let parent_path = node.parent().parent().prev().attr('data-path');
                        node.parent('li').remove();
                        // Close any active files
                        $('#active-files a')
                            .each(function() {
                                var curPath = $(this)
                                    .attr('data-path');
                                if (curPath.indexOf(path) == 0) {
                                    codiad.active.remove(curPath);
                                }
                            });
                        /* Notify listeners. */
                    	amplify.publish('filemanager.onDelete', {deletePath: path, path: parent_path });
                    }
                    codiad.modal.unload();
                });
            });
        },
		
		deleteInnerNode: function(path) {
            var _this = this;
            codiad.modal.load(400, this.dialog, {
                action: 'delete',
                path: path
            });
            $('#modal-content form')
                .live('submit', function(e) {
                e.preventDefault();
                $.get(_this.controller + '?action=deleteInner&path=' + encodeURIComponent(path), function(data) {
                    var deleteResponse = codiad.jsend.parse(data);
                    if (deleteResponse != 'error') {
                        var node = $('#file-manager a[data-path="' + path + '"]').parent('ul').remove();
						
                        // Close any active files
                        $('#active-files a')
                            .each(function() {
                                var curPath = $(this)
                                    .attr('data-path');
                                if (curPath.indexOf(path) == 0) {
                                    codiad.active.remove(curPath);
                                }
                            });
                            
	                    //Rescan Folder
	                    node.parent()
	                    .find('a.open')
	                    .each(function() {
	                        _this.rescanChildren.push($(this)
	                            .attr('data-path'));
	                    });
	                    
	                    /* Notify listeners. */
                    	amplify.publish('filemanager.onDelete', {deletePath: path + "/*", path: path });
                    }
                    codiad.modal.unload();
                });
            });
        },
        //////////////////////////////////////////////////////////////////
        // Search
        //////////////////////////////////////////////////////////////////

        search: function(path) {
            codiad.modal.load(500, this.dialog,{
                action: 'search',
                path: path
            });
            codiad.modal.load_process.done( async function() {
                var lastSearched = JSON.parse( await codiad.settings.get_option("lastSearched"));
                if(lastSearched) {
                    $('#modal-content form input[name="search_string"]').val(lastSearched.searchText);
                    $('#modal-content form input[name="search_file_type"]').val(lastSearched.fileExtension);
                    $('#modal-content form select[name="search_type"]').val(lastSearched.searchType);
                    if(lastSearched.searchResults != '') {
                      $('#filemanager-search-results').slideDown().html(lastSearched.searchResults);
                    }
                }
            });
            codiad.modal.hideOverlay();
            var _this = this;
            $('#modal-content form')
                .live('submit', function(e) {
                $('#filemanager-search-processing')
                    .show();
                e.preventDefault();
                searchString = $('#modal-content form input[name="search_string"]')
                    .val();
                fileExtensions=$('#modal-content form input[name="search_file_type"]')
                     .val();
                searchFileType=$.trim(fileExtensions);
                if (searchFileType != '') {
                    //season the string to use in find command
                    searchFileType = "\\(" + searchFileType.replace(/\s+/g, "\\|") + "\\)";
                }
                searchType = $('#modal-content form select[name="search_type"]')
                    .val();
                $.post(_this.controller + '?action=search&path=' + encodeURIComponent(path) + '&type=' + searchType, {
                    search_string: searchString,
                    search_file_type: searchFileType
                }, function(data) {
                    searchResponse = codiad.jsend.parse(data);
                    var results = '';
                    if (searchResponse != 'error') {
                        $.each(searchResponse.index, function(key, val) {
                            // Cleanup file format
                            if(val['file'].substr(-1) == '/') {
                                val['file'] = val['file'].substr(0, str.length - 1);
                            }
                            val['file'] = val['file'].replace('//','/');
                            // Add result
                            results += '<div><a onclick="codiad.filemanager.openFile(\'' + val['result'] + '\');setTimeout( function() { codiad.active.gotoLine(' + val['line'] + '); }, 500);codiad.modal.unload();">Line ' + val['line'] + ': ' + val['file'] + '</a></div>';
                        });
                        $('#filemanager-search-results')
                            .slideDown()
                            .html(results);
                    } else {
                        $('#filemanager-search-results')
                            .slideUp();
                    }
                    _this.saveSearchResults(searchString, searchType, fileExtensions, results);
                    $('#filemanager-search-processing')
                        .hide();
                });
            });
        },

        /////////////////////////////////////////////////////////////////
        // saveSearchResults
        /////////////////////////////////////////////////////////////////
        saveSearchResults: function(searchText, searchType, fileExtensions, searchResults) {
            var lastSearched = {
                searchText: searchText,
                searchType: searchType,
                fileExtension: fileExtensions,
                searchResults: searchResults
            };
            localStorage.setItem("lastSearched", JSON.stringify(lastSearched));
        },
        //////////////////////////////////////////////////////////////////
        // Upload
        //////////////////////////////////////////////////////////////////

        uploadToNode: function(path) {
            codiad.modal.load(500, this.dialogUpload, {path: path});
        },

        //////////////////////////////////////////////////////////////////
        // Download
        //////////////////////////////////////////////////////////////////

        download: function(path) {
            var type = this.getType(path);
            $('#download')
                .attr('src', 'components/filemanager/download.php?path=' + encodeURIComponent(path) + '&type=' + type);
        }
    };

})(this, jQuery);
