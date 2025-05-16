<?php
	namespace Sloway;
?>

<script>
$(document).ready(function() {
    $("#cart").ac_create();
    
    $("#cart [name=reg_mode]").change(function() {
        $("[data-mask]").hide();    
        $("[data-mask*=" + $(this).val() + "]").show();
        
        $("#cart .ac_invalid").removeClass("ac_invalid");
    });

    $("#cart [data-mask]").hide();    
    $("#cart [data-mask*=<?=$order->reg_mode?>]").show();
});
</script>

<h1><?php echo et("cart_user_header") ?></h1>
<?php if ($order->user): ?>
<br>
<div style="text-align: center"><?=et("Logged as") . ": " . $order->username()?></div>
<input type="hidden" name="reg_mode" value="user">
<?php else: ?>

<table class="cart_form">
<tr>
    <td></td>
    <td>
        <ul class="ac_checklist" data-name="reg_mode" data-mode="radio" data-value="<?=$order->reg_mode?>">
            <li data-value="skip"><?=et("Skip registration")?></li>
            <li data-value="reg"><?=et("Register as new user")?></li>
            <li data-value="login"><?=et("Log in")?></li>
        </ul>
    </td>
</tr>
<tr data-mask="skip,reg">
    <td><span class="cart_required">*</span><?=et("E-mail")?></td>
    <td><input class="ac_edit" name="email" type="text" value="<?=$order->email?>" <?php if (v($order->err_fields, "email", false)) echo "data-invalid='true'"?>></td>       
</tr>
<tr data-mask="reg,login">
    <td><span class="cart_required">*</span><?=et("Username")?></td>
    <td><input class="ac_edit" name="username" type="text" value="<?=$order->reg_username?>" <?php if (v($order->err_fields, "username", false)) echo "data-invalid='true'"?>></td>       
</tr>
<tr data-mask="reg,login">
    <td><span class="cart_required">*</span><?=et("Password")?></td>
    <td>
        <input class="ac_edit" name="password" type="password" value="<?=$order->reg_password?>" <?php if (v($order->err_fields, "password", false)) echo "data-invalid='true'"?>>
        <div style="font-size: 12px; text-align: right" data-mask="login">
            <a href="<?=url::site("User/FPassword")?>"><?=et("Forgotten password")?></a><br>    
            <a href="<?=url::site("User/FUsername")?>"><?=et("Forgotten username")?></a>
        </div>    
    </td>       
</tr>
<tr data-mask="reg">
    <td><span class="cart_required">*</span><?=et("Confirm password")?></td>
    <td><input class="ac_edit" name="cpassword" type="password" value="<?=$order->reg_password?>" <?php if (v($order->err_fields, "cpassword", false)) echo "data-invalid='true'"?>></td>       
</tr>
</table>

<?php endif ?>