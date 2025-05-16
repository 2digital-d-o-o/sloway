<?php
	use Sloway\admin;
	use Sloway\acontrol;

//    $lang = Admin::LangSelect(true, false, true);

	echo admin::AjaxForm_Begin('AdminSettings/Ajax_ProfileHandler');
    
	echo admin::SectionBegin(et("User"));
	echo admin::Field(et('Username'), acontrol::edit('username', $username));
        
	echo admin::Field(et('New password'), acontrol::edit('npassword', null, array('password' => true)));
	echo admin::Field(et('Confirm password'), acontrol::edit('cpassword', null, array('password' => true)));
	echo admin::SectionEnd();
	
	echo admin::AjaxForm_End(array('save' => t('Save'), '!cancel' => t('Cancel')));
 ?>
