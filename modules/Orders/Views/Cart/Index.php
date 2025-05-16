<?php 
	namespace Sloway;

	if (!isset($locked)) $locked = false; 

?>
<?= facebook_api::generateClientEventScript("PageView", "Checkout", 0); ?>
<?php if($step == "User"): ?>
<?= facebook_api::generateClientEventScript("InitiateCheckout", "Checkout", 0); ?>
<?php elseif($step == "Review"): ?>
<?= facebook_api::generateClientEventScript("AddPaymentInfo", "Checkout", 0); ?>
<?php endif; ?>

<script>

var url_base = "<?=url::site("Cart")?>";
function scroll_to_section() {
    var target = $("#cart .cart_step.cart_section.curr");
    if (!target.is(":visible"))
        target = $("#cart");
    
    target.scrollToElem();
}
function cart_reload() {
	$("#cart_header .cart_step.curr").click();
}

$(document).ready(function() {
	<?php if (!$ajax): ?>
	$("#cart").initialState();
	<?php endif ?>
	
	$("#cart").bind("stateChanged", function(e, url, data) {
		if (typeof data.step != "undefined")
			next = data.step; else
			next = "Cart";
				
		$("#cart_form [name=next_step]").val(next);
		data = $("#cart_form").serialize();
		href = $("#cart_form").attr("action");
		
        $.overlay_loader();
		$.post(href, data, function(r) {
			if (r.redirect) {
				window.location.href = r.redirect;
			} else {
                $.overlay_close();
			    if (r.content) {
				    $("#cart").replaceWith(r.content);
                    
                   // scroll_to_section();
                    
				    step = $("#cart").attr("step");    
				    url = url_base + "/" + step;
				    
				    $("#cart").replaceState(url, url, {"step" : step});
                    $(document).trigger("cart_scroll", [step]);
			    }
            }
		}, "json").fail(function() {
            $("#cart_main").prepend("<div class='cart_message cart_message_error'>Error occured while processing your cart</div>");
            $.overlay_close();    
        });
	});
	
	$("#cart_form").bind("keypress", function(e) {
		if (e.keyCode == 13)
			return false;
	});
	
	<?php if (!$locked): ?>
	$(".cart_step").click(function() {
        var on_submit = $("#cart_form").data("on_submit");
        if (typeof on_submit == "function") {
            var res = on_submit.apply($("#cart_form"), [$(this).hasClass("next")]);
            if (res === false)
                return false;
        }
        
		$("[name=next_step]").val($(this).attr('href'));
		data = $("#cart_form").serialize();
		href = $("#cart_form").attr("action");
		
        $.overlay_loader();
		$.post(href, data, function(r) {
			if (r.redirect) {
				window.location.href = r.redirect; 
            } else {
                $.overlay_close();
			    if (r.content) {
				    $("#cart").replaceWith(r.content);
                    
                    //scroll_to_section();
                    
				    step = $("#cart").attr("step");    
				    url = url_base + "/" + step;
				    
				    $("#cart").pushState(url, url, {"step" : step}); 
                    $(document).trigger("cart_scroll", [step]);
			    } 
            }
		}, "json").fail(function() {
            $("#cart_main").prepend("<div class='cart_message cart_message_error'>Error occured while processing your cart</div>");
            $.scrollTo($("#cart_main"));
            $.overlay_close();
        });
		
		return false;
	});
	<?php else: ?>
	$(".cart_step").click(function() { return false });
	<?php endif ?>
});
</script>

<div id="cart" step="<?=$step?>">
	<?php if ($step): ?>
	<form id="cart_form" method="post" action="<?=url::site("Cart/Submit/" . $step)?>">
        
		<input type="hidden" name="next_step" value="">
		<input type="hidden" name="ajax" value="1">
		
        <table id="cart_header">
        <tr>
        <?php
            $ci = array_search($step, $steps);
    
            $w = 1 / count($steps) * 100;
            foreach ($steps as $i => $step) {
                $c = "";
                $s = "cart_step";
                if ($i < $ci) $c.= " comp";
                if ($i == $ci) $c.= " curr";
                
                if ($step == 'Invoice') {
                    $s = "";
                    $c.= " final";
                }
                
                echo "<td class='cart_tab $c' width='$w%'>";
                echo "<a href='$step' class='$s $c' onclick='return false;'>" . et("cart_step_" . strtolower($step)) . "</a>";
                echo "</td>";
            }  
        ?>
        </tr>
        </table>
		
        <?php foreach ($steps as $stp): ?>
        
        <a class="cart_step cart_section <?php if ($stp == $step) echo " curr"?>" href="<?=$stp?>" onclick="return false"><?=et("cart_step_" . strtolower($stp))?></a>
        
        <?php if ($stp == $step): ?>
		<div id="cart_main" <?php if ($editable) echo "class='cart_edit'"?>>
            <?php if ($message): ?>
            <div id="cart_message" class="cart_message cart_message_<?=$message_type?>">
                <?=$message?>
            </div>
            <?php endif ?>
            
			<?php if (isset($content)) echo $content ?>
		</div>
        
		<?php if ($prev || $next): ?>
		<div id="cart_menu">
			<?php if (is_string($prev)): ?>
			<a href="<?=$prev?>" onclick="return false" id="cart-prev" class="cart_button cart_step prev"><?=et("cart_step_prev." . strtolower($step))?></a>
			<?php elseif (is_array($prev)): ?>
			<a href="<?=$prev["url"]?>" class="cart_button prev"><?=$prev["title"]?></a>
			<?php endif ?>
			
			<?php if (is_string($next)): ?>
			<a href="<?=$next?>" onclick="return false" id="cart-next" class="cart_button cart_step next"><?=et("cart_step_next." . strtolower($step))?></a>
			<?php elseif (is_array($next)): ?>
			<a href="<?=$next["url"]?>" class="cart_button next"><?=$next["title"]?></a>
			<?php endif ?>
		</div>
		<?php endif ?>
        <?php endif ?>

        <?php endforeach ?>
	</form>
<?php else: ?>
<?php echo $content; ?>
<?php endif ?>
</div>

<?php 
	if (isset($_GET['show_order']))
		xd($order);
?>
