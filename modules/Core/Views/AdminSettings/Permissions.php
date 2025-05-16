<?php

use Sloway\Admin;
use Sloway\acontrol;


?>
<script>
function check_all() { $.ac.checktree.check_all($("#permissions")); }
function uncheck_all() { $.ac.checktree.uncheck_all($("#permissions")); }
</script>
<?php
    $m = "<br>";
    $m.= "<a class='admin_link' onclick='check_all()'>" . et("Check all") . "</a><br>";
    $m.= "<a class='admin_link' onclick='uncheck_all()'>" . et("Uncheck all") . "</a><br>";
    
    echo Admin::Field(et("Title"), acontrol::edit('name', $role->name));
    $ops = array(
        'trans' => true,
        'paths' => true,
        'expanded' => true, 
        'three_state' => false, 
        'dependency' => "0110", 
        'id' => 'permissions',
    );
    // echod($perm_tree);
    echo Admin::Field(et("Permissions") . $m, acontrol::checktree('permissions', $perm_tree, $role->permissions, $ops));
?>
