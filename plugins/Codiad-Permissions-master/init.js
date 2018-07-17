/*
* Copyright (c) Codiad & Andr3as, distributed
* as-is and without warranty under the MIT License.
* See [root]/license.md for more information. This information must remain intact.
*/

(function(global, $){
    
    var codiad = global.codiad,
        scripts = document.getElementsByTagName('script'),
        path = scripts[scripts.length-1].src.split('?')[0],
        curpath = path.split('/').slice(0, -1).join('/')+'/';

    $(function() {
        codiad.Permissions.init();
    });

    codiad.Permissions = {
        
        path    : curpath,
        file    : "",
        
        init: function() {
			var _this = this;
			$('.more_control').live('click', function(e){
				$('.more').toggle(0, function(){
					$('.more_control span').removeClass('icon-right-open-big');
					$('.more_control span').removeClass('icon-down-open-big');
					if ($('.more').is(':visible')) {
						$('.more_control span').addClass('icon-down-open-big');
					} else {
						$('.more_control span').addClass('icon-right-open-big');
					}
				});
			});
			$('.more input:checkbox').live('change', function(e){
				_this.calculateRights();
			});
			$('#permissions').live('input keyup', function(e){
				_this.calculateBoxes($('#permissions').val());
			});
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Show dialog to modify permissions
		//
		//  Parameters:
		//
		//  path - {String} - File path
		//
		//////////////////////////////////////////////////////////
        showDialog: function(path) {
            this.file = path;
            codiad.modal.load(100, this.path+"dialog.php?path="+path);
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Change permissions
        //
		//////////////////////////////////////////////////////////
        change: function() {
            var perm = $('#permissions').val();
            codiad.modal.unload();
            $.getJSON(this.path+"controller.php?action=changePermission&path="+this.file+"&mode="+perm, function(data){
                if (data.status == "error") {
                    codiad.message.error(data.message);
                } else {
                    codiad.message.success(data.message);
                }
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Calculate rights
        //
		//////////////////////////////////////////////////////////
        calculateRights: function() {
			var sum = 0, _this = this;
			$('.more input:checkbox').each(function(i, item){
				var factor = 1, right = 1;
				if (_this.isChecked(item)) {
					if ($(item).hasClass('owner')) {
						factor = 100;
					} else if ($(item).hasClass('group')) {
						factor = 10;
					}
					
					if ($(item).hasClass('r')) {
						right = 4;
					} else if($(item).hasClass('w')) {
						right = 2;
					}
					sum += factor * right;
				}
			});
			var obj = $('.more input:checkbox');
			if (!this.isChecked(obj[0]) && !this.isChecked(obj[1]) && !this.isChecked(obj[2])) {
				sum = '0' + sum;
				if (!this.isChecked(obj[3]) && !this.isChecked(obj[4]) && !this.isChecked(obj[5])) {
					sum = '0' + sum;
				}
			}
			$('#permissions').val(sum);
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Calculate checkboxes
        //
        //  Parameter:
        //
        //  rights - {string} - File rights
        //
		//////////////////////////////////////////////////////////
        calculateBoxes: function(rights) {
            $('.more input:checkbox').removeAttr('checked');
            var fn = function(right, selector) {
				if (right == '1' || right == '3' || right == '5' || right == '7') {
					this.selectCheckbox(selector + ' .x');
				}
				if (right == '2' || right == '3' || right == '6' || right == '7') {
					this.selectCheckbox(selector + ' .w');
				}
				if (right == '4' || right == '5' || right == '6' || right == '7') {
					this.selectCheckbox(selector + ' .r');
				}
            }.bind(this);
            if (rights[0] === '0' && rights.length > 3) {
				rights = rights.substring(1);
            }
            if (rights.length < 1) {
				rights = '000';
            } else if (rights.length < 2) {
				rights += '00';
            } else {
				rights += '0';
            }
            fn(rights[0], ".owner");
            fn(rights[1], ".group");
            fn(rights[2], ".other");
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Check if checkbox is checked
        //
        //  Parameter:
        //
        //  item - {object} - jQuery object
        //
		//////////////////////////////////////////////////////////
        isChecked: function(item) {
			if ($(item).attr('checked') == "checked") {
				return true;
            } else {
				return false;
            }
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Select checkbox
        //
        //  Parameter:
        //
        //  selector - {string} - Selector of checkbox(s)
        //
		//////////////////////////////////////////////////////////
        selectCheckbox: function(selector) {
			$('.more ' + selector).attr('checked', 'checked');
        }
        
    };
})(this, jQuery);