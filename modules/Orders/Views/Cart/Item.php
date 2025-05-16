<?php 
	namespace Sloway;

    if (!isset($subitem)) $subitem = false;
    $sub_items = v($item, "items", array());
    
    $class = "cart_item";
    if ($subitem) $class.= " cart_subitem";
    if ($group = count($sub_items) > 0) $class.= " cart_group";    
?>  

<tr class="<?=$class?>" data-index="<?=$index?>">
<?php foreach ($columns as $column): ?>
    <?php if ($column == "image"): ?>

    <!-- IMAGE -->
    <?php if (!$subitem): ?>
    <td data-col="image" rowspan="<?=count($sub_items)+2?>">
    <?php if ($item->image) echo thumbnail::create(null, $item->image, null, "cart_thumb")->display(); ?>
    </td>
    <?php endif ?>
    <?php elseif ($column == "desc"): ?>
    
    <!-- DESC -->
    <td data-col="desc">
        <?php if ($item->url): ?>
        <a class="cart_item_title" href="<?=$item->url?>"><?=$item->title?></a>
        <?php else: ?>
        <div class="cart_item_title"><?=$item->title?></div>
        <?php endif ?>
        <div class="cart_item_desc"><?=$item->description?></div>
        
        <?php if (count($sub_items)): ?>
        <div class="cart_item_subdesc">
        <?php 
            foreach ($sub_items as $i => $sub) 
                echo " - " . $sub->title . "<br>";
        ?>
        </div>
        <?php endif ?>
    </td>
    <?php elseif ($column == "quantity"): ?>    
    
    <!-- QUANTITY -->    
    <td data-col="quantity">
        <label><?=et("cart_column_quantity")?>:</label>
        <?php
            if ($subitem) 
                echo ""; else
            if (!$edit) 
                echo $item->quantity; else
                echo acontrol::select("quantity", $order->itemQuantitySelect($item), $item->quantity, array("style" => "max-width: 100px", "placeholder" => $item->quantity . " - " . t("out of stock"))); 
        ?>
    </td>
    <?php elseif ($column == "price_piece"): ?>
    
    <!-- PRICE/PIECE -->
    <td data-col="price_piece">
        <label><?=et("cart_column_price_piece")?></label>
        <?php
            if ($item instanceof order_group) 
                echo $order->groupPrice($item, "item", "tax,format"); else
                echo $order->itemPrice($item, "item", "tax,format");  
        ?>
    </td>
    <?php elseif ($column == "discount"): ?>
    
    <!-- DISCOUNT -->
    <td data-col="discount">
        <label><?=et("cart_column_discount")?>:</label>
        <?php
            echo $order->itemPrice($item, "discount", "format");
        ?>        
    </td>
    <?php elseif ($column == "commission"): ?>
    
    <!-- COMMISSION -->
    <td data-col="commission">
        <label><?=et("cart_column_commission")?>:</label>
        <?php
            //if ($edit && $this->cart_edit)
            //    echo acontrol::edit("commission", fixed::gen($item->commission), array("style" => "max-width: 100px")); else
                echo utils::price($item->commission);
        ?>
    </td>
    <?php elseif ($column == "price_total"): ?>
    
    <!-- PRICE TOTAL -->
    <td data-col="price_total">
        <label><?=et("cart_column_price_total")?>:</label>
        <?php 
            if ($item instanceof order_group) 
                echo $order->groupPrice($item, "item", "all,format"); else
                echo $order->itemPrice($item, "item", "all,format");
        ?>
    </td>
    <?php endif ?>
<?php endforeach ?>
</tr>

<?php
    foreach ($sub_items as $i => $sub) {
        echo new View("Cart/Item", array("item" => $sub, "subitem" => true, "index" => $index . ".items." . $i, "edit" => $edit, "columns" => $columns));      
    }
?> 

<?php if (!$subitem): ?>
<tr class="cart_item_menu">
    <td>
        <?php if ($edit): ?>
        <?php if (!$item->in_stock): ?>
        <div class="cart_item_outofstock"><?=et("cart_item_out_of_stock")?></div>
        <?php endif ?>
        
        <?php if ($edit): ?>
        <a href="<?=$index?>" class="cart_item_remove" onclick="return false"><?=et("Remove")?></a>
        <?php endif ?>
        <?php endif ?>
    </td>
</tr>
<?php endif ?>

