$.ac.checktree.on_build_node = function(parent, ops, index) {
    $(this).bind("contextmenu", function(e) {
        var menu = [];
        if (!$(this).hasClass("act_leaf")) {
            menu.push({content: "Expand all", name: "expand"});

            var tree = $(this).closest(".ac_tree");
            var ops = tree.data("ac");
            
            if (ops.dependency[3] != "1")
                menu.push({content: "Check all", name: "expand"});
        }
        
        if (menu.length)
        $(this).contextmenu(e.clientX, e.clientY, menu, {
        });   
    
        e.preventDefault();
        return false;
    });    
}