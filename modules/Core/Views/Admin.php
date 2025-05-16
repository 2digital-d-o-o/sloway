<?php 
	use Sloway\core;
	use Sloway\path;
	use Sloway\url;
	use Sloway\admin;
	use Sloway\acontrol;

    core::document();

    header('X-UA-Compatible: IE=edge,chrome=1'); 
?>
<!DOCTYPE html> 
<html>
<head>
    <title></title>
    <base href="<?php echo url::base() ?>">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Expires" content="Tue, 21 Sep 2016 12:00:00">
    <meta http-equiv="Pragma" content="no-cache">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex" />

	<?php echo core::head(); ?>  

<script>
	$(document).ready(function() {   
        $(document).ac_create(function() {
            $("#header_lang [name=admin_lang]").change(function() {
                $.overlay_loader();
                $.core.language($(this).val());  
            });
        });
        $("#header_menu_button").click(function() {
            if ($("#header_dropdown").is(":visible"))
                 $("#header_dropdown").slideUp(function() { $(this).removeAttr("style") }); else
                 $("#header_dropdown").slideDown(); 
        });
		
		$(window).bind("scroll resize", function() {
			var menu = $("#module_menu");
			if (!menu.length) return;
			
			var ofs = menu.offset();
			var y = ofs.top - $(window).scrollTop();
			var h = $(window).height();
			
			menu.css("max-height", h-y-10 + "px");
		});
        
        $.admin.layout();
        $(window).resize(function() {
            clearTimeout($.admin.resize_timeout);
            $.admin.resize_timeout = setTimeout(function() { $.admin.layout() }, 100);
        });
        
        $("body").initialState();
        $("body").bind("stateChanged", function(e, url, param) {
            $.admin.reload_module(url, false);
        });
	});
</script>

</head>
<?php core::body() ?>

<body id="admin_body" class="admin_skin_blue admin">
<div id="wrapper">
    <div id="header">        
	    <a id="header_logo" href="<?=url::site() ?>" target="_blank">
            <img src="<?=$logo?>">
        </a>
		<div id="header_user">
			<?=et("Logged in as")?>: <a href="<?=url::site("AdminSettings/Profile")?>" onclick="return admin_redirect(this)"><?php echo $admin_username ?></a>&nbsp;|&nbsp;
			<a href="<?=url::site("AdminLogin/Logout")?>"><?=et("Logout")?></a>
		</div>

        <ul id="header_menu">
			<?php foreach ($modules as $name => $ops): ?>
			<li>
				<a href="<?=$ops["link"]?>" onclick="return admin_redirect(this)">
					<?=$ops["title"]?>
				</a>
			</li>
			<?php endforeach ?>
		</ul>
        <div id="header_right">
			<div id="header_menu_button"></div>
		</div>
		<div id="header_dropdown">
		<ul>
			<?php foreach ($modules as $name => $ops): ?>
			<li>
				<a href="<?=$ops["link"]?>" onclick="return admin_redirect(this)">
					<?=$ops["title"]?>
				</a>
			</li>
			<?php endforeach ?>
		</ul>	
		</div>
    </div>
    <div id="header_spacer"></div>

    <div id="main">
        <div id="main_content">
		<?php echo $content ?>
	    </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var module_content = $("#module_content");
    
    if (!module_content.hasClass("loaded")) {
        module_content.addClass("loaded"); 
        $(document).trigger("module_loaded");
    }
});
</script>

<?php core::end() ?>
<?php /*
<div id="sloway_ftr">
    <a href="https://sloway.si" target="_blank"><img id="header_sloway" src="<?=path::gen('site.modules.Core','media/img/sloway-logo.png')?>"></a>
</div>
 * 
 */ ?>
</body>
</html>

