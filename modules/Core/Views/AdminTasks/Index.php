<?php namespace Sloway ?>

<script>  
$(document).ready(function() {
    var tasks_model = <?=json_encode($dg_model)?>;
    
    $("#tasks").datagrid({
        mode: "ajax",
        model: tasks_model,
        modules: ["col_resize", "sorting"],
        handler: doc_base + 'AdminTasks/Ajax_Handler',
        sorting: {
            sort: "id",
            sort_dir: -1
        },
        layout: {
            freeze: [0,1],
            fill: "spacer",
        },
        footer: {
            left: "reload",
        },
    });
});

function task_status() {
    var row = $(this).parents(".dg_row:first");
    var id = row.attr("data-id");
    var st = $(this).is(":checked") ? 1 : 0;
    
    $.post(doc_base + 'AdminTasks/Ajax_Active/' + id + '/' + st);    
}

function task_edit(id) {
    if (typeof id == "undefined") id = 0;
    
    $.overlay_ajax(doc_base + "AdminTasks/Ajax_Edit/" + id, "ajax", {}, {
        scrollable: true,
        onDisplay: function(r) {
            $(this).ac_create();
        },    
        onClose: function(r) {
            if (r) $("#tasks").datagrid("reload");
        }
    });     
    
    return false;   
}

function task_delete(id) {
    $.overlay_confirm('<?=Admin::Confirm("delete task")?>', function() {
        $("#tasks").datagrid("reload", { "delete" : id });
    });    
} 

</script>


<script>
$(document).ready(function() {
	$("[name^=active]").change(function() {
		id = $(this).attr('name').replace('active_','');
		st = $(this).val();
		
		$.post(doc_base + 'AdminTasks/Ajax_Active/' + id + '/' + st);
	});	
});
</script>
<?php   
	$menu = Admin::ButtonS(et("Add task"), null, "right", false, "onclick='task_edit()'");
	echo Admin::SectionBegin(et("Tasks") . $menu);
    echo "<div id='tasks'></div>";
	echo Admin::SectionEnd();
?>