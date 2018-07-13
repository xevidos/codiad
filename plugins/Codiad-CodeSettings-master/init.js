/*
 * Copyright (c) Codiad & Andr3as, distributed
 * as-is and without warranty under the MIT License.
 * See http://opensource.org/licenses/MIT for more information. 
 * This information must remain intact.
 */

(function(global, $){
    
    var codiad = global.codiad,
        scripts = document.getElementsByTagName('script'),
        path = scripts[scripts.length-1].src.split('?')[0],
        curpath = path.split('/').slice(0, -1).join('/')+'/';

    $(function() {
        codiad.CodeSettings.init();
    });
    
    codiad.CodeSettings = {
        
        path: curpath,
        file: "",
        open: false,
        redo: false,
        settings: null,
        template: "",
        commands: null,
        entries: 0,
        
        init: function() {
            var _this = this;
            this.$pSave = this.saveExpert.bind(this);
            this.$cSave = codiad.active.save.bind(this);
            //Load keymap
            this.load();
            //Load template
            $.get(this.path+"template.html", function(html){
                _this.template = html;
            });
            $.getScript(this.path+"beautify.js");
            //Load default commands
            $.getJSON(this.path+"default.commands.json", function(json){
                _this.commands = json;
            });
            //Set keymap
            amplify.subscribe("active.onOpen", function(path){
                //Overwrite save commands
                var manager = codiad.editor.getActive().commands;
                manager.addCommand({
                    name: 'Save',
                    bindKey: "Ctrl-S",
                    exec: function () {
                        codiad.active.save();
                    }
                });
                _this.setKeys();
                if (_this.open && path === _this.file && _this.redo) {
                    _this.addCommands();
                }
                if (codiad.editor.getActive() !== null) {
                    _this.commands = codiad.editor.getActive().commands.byName;
                }
                //Save current existing commands
                setTimeout(function(){
                    $.post(_this.path+"controller.php?action=saveCommands", {commands: JSON.stringify(_this.commands)}, function(data){
                        var json = JSON.parse(data);
                        if (json.status == "error") {
                            codiad.message.error(json.message);
                        }
                    });
                }, 10);
            });
            amplify.subscribe("active.onFocus", function(path){
                if (_this.open) {
                    if (path === _this.file) {
                        _this.addCommands();
                    } else {
                        _this.restoreCommands();
                    }
                }
            });
            amplify.subscribe("active.onClose", function(path){
                if (_this.file === path) {
                    _this.open = false;
                }
            });
            
            amplify.subscribe('settings.dialog.save', function(){
                if ($('#hotkey_div').length > 0) {
                    _this.saveDialog();
                }
            });
            //Live feature
            $('.command_name').live("change", function(){
                var line = $(this).attr('data-line');
                var name = $(this).val();
                var com  = _this.commands[name].bindKey;
                if (typeof(com.win) != 'undefined') {
                    $('.command_win[data-line="'+line+'"]').val(com.win);
                } else {
                    if (!$.isPlainObject(com) && !$.isArray(com)) {
                        $('.command_win[data-line="'+line+'"]').val(com);
                    }
                }
                if (typeof(com.win) != 'undefined') {
                    $('.command_mac[data-line="'+line+'"]').val(com.mac);
                } else {
                    if (!$.isPlainObject(com) && !$.isArray(com)) {
                        $('.command_mac[data-line="'+line+'"]').val(com);
                    }
                }
                
            });
            $('.command_win').live("change", function(){
                _this.onCommandChange(this);
            });
            $('.command_mac').live("change", function(){
                _this.onCommandChange(this);
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Show dialog
        //
        //////////////////////////////////////////////////////////
        showDialog: function() {
            codiad.settings.show(this.path.replace(location.toString(), "") + '/dialog.php');
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Load current settings
        //
        //////////////////////////////////////////////////////////
        load: function() {
            var _this = this;
            $.getJSON(this.path+"controller.php?action=load", function(json){
                _this.settings    = json || {};
                _this.settings.keys = json.keys || {};
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Set keybindings of settings
        //
        //////////////////////////////////////////////////////////
        setKeys: function() {
            var _this = this;
            setTimeout(function(){
                if (codiad.editor.getActive() !== null) {
                    var manager = codiad.editor.getActive().commands;
                    //var command;
                    for (var i = 0; i < _this.settings.keys.length; i++) {
                        var element = _this.settings.keys[i];
                        if (typeof(manager.byName[element.name]) != 'undefined') {
                            manager.addCommand({
                                name: element.name,
                                bindKey: element.bindKey,
                                exec: manager.byName[element.name].exec
                            });
                        }
                    }
                }
            }, 50);
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Open file for expert settings
        //
        //////////////////////////////////////////////////////////
        edit: function() {
            var _this = this;
            //Open file and load currrent settings
            $.getJSON(this.path+"controller.php?action=open", function(json){
                if (json.status !== "error") {
                    var opt = {
                        "indent_size": codiad.editor.settings.tabSize,
                        "indent_char": " ",
                        "indent_with_tabs": !codiad.editor.settings.softTabs
                    };
                    _this.content   = json.content;
                    json.content    = js_beautify(json.content, opt);
                    _this.file      = json.name;
                    _this.open      = true;
                    codiad.modal.unload();
                    codiad.active.open(json.name, json.content, json.mtime, false, true);
                } else {
                    codiad.message.error(json.message);
                }
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Save expert settings
        //
        //////////////////////////////////////////////////////////
        saveExpert: function() {
            var _this = this;
            var content = codiad.editor.getContent();
            try {
                this.settings = JSON.parse(content);
            } catch (e) {
                codiad.message.error("Error: "+e);
                return false;
            }
            this.save();
            return true;
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Save dialog settings
        //
        //////////////////////////////////////////////////////////
        saveDialog: function() {
            var buf = [];
            var line;
            $('.command_line').each(function(i, item){
                var obj = {"name": "","bindKey": {"win": "","mac": ""}};
                line = $(item).attr("data-line");
                obj.name = $('.command_name[data-line="'+line+'"]').val();
                obj.bindKey.win = $('.command_win[data-line="'+line+'"]').val();
                obj.bindKey.mac = $('.command_mac[data-line="'+line+'"]').val();
                buf.push(obj);
            });
            if ($.isArray(this.settings)) {
                this.settings = {};
            }
            this.settings.keys = buf;
            this.save();
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Save current user settings
        //
        //////////////////////////////////////////////////////////
        save: function() {
            var _this   = this;
            var content = this.settings;
            content     = JSON.stringify(content);
            $.post(this.path+"controller.php?action=save", {"content": content}, function(data){
                var json = JSON.parse(data);
                if (json.status !== "error") {
                    $('li[data-path="'+_this.file+'"]').removeClass('changed');
                    _this.load();
                    _this.setKeys();
                    codiad.message.success(json.message);
                } else {
                    codiad.message.error(json.message);
                }
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Change save command for expert settings
        //
        //////////////////////////////////////////////////////////
        addCommands: function() {
            if (codiad.editor.getActive() === null) {
                return false;
            }
            var manager = codiad.editor.getActive().commands;
            try {
                manager.commands.Save.exec = this.$pSave;
                this.redo = false;
            } catch (e) {
                this.redo = true;
            }
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Restore save command after expert settings
        //
        //////////////////////////////////////////////////////////
        restoreCommands: function() {
            if (codiad.editor.getActive() === null) {
                return false;
            }
            var manager = codiad.editor.getActive().commands;
            try {
                manager.commands.Save.exec = this.$cSave;
            } catch(e) {}
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Display commands in dialog
        //
        //////////////////////////////////////////////////////////
        show: function() {
            var _this = this;
            $.each(this.settings.keys, function(i, item){
                _this.addEntry(item.name, item.bindKey.win, item.bindKey.mac);
            });
            $('#hotkey_div').css('max-height', function(){
                return 0.6*window.innerHeight + "px";
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Add new command
        //
        //  Parameter
        //
        //  name - {String} - Name of command
        //  win - {String} - Command for win
        //  mac - {String} - Command for mac
        //
        //////////////////////////////////////////////////////////
        addEntry: function(name, win, mac) {
            var coms = this.getCommands();
            if (coms === false) {
                return false;
            }
            var template = this.template;
            var option = "";
            for (var i = 0; i < coms.length; i++) {
                if (coms[i].name == name) {
                    option += "<option selected>"+coms[i].name+"</option>";
                } else {
                    option += "<option>"+coms[i].name+"</option>";
                }
            }
            template = template.replace("__options__", option);
            template = template.replace("__win__", win);
            template = template.replace("__mac__", mac);
            template = template.replace(new RegExp("__line__", "g"), this.entries++);
            $('#hotkey_list').append(template);
            this.setDelete();
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Add new empty command
        //
        //////////////////////////////////////////////////////////
        add: function() {
            this.addEntry("","","");
            $('.command_name:last').trigger('change');
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Activate delete button
        //
        //////////////////////////////////////////////////////////
        setDelete: function(){
            $('.command_remove').click(function(){
                var line = $(this).attr("data-line");
                $('.command_line[data-line="'+line+'"]').remove();
                return false;
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Get current commands or a fallback
        //
        //////////////////////////////////////////////////////////
        getCommands: function() {
            var commands;
            if (codiad.editor.getActive() === null) {
                if (this.commands === null) {
                    codiad.message.error("Open file to display all commands!");
                    return false;    
                } else {
                    commands = this.commands;
                }
            } else {
                commands = codiad.editor.getActive().commands.byName;
            }
            
            var buf = [];
            $.each(commands, function(i, item){
                buf.push(item);
            });
            buf.sort(function(a,b){
                var newBuf = [a.name,b.name];
                if (a === b) {
                    return 0;
                }
                newBuf.sort();
                if (newBuf[0] === a.name) {
                    return -1;
                } else {
                    return 1;
                }
            });
            return buf;
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Display help command
        //
        //////////////////////////////////////////////////////////
        help: function() {
            codiad.modal.load(400, this.path+"dialog.php?action=help");
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Check if command is already used
        //
        //  Parameter
        //
        //  field - {jQuery objcet} - Field to check
        //
        //////////////////////////////////////////////////////////
        onCommandChange: function(field) {
            var type = 'win';
            if ($(field).hasClass('.command_mac')) {
                type = 'mac';
            }
            var value = $(field).val();
            if (this.commands === null) {
                return false;
            }
            var com;
            $.each(this.commands, function(i, item){
                if (typeof(item.bindKey) == 'undefined') {
					return;
                }
                if (typeof(item.bindKey[type]) == 'undefined') {
                    com = item.bindKey;
                } else {
                    com = item.bindKey[type];
                }
                if (value === com) {
                    codiad.message.notice("Command already used!");
                    return false;
                }
            });
        }
    };
})(this, jQuery);