<?php
	namespace Sloway;
	use \Sloway\url;
	use \Sloway\lang;
	use \Sloway\core;
	use \Sloway\path;
?>


var doc_base = "<?=url::site()?>";
var project_name = "<?=core::$project_name?>";
var language = "<?=lang::$lang?>";
var lang_profile = "<?=lang::$profile_name?>";



$.rl.images_url = '<?=$img_srv?>';


<?php if ($admin_logged): ?>
$(document).keydown(function(e) {
    if (e.keyCode == 84 && e.shiftKey && e.altKey) {
        $.core.translate('<?=core::$profile?>', doc_base + "Admin/Ajax_Translate");
        e.preventDefault();
        e.stopPropagation();
        return false;
    }
});
<?php endif ?>

<?php if (core::$profile == "admin"): ?>
var admin_module_path = '<?=path::gen("site.modules.Core", false)?>';
var admin_site_domain = '<?=url::base()?>';
var admin_upload_path = '/<?=path::gen("uploads")?>';
var admin_uploads_url = '<?=str_replace(url::base(), "", path::gen("site.uploads"))?>';

var trans_admin_rename = '<?=t('Rename')?>';
var trans_admin_delete = '<?=t('Delete')?>';
var trans_admin_remove = '<?=t('Remove')?>';
var trans_admin_delete_c = '<?=t('Delete.confirm')?>';
var trans_admin_edit = '<?=t('Edit')?>';
var trans_admin_add = '<?=t('Add')?>';
var trans_admin_save = '<?=t('Save')?>';
var trans_admin_cancel = '<?=t('Cancel')?>';
var trans_admin_view = '<?=t('View')?>';
var trans_admin_preview = '<?=t('Preview')?>';
var trans_admin_save_close = '<?=t('Save and close')?>';
var trans_admin_confirm = '<?=t('admin_confirm_prefix')?>';
var trans_admin_template = '<?=t('Template')?>';
var trans_admin_template_add = '<?=t('Add template')?>';
var trans_admin_url = '<?=t('URL')?>';
var trans_admin_clear = '<?=t('Clear')?>';
var trans_admin_options = '<?=t("Options")?>';
var trans_admin_properties = '<?=t('Properties')?>';
var trans_admin_container = '<?=t('Container')?>';
var trans_admin_close = '<?=t('Close')?>';
var trans_admin_confirm_revert = '<?=t("admin_confirm_revert")?>';
var trans_admin_elapsed = '<?=t("Running time")?>';
var trans_admin_remaining = '<?=t("Remaining")?>';
var trans_admin_copy_to_all = '<?=t("Copy to all")?>';
var trans_admin_copy_to_all_empty = '<?=t("Copy to all empty")?>';
var trans_admin_copy_to = '<?=t("Copy to")?>';

<?php /* foreach (config::get("content.classes", array()) as $media => $classes): ?>
$.admin.editor.classes['<?=$media?>'] = {};
<?php foreach ($classes as $class): ?>
$.admin.editor.classes['<?=$media?>']['<?=$class?>'] = '<?=t($class)?>';
<?php endforeach ?>
<?php endforeach  */ ?>

$.datagrid.def_options.cookie_prefix = '<?=core::$project_name?>_';
$.datagrid.def_options.trans["of"] = '<?=t("of")?>'; 
$.datagrid.def_options.trans["Rows per page"] = '<?=t("Rows per page")?>';
$.datagrid.def_options.trans["Viewing"] = '<?=t("Viewing")?>';

$.rleditor.options.base_url = "<?=url::base()?>";
$.rleditor.options.trans["Edit"] = '<?=t("Edit")?>';
$.rleditor.options.trans["Remove"] = '<?=t("Remove")?>';
$.rleditor.options.trans["Template"] = '<?=t("Template")?>';
$.rleditor.options.trans["Add template"] = '<?=t("Add template")?>';
$.rleditor.options.trans["Clear"] = '<?=t("Clear")?>';
$.rleditor.options.trans["Container"] = '<?=t("Container")?>';
$.rleditor.options.trans["Properties"] = '<?=t("Properties")?>';

$.browser.def_options.trans["New folder"] = '<?=t("New folder")?>';
$.browser.def_options.trans["Rename"] = '<?=t("Rename")?>';
$.browser.def_options.trans["Delete"] = '<?=t("Delete")?>';
$.browser.def_options.trans["Paste"] = '<?=t("Paste")?>';
$.browser.def_options.trans["View"] = '<?=t("View")?>';
$.browser.def_options.trans["Copy"] = '<?=t("Copy")?>';
$.browser.def_options.trans["Cut"] = '<?=t("Cut")?>';
$.browser.def_options.trans["Cancel upload"] = '<?=t("Cancel upload")?>';
$.browser.def_options.trans["Copy to"] = '<?=t("Copy to")?>';
$.browser.def_options.trans["Move to"] = '<?=t("Move to")?>';
$.browser.def_options.trans["Upload"] = '<?=t("Upload")?>';

$.ov.default_options.maximize = "vert";
$.ov.default_options.pos_x = "right";
$.ov.default_options.resize = "left";
$.ov.default_options["loader"] = '<?=path::gen("site.modules.Core", "media/img/loader.gif")?>';
$.ov.default_options["elem_loader"] = '<?=path::gen("site.modules.Core", "media/img/throbber.gif")?>';

$.ov.default_buttons.close.title = '<?=t("Close")?>';
$.ov.default_buttons.cancel.title = '<?=t("Cancel")?>';

$.timepicker._defaults.firstDay = 1;

<?php /*if (lang::$lang != "en"): ?>
$.timepicker._defaults.monthNames = ['<?=t("January")?>','<?=t("February")?>','<?=t("March")?>','<?=t("April")?>','<?=t("May")?>','<?=t("June")?>','<?=t("July")?>','<?=t("August")?>','<?=t("September")?>','<?=t("October")?>','<?=t("November")?>','<?=t("December")?>'];
$.timepicker._defaults.dayNamesMin = ['<?=t("Su")?>', '<?=et("Mo")?>', '<?=t("Tu")?>', '<?=t("We")?>', '<?=t("Th")?>', '<?=t("Fr")?>', '<?=t("Sa")?>'];
$.timepicker._defaults.hourText = '<?=t("Hour")?>';
$.timepicker._defaults.timeText = '<?=t("Time")?>';
$.timepicker._defaults.minuteText =  '<?=t("Minute")?>';
$.timepicker._defaults.currentText = '<?=t("Now")?>';
$.timepicker._defaults.closeText = '<?=t("Close")?>';
<?php endif */ ?>

<?php
    $list_site = array();
    $list_mail = array();
?>

<?php foreach ($templates as $name => $ops): ?>
<?php
    if ($ops["platform"] == "*" || strpos($ops["platform"], "site") !== false) $list_site[]= $name;
    if ($ops["platform"] == "*" || strpos($ops["platform"], "mail") !== false) $list_mail[]= $name;
?>
if (!$.rleditor.options.templates['<?=$name?>'])
    $.rleditor.options.templates['<?=$name?>'] = {};
    
$.extend($.rleditor.options.templates['<?=$name?>'], {
    html: <?=json_encode($ops["html"])?>,
    html_mail: <?=json_encode($ops["html_mail"])?>,
    title: '<?=$ops["title"]?>',
    auto_edit: '<?=$ops["auto_edit"]?>',
    root: <?=$ops["root"] ? "true" : "false"?>,
    sub_frags: '<?=$ops["add"]?>',
});
<?php endforeach ?>
$.rleditor.options.template_list = <?=json_encode($list_site)?>;
$.rleditor.options.template_list_site = <?=json_encode($list_site)?>;
$.rleditor.options.template_list_mail = <?=json_encode($list_mail)?>;

<?php endif ?>


