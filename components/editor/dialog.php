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

switch($_GET['action']){

    //////////////////////////////////////////////////////////////////
    // Find & Replace
    //////////////////////////////////////////////////////////////////
    
    case 'search':
    $type = $_GET['type'];
    ?>
    <label><?php i18n("Find:"); ?></label>
    <textarea name="find" autofocus="autofocus" autocomplete="off"></textarea>
    
    <?php if($type=='replace'){ ?>

    <label><?php i18n("Replace:"); ?></label>
    <textarea name="replace"></textarea>
    
    <?php } ?>

    <button class="btn-left" onclick="codiad.editor.search('find');return false;"><?php i18n("Find"); ?></button>
    <?php if($type=='replace'){ ?>
        <button class="btn-mid" onclick="codiad.editor.search('replace');return false;"><?php i18n("Replace"); ?></button>
        <button class="btn-mid" onclick="codiad.editor.search('replaceAll');return false;"><?php i18n("Replace ALL"); ?></button>
    <?php } ?>
    <button class="btn-right" onclick="codiad.modal.unload(); return false;"><?php i18n("Cancel"); ?></button>
    <?php
    break;
}

?>
</form>
<script>
$(function(){
    <?php if($_GET['action']=='search'){ ?>
    $('textarea[name="find"]').val(codiad.active.getSelectedText());
    $('textarea[name="find"]').focus();
    <?php } ?>
});

</script>
