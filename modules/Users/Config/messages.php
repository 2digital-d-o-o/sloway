<?php 

$config["users"] = array(
    "icon" => \Sloway\path::gen("site.modules.Users", "media/img/icon_users.png"), 
    "variations" => array(
        "auth_mail", 
        "registered", 
        "cpassword",
        "fusername"
    )
);
	
$config["variables"] = array("title", "email", "email_link");
	


