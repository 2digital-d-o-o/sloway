(function( $ ){   
    $.core = {
        
        size_listener_timeout: null,
        images_listener_timeout: null,
        
        translating: false,
        translate: function(profile, url) {
            if ($.core.translating) {
                $.core.translating = false;
                $.overlay_close_all();
                return;
            }
    
            $.core.translating = true;
        
            var html = "";
            $(".translation").each(function(i) {
                var ofs = $(this).offset();
                var w = $(this).outerWidth();
                var h = $(this).outerHeight();
                var tid = "translation_" + i;
                                        
                $(this).attr("data-tid", tid);
                
                html+= '<div class="translation_hook" data-ref="' + tid + '"';
                html+= ' style="position: absolute; top: ' + ofs.top + 'px; left: ' + ofs.left + 'px; width: ' + w + 'px; height: ' + h + 'px; border: 1px solid red; cursor: pointer">';
                html+= '</div>';
            });

            var ov = $.overlay({
                modal: true,
                name: "translator",
                content: html,
                profile: profile,
                onDisplay: function(ops) {
                    var glass = $(this).children(".overlay_glass");
                    var zindex = parseInt(glass.css("z-index"));
                    
                    $(".translation_hook", this).each(function() {
                        $(this).css("z-index", zindex + 10);
                        $(this).click(function() {
                            var tid = $(this).attr("data-ref");
                            var orig = $(".translation[data-tid=" + tid + "]");   
                            
                            var data = {
                                text : orig.attr("data-trans"),
                                lang : orig.attr("data-lang"),
                                key : orig.attr("data-key"),
                            };
                            
                            $.overlay_ajax(url + "/" + ops.profile, "ajax", data, { 
                                name: tid, 
                                "class": "translator_dialog",
                                parent: "translator",
                                height: 300,
                                modal: false,
                                onLoaded: function() {
                                    $(this).ac_create();
                                    $(this).find("[name=text]").focus();
                                },
                                onClose: function(result) {
                                    if (result === false) return;
                                    
                                    var val = $(this).find("[name=text]").val();
                                    var key = $(this).find("[name=key]").val();
                                    
                                    $(".translation").each(function() {
                                        if ($(this).attr("data-key") == key) {
                                            $(this).html(val);
                                            $(this).attr("data-trans", val);
                                        }
                                    }); 
                                }
                            });
                            
                            return false;
                        });        
                    });
                }
            });
        },
        trans: {
            cookies_accept: 'Sprejmi', 
            cookies_decline: 'Zavrni',
            cookies_info: 'Spletno mesto uporablja piškotke'
        },
        language: function(lang, mode, callback) {
            var url = doc_base + "Core/Ajax_Language";
            
            $.post(url, { 
                lang: lang,
                mode: mode,
                profile: lang_profile
            }, function(r) {
                if (typeof callback == "function")
                    callback(true); 
                
                window.location.reload();
            }).fail(function() {
                if (typeof callback == "function")
                    callback(false); 
            });
        },
        cookies_info: function() {
            var state = $.cookie(project_name + '_cookies');
            if (state === null) 
                $.cookie(project_name + '_cookies', 0, { path: '/', expires: 100}); else
            if (state === "0") 
                $.cookie(project_name + '_cookies', 1, { path: '/', expires: 100});  
            
            var html = "<div id='cookies_button'></div>";
            html+= "<div id='cookies_info'>";
            html+= "<label>" + $.core.trans.cookies_info + "</label>";
            html+= "<button id='cookies_accept'>" + $.core.trans.cookies_accept + "</button>";
            html+= "<button id='cookies_decline'>" + $.core.trans.cookies_decline + "</button>";
            html+= "</div>";
            
            var menu = $(html).appendTo($("body")); 
            
            if (state === null)
                $("#cookies_info").addClass("unknown"); else
            if (state === "-1")
                $("#cookies_info").addClass("declined"); else
                $("#cookies_info").addClass("accepted");    
            
            $("#cookies_button").click(function() {
                if ($("#cookies_info").is(":visible"))
                    $("#cookies_info").hide(); else 
                    $("#cookies_info").show();
            });
            $("#cookies_accept").click(function() {
                $.cookie(project_name + '_cookies', 1, { path: '/', expires: 100});
                $("#cookies_info").addClass("accepted").removeClass("declined").hide();
            });
            $("#cookies_decline").click(function() {
                $.cookie(project_name + '_cookies', -1, { path: '/', expires: 100});
                $("#cookies_info").addClass("declined").removeClass("accepted").hide();
            }); 
        },   
        size_listener: function() {
            $("body").trigger("resized", [$("body").width()]); 
        },
        start_listeners: function() {
            $.core.size_listener();
            
            $(window).bind("resize", function() {
                clearTimeout($.core.size_listener_timeout);
                $.core.size_listener_timeout = setTimeout(function() {
                    $.core.size_listener();                    
                }, 20);    
            });
        }
    }
})( jQuery );        




