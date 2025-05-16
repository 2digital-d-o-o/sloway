<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>  
<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<script>

function init_timeline(tl) {
    if (!tl.length) return;
    
    tl.timeline({
        interval : 2,
        event_overflow: 3,
        start : '<?php echo utils::date(time()) ?>',
        end : '<?php echo utils::date(strtotime("+1 months")) ?>',
        val_prefix : "tl_",

        events: [
        { 
            title: "EVENT1",
            date: '<?php echo utils::date(time()) ?>', 
        },
        { 
            title: "EVENT2",
            date: '<?php echo utils::date(strtotime("+1 days")) ?>', 
        }
        ],

        /*
        fmt_event : function(e, date, data) {
            t = data.sequence[e.type].title;
            
            p = "";
            if (typeof e.price != "undefined" && e.price != '') {
                p+= " - " + e.price + "€";
                
                if (typeof e.commission != "undefined" && e.commission != '') 
                    p+= " / " + e.commission + "€";
            }
            
            return t + " " + dateFormat(date, "dd.mm") + p;
        },
        fmt_name : function(e, date, data) {
            return "tl_event:" + e.index;
        },
        fmt_value : function(e, date, data) {
            p = (typeof e.price != "undefined" && e.price != '') ? e.price : "";
            c = (typeof e.commission != "undefined" && e.commission != '') ? e.commission : "";
            return e.type + ":" + e.date + ":" + p + ":" + c;
        },     
        onUpdate : function() {
            update_tl_prices();
        },                */
    }); 
    
    //update_tl_prices(); 
}

/*
function update_tl_prices() {
    tl = $("#tl");
    data = tl.data("axis");
    
    events = data.events.slice(0);    
    events.sort(function(e1,e2) {
        d = e1.offset - e2.offset;
        if (Math.abs(d) > 0.0001)
            return d;
            
        return e1.itype - e2.itype;
    });
    
    html = "";
    for (i = 0; i < events.length; i++) {
        event = events[i];
        
        typ = data.sequence[event.type];
        html+= "<div class='tl_price' ind='" + event.index + "'>";
        html+= "<span>" + typ.title + " (" + event.date + ")</span>";
        if (typ.price) {
            ep = (typeof event.price != "undefined") ? event.price : "";
            ec = (typeof event.commission != "undefined") ? event.commission : "";
            html+= '<span style="width: 40px"><?=t('Price')?>:</span><input class="tl_price_edit" type="text" value="' + ep + '">';
            
            html+= '<span style="width: 70px"><?=t('Commission')?>:</span><input class="tl_commission_edit" type="text" value="' + ec + '">';
            html+= '<input type="button" class="tl_price_apply advbutton admin_button small" value="<?=t('Apply')?>" style="display: none">';
        } 
        html+= "</div>";
    }
    
    $("#tl_prices").html(html);
}     */

$(document).ready(function() {
    init_timeline($("#tl"));
});
/*        
    $(".tl_price_edit, .tl_commission_edit").live("keydown", function() {
        p = $(this).parents(".tl_price:first");    
        $(".tl_price_apply", p).show();        
    });
    $(".tl_price_apply").live("click", function() {
        p = $(this).parents(".tl_price:first");
        ind = parseInt(p.attr("ind"));
        
        data = $("#tl").data("axis");
        data.events[ind].price = $(".tl_price_edit", p).val();
        data.events[ind].commission = $(".tl_commission_edit", p).val();
        
        $("#tl").data("axis", data);
        $("#tl").axis("update_events");
    });
});*/
</script>
<?php
    echo "<div id='tl'></div>";
    echo "<div id='tl_prices'></div>";

?>
    

 