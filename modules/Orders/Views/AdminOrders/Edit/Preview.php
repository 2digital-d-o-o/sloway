<?php namespace Sloway ?>

<div style="background-color: white; padding: 10px">
<?php 
    echo view("\Sloway\Order\Invoice", array("order" => $order));
?>
</div>