<?php
	use Sloway\Admin;
	use Sloway\acontrol;
	use Sloway\config;

?>

<?php        
    echo Admin::Field(et("Title"), acontrol::edit('title'));
    echo Admin::Field(et("Code"), acontrol::edit('code', ""));
    echo Admin::Field(et("Initial stock"), acontrol::edit('stock', config::get("catalog.initial_stock", 0)));
    
    $ops = array(
        "paths" => true,
        "style" => "max-height: 200px; overflow: auto",
        "three_state" => false,
        "dependency" => "0110"
    );
    $tree = acontrol::tree_items($categories, "subcat", "id", "title");
    echo Admin::Field(et("Categories"), acontrol::checktree('categories', $tree, $categories_value, $ops));
?>
        
