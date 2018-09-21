/*
 *  Copyright (c) Codiad & daeks, distributed
 *  as-is and without warranty under the MIT License. See
 *  [root]/license.txt for more. This information must remain intact.
 */ 
 
 (function (global, $) {

    var codiad = global.codiad,
        scripts= document.getElementsByTagName('script'),
        path = scripts[scripts.length-1].src.split('?')[0],
        curpath = path.split('/').slice(0, -1).join('/')+'/';

    $(window)
        .load(function() {
            codiad.macro.init();
        });

    codiad.macro = {

        controller: curpath + 'controller.php',
        dialog: curpath + 'dialog.php',

        //////////////////////////////////////////////////////////////////
        // Initilization
        //////////////////////////////////////////////////////////////////

        init: function () {
            var _this = this;
            $.get(_this.controller + '?action=init', function(data) {
                var response = jQuery.parseJSON(data);
                var buffer = [];
                jQuery.each(response, function(i, val) {
                  if(val['t'] == 'context-menu') {
                    buffer.push(val['a']);
                  }
                });
                if (buffer.indexOf('both')) {
                  $('#context-menu').append('<hr class="both">');
                } else {
                  if (buffer.indexOf('root-only') !== -1) {
                    $('#context-menu').append('<hr class="root-only">');
                  }
                  if (buffer.indexOf('directory-only') !== -1) {
                    $('#context-menu').append('<hr class="directory-only">');
                  }
                  if (buffer.indexOf('file-only') !== -1) {
                    $('#context-menu').append('<hr class="file-only">');
                  }
                }
                jQuery.each(response, function(i, val) {
                  if(val['t'] == 'context-menu') {
                    var macro = '<a class="'+val['a']+'" onclick="codiad.macro.execute(\''+i+'\','+val['d']+', $(\'#context-menu\').attr(\'data-path\'));"><span class="icon-'+val['i']+'"></span>'+val['n']+'</a>';
                    $('#'+val['t']).append(macro);
                  }
                  if(val['t'] == 'sb-right-content') {
                    var macro = '<a onclick="codiad.macro.execute(\''+i+'\','+val['d']+', codiad.project.getCurrent());"><span class="icon-'+val['i']+'"></span>'+val['n']+'</a>';
                    $('.'+val['t']).prepend(macro);                    
                  }
                });
            });
        },
        
        //////////////////////////////////////////////////////////////////
        // Config
        //////////////////////////////////////////////////////////////////

        config: function () {
            var _this = this;
            $('#modal-content form')
                .die('submit'); // Prevent form bubbling
                codiad.modal.load(850, this.dialog + '?action=config');
        },
        
        //////////////////////////////////////////////////////////////////
        // Add
        //////////////////////////////////////////////////////////////////

        add: function () {
            var rowid = parseInt($('#macrocount').val())+1;
            var newcommand = '<tr id="l'+rowid+'"><td width="100px"><input id="rowid" type="hidden" value="'+rowid+'"><select id="t'+rowid+'"><option value="context-menu">Menu</option><option value="sb-right-content">Bar</option></select></td><td width="150px"><input class="macro-command" id="n'+rowid+'" type="text" value=""></td><td width="100px"><input class="macro-command" id="i'+rowid+'" type="hidden" value="keyboard"><select id="a'+rowid+'"><option value="root-only">Root</option><option value="file-only">File</option><option value="directory-only">Folder</option><option value="both" selected>All</option></select></td><td width="400px"><input class="macro-command" id="c'+rowid+'" type="text" value=""></td><td width="100px"><select id="d'+rowid+'"><option value="false">No</option><option value="true">Yes</option></select></td><td width="50px"><button class="btn-left" onclick="codiad.macro.remove(\''+rowid+'\',);return false;">X</button></td></tr>';
            $('#macrolist').append(newcommand);
            $('.macro-wrapper').scrollTop(1000000);
            $('#macrocount').val(rowid);
        },
        
        //////////////////////////////////////////////////////////////////
        // Del
        //////////////////////////////////////////////////////////////////

        remove: function (id) {
            $('#l' + id).remove();
        },
        
        //////////////////////////////////////////////////////////////////
        // Save
        //////////////////////////////////////////////////////////////////

        save: function () {
            var _this = this;
            var formData = {'n[]' : [], 'd[]' : [], 'a[]' : [], 't[]' : [], 'i[]' : [], 'c[]' : []};
            
            $("#macrolist tr").each(function(i, tr) {
                $this = $(this)
                var rowid = $this.find("input#rowid").val();
                formData['n[]'].push($this.find("input#n"+rowid).val());
                formData['d[]'].push($this.find("select#d"+rowid).val());
                formData['a[]'].push($this.find("select#a"+rowid).val());
                formData['i[]'].push($this.find("input#i"+rowid).val());
                formData['t[]'].push($this.find("select#t"+rowid).val());
                formData['c[]'].push($this.find("input#c"+rowid).val());
            });
            
            $.get(this.controller+'?action=save', formData, function(data){
                var response = codiad.jsend.parse(data);
                if (response != 'error') {
                    window.location.reload();
                } else {
                    codiad.message.error('Save failed');
                }
            });
        },
        
        //////////////////////////////////////////////////////////////////
        // Save
        //////////////////////////////////////////////////////////////////

        execute: function (id, daemon, path) {
            var _this = this;
            if(daemon) {
              $.get(_this.controller + '?action=execute&id=' + id + '&path=' + path, function(data){
                  var response = codiad.jsend.parse(data);
                  if (response != 'error') {
                      codiad.message.success('Macro executed');
                  } else {
                      codiad.message.error('Save failed');
                  }
              });
            } else {
              $('#modal-content form')
                  .die('submit'); // Prevent form bubbling
                  codiad.modal.load(850, this.dialog + '?action=execute&id=' + id + '&path=' + path);
            }
        }

    };

})(this, jQuery);