<?php

/*
*  Copyright (c) Codiad & Kent Safranski (codiad.com), distributed
*  as-is and without warranty under the MIT License. See 
*  [root]/license.txt for more. This information must remain intact.
*/

require_once('../../common.php');

//////////////////////////////////////////////////////////////////
// Verify Session or Key
//////////////////////////////////////////////////////////////////

checkSession();

?>
<form onsubmit="return false;">
	<?php
	switch( $_GET['action'] ) {
		
		case 'line':
			
			?>
			<label><?php i18n("Goto Line:"); ?></label>
			<input name="goto_line" autofocus="autofocus" autocomplete="off">
			<button class="btn-left" onclick="codiad.editor.goto_line();return false;"><?php i18n("Goto"); ?></button>
			<button class="btn-right" onclick="codiad.modal.unload(); return false;"><?php i18n("Cancel"); ?></button>
			<?php
		break;	
		break;
		
		case 'search':
			
			//////////////////////////////////////////////////////////////////
			// Find & Replace
			//////////////////////////////////////////////////////////////////
			
			
			$type = $_GET['type'];
			?>
			<label><?php i18n("Find:"); ?></label>
			<input name="find" autofocus="autofocus" autocomplete="off">
			<textarea style="display: none;" name="find" autofocus="autofocus" autocomplete="off"></textarea>
			<?php
			if( $type == 'replace' ) {
				
				?>
				<label><?php i18n("Replace:");?></label>
				<input name="replace">
				<textarea style="display: none;" name="replace"></textarea>
				<?php
			}
			?>
			<button class="btn-left" onclick="codiad.editor.search('find');return false;"><?php i18n("Find"); ?></button>
			<button class="btn-mid" onclick="codiad.editor.toggleMultiLine( this );return false;"><?php i18n("Multi Line"); ?></button>
			<?php
			if( $type == 'replace' ) {
				
				?>
				<button class="btn-mid" onclick="codiad.editor.search('replace');return false;"><?php i18n("Replace"); ?></button>
				<button class="btn-mid" onclick="codiad.editor.search('replaceAll');return false;"><?php i18n("Replace ALL"); ?></button>
				<?php
			}
			?>
			<button class="btn-right" onclick="codiad.modal.unload(); return false;"><?php i18n("Cancel"); ?></button>
			<?php
		break;
		
		case 'sort':
			
			?>
			<label><?php i18n("Sort:"); ?></label>
			<textarea style="" name="sort" autofocus="autofocus" autocomplete="off"></textarea>
			<div>
				<label><input name="case_sensitive" style="display:inline-block;vertical-align: bottom;" type="checkbox"> Case Sensitive</label>
			</div>
			<div style="display: none;" >
				<label>Delimiter:</label>
				<input type="text" name="delimiter" value="">
			</div>
			<button class="btn-left" onclick="codiad.editor.sort( '<?php echo addcslashes( PHP_EOL, PHP_EOL );?>' );return false;"><?php i18n("Sort"); ?></button>
			<button class="btn-right" onclick="codiad.modal.unload();return false;"><?php i18n("Cancel"); ?></button>
			<?php
		break;
	}
	?>
</form>
<script>
	$( function() {
		<?php
		if( $_GET['action'] == 'search' ) {
			?>
			if( codiad.editor.multi_line ) {
				
				$('textarea[name="find"]').val( codiad.active.getSelectedText() );
				$('textarea[name="find"]').focus();
			} else {
				
				$('input[name="find"]').val( codiad.active.getSelectedText() );
				$('input[name="find"]').focus();
			}
			<?php
		} elseif( $_GET['action'] == 'sort' ) {
			
			?>
			$('textarea[name="sort"]').val( codiad.active.getSelectedText() );
			$('textarea[name="sort"]').focus();
			<?php
		}
		?>
	});
</script>
