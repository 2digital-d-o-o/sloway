<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<?php 
    if (!isset($this->admin_panel))
        $this->admin_panel = array();
?>          
<div id="admin_panel_spacer"></div>
<div id="admin_panel">
    <table>
    <tr>
        <td class="admin_panel_dropdown">
            <a href="<?=url::site("Admin")?>">Administrator</a>
            <ul>
                <?php foreach ($this->admin_modules as $module): ?>
                <li><a href="<?=$module["link"]?>"><?=$module["title"]?></a></li>
                <?php endforeach ?>
            </ul>
        </td>
        <td>
            <span>Logged as: <?=$this->admin_user->username?></span>
            <a href="<?=url::site("AdminLogin/Logout")?>">Logout</a>
        </td>
        <?php 
            foreach ($this->admin_panel as $column) {
                if (is_array($column)) {
                    $dropdown = v($column, "dropdown");
                    if ($dropdown)
                        echo "<td class='admin_panel_dropdown'>"; else
                        echo "</td>";
                    
                    echo v($column, "title");
                    echo $dropdown;
                    echo "</td>";
                } else 
                if (is_string($column))
                    echo "<td>" . $column . "</td>";
            }
        ?>
    </tr>
    </table>
</div>
