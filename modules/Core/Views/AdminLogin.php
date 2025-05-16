<?php 
	namespace Sloway;

	header('X-UA-Compatible: IE=edge,chrome=1'); 
?>
<!DOCTYPE html> 
<html>
<head>
    <title></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<?php echo core::head($this) ?>
				
</head>
<script>
$(document).ready(function() {
    $("#login_username, #login_password").ac_edit(); 
    $("#login_lang").ac_select();
    $("#login_remember").ac_checkbox();
	$("[name=admin_lang]").change(function() {
		$.core.language($(this).val());  
	});
});
</script>
<body>    

<div id="header">            
	<a id="header_logo" href="<?=url::site("Admin") ?>" onclick="return admin_redirect(this)">
		<img src="<?=$logo?>">
	</a>
</div>

<div id="login" class="<?php if ($image) echo "with_image"?>">   
    <div id="login_main"> 
		<h2><?=t("Login")?></h2>
        <?php if ($message): ?>
        <div class="admin_message failure"><?=$message?></div>
        <?php endif ?>    
        <form method="post" action="<?=url::site("AdminLogin")?>">
			<label><?=t("Username")?></label>
            <input id="login_username" type="text" name="username"><br>
			<label><?=t("Password")?></label>
            <input id="login_password" type="password" name="password">
			<label for="login_remember"><?=et("Remember me")?></label>
			<input id="login_remember" name="remember">    
            <input class="admin_button" id="login_submit" name="login" type="submit" value="<?=t("Login")?>">
		</form>
	</div>
	<div id="login_image">
		<?php if ($image) echo "<img src='$image'>"; ?>
	</div>
	<div class="clear"></div>
</body>
</html>

