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
            codiad.autoupdate.init();
        });

    codiad.autoupdate = {

        controller: curpath + 'controller.php',
        dialog: curpath + 'dialog.php',

        //////////////////////////////////////////////////////////////////
        // Initilization
        //////////////////////////////////////////////////////////////////

        init: function () {
            var _this = this;
            $.get(_this.controller + '?action=init');
            $('#sb-right a[onclick="codiad.update.check();"]').attr("onclick", "codiad.autoupdate.check();");
        },
        
        //////////////////////////////////////////////////////////////////
        // Update Check
        //////////////////////////////////////////////////////////////////

        check: function (type) {
            var _this = this;
            $('#modal-content form')
                .die('submit'); // Prevent form bubbling
                codiad.modal.load(500, this.dialog + '?action=check&type='+type);
                $('#modal-content').html('<div id="modal-loading"></div><div align="center">Checking...</div><br>');
        }, 
        
        //////////////////////////////////////////////////////////////////
        // Update System
        //////////////////////////////////////////////////////////////////

        update: function () {
            var _this = this;
            var remoteversion = $('#modal-content form input[name="remoteversion"]')
                        .val();
            var remotename = $('#modal-content form input[name="remotename"]')
                        .val();            
            codiad.modal.load(350, this.dialog + '?action=update&remoteversion=' + remoteversion + '&remotename=' + remotename);            
            $('#modal-content form')
                    .live('submit', function (e) {
                    e.preventDefault();
                    var remoteversion = $('#modal-content form input[name="remoteversion"]')
                        .val();
                        $('#modal-content').html('<div id="modal-loading"></div><div align="center">Downloading & Installing...</div><br>');
                        $.get(_this.controller + '?action=download&remoteversion=' + remoteversion, function(data) {
                            var response = codiad.jsend.parse(data);
                            codiad.modal.unload();
                            if (response != 'error') {
                                window.open('./' + remoteversion + '.php','_self');
                            } else {
                                codiad.message.error('Update failed');
                            }
                        });
                });
        },
        
        //////////////////////////////////////////////////////////////////
        // Download Archive
        //////////////////////////////////////////////////////////////////

        download: function () {
            var _this = this;
            var archive = $('#modal-content form input[name="archive"]')
                        .val();
            $('#download')
                .attr('src', archive);            
            $.get(_this.controller + '?action=clear');             
            codiad.modal.unload();    
        }

    };

})(this, jQuery);