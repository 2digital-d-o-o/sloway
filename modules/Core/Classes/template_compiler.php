<?php

namespace Sloway;

class template_compiler
{
    public static function parse_tags($attr)
    {
        $atList = [];

        $attr = trim($attr);
        if (!$attr) return [];

        if (preg_match_all('/\s*(?:([a-z0-9-]+)\s*=\s*["\']([^"^\']*)["\'])|(?:\s+([a-z0-9-]+)(?=\s*|>|\s+[a..z0-9]+))/i', $attr, $m)) {
            for ($i = 0; $i < count($m[0]); $i++) {
                if ($m[3][$i])
                    $atList[$m[3][$i]] = null;
                else
                    $atList[$m[1][$i]] = $m[2][$i];
            }
        }

        return $atList;
    }
    public static function link($url)
    {
        $trg = "";
        if (strpos($url, "_BLANK") !== false) {
            $url = str_replace("_BLANK", "", $url);
            $trg = "_blank";
        }
        $url = trim($url);
        if ($url && strpos($url, "http") !== 0 && strpos($url, "ftp") !== 0)
            $url = url::site($url);

        if (!$url) return false;

        $res = new stdClass();
        $res->url = $url;
        $res->trg = $trg;

        return $res;
    }
    public static function slideshow($slideshow, $param, $ops)
    {
        $framed = pq($slideshow)->hasClass("rl_framed");
        $size = pq($slideshow)->attr("data_attr_sld_size");

        $interval = pq($slideshow)->attr("data_attr_int");
        if (!$interval) $interval = 0;

        $span = pq($slideshow)->children("ul");
        $style = pq($span)->attr("style");
        $items = pq($span)->children("li");
        $slides = array();
        foreach ($items as $item) {
            $slide = new genClass();
            $slide->background = str_replace("media/uploads/", "", pq($item)->attr("data-path"));
            $slide->title = pq($item)->children("[data-name=title]")->html();
            $slide->content = pq($item)->children("[data-name=desc]")->html();
            $slide->link = self::link(pq($item)->children("[data-name=url]")->html());
            $slides[] = $slide;
        }

        if (count($slides)) {
            $ss = slideshow::create($slides, $size);
            $html = "<div class='rl_template_span' style='$style'>";
            $html .= view($ops["view_site"], array(
                "slides" => $ss->slides,
                "framed" => $framed,
                "aspect_ratio" => $ss->aspect_ratio,
                "height" => $ss->height,
                "interval" => $interval,
                "max_height" => $ss->max_height,
                "min_height" => $ss->min_height,
            ));
            $html .= "</div>";
        } else
            $html = "";

        pq($slideshow)->html($html);
    }
    public static function image_list($gallery, $param, $ops)
    {
        $img_mode = pq($gallery)->attr("data_attr_img_mode");
        $ajax = pq($gallery)->hasClass("rl_class_ajax");

        $span = pq($gallery)->children("ul");
        $style = pq($span)->attr("style");
        $items = pq($span)->children("li");

        $images = array();
        foreach ($items as $item) {
            $img = new genClass();
            $img->path = path::gen("site.uploads", str_replace("media/uploads/", "", pq($item)->attr("data-path")));
            $img->title = pq($item)->children("[data-name=title]")->html();
            $img->url = pq($item)->children("[data-name=url]")->html();
            $img->mode = $img_mode;


            $images[] = $img;
        }

        if (!count($images)) return;

        $html = "<div class='rl_template_span' style='$style'>";
        $html .= view($ops["view_site"], array(
            "images" => $images,
            "popup" => pq($gallery)->hasClass("rl_class_popup"),
            "item_width" => pq($gallery)->attr("data_attr_itm_width"),
            "item_spacing" => pq($gallery)->attr("data_attr_itm_spacing"),
            "aspect_ratio" => pq($gallery)->attr("data_attr_itm_ratio"),
            "image_mode" => pq($gallery)->attr("data_attr_img_mode"),
            "center" => true,
            "ajax" => $ajax,
        ));
        $html .= "</div>";

        pq($gallery)->html($html);
    }
    public static function image_slider($gallery, $param, $ops)
    {
        $framed = pq($gallery)->hasClass("rl_framed");
        $img_mode = pq($gallery)->attr("data_attr_img_mode");
        $ajax = pq($gallery)->hasClass("rl_class_ajax");

        $span = pq($gallery)->children("ul");
        $style = pq($span)->attr("style");
        $items = pq($span)->children("li");

        $images = array();
        foreach ($items as $item) {
            $img = new genClass();
            $img->path = path::gen("site.uploads", str_replace("media/uploads/", "", pq($item)->attr("data-path")));
            $img->title = pq($item)->children("[data-name=title]")->html();
            $img->url = pq($item)->children("[data-name=url]")->html();
            $img->mode = $img_mode;

            $images[] = $img;
        }

        if (!count($images)) return;

        $html = "<div class='rl_template_span' style='$style'>";
        $html .= view($ops["view_site"], array(
            "images" => $images,
            "framed" =>  $framed,
            "class" => "rl_slider",
            "caption" => pq($gallery)->find(".rl_template_element[data-name=title]")->html(),
            "item_height" => pq($gallery)->attr("data_attr_itm_height"),
            "item_width" => pq($gallery)->attr("data_attr_itm_width"),
            "image_mode" => pq($gallery)->attr("data_attr_img_mode"),
            "interval" => pq($gallery)->attr("data_attr_sld_auto"),
            "speed" => pq($gallery)->attr("data_attr_sld_speed"),
            "ajax" => $ajax,
        ));
        $html .= "</div>";

        pq($gallery)->html($html);
    }
    public static function banner($banner, $param, $ops)
    {
        $image = pq($banner)->find(".adaptive_image")->attr("data-path");

        $ratio = pq($banner)->attr("data_attr_itm_ratio");
        if (!$ratio) $ratio = "%";

        if ($ratio == "%") {
            if ($image) {
                $pth = str_replace("media/uploads/", "", $image);
                $size = @getimagesize(path::gen("root.uploads", $pth));
                if ($size)
                    $ratio = 100 * $size[1] / $size[0];
                else
                    $ratio = false;
            } else
                $ratio = false;
        }

        $id = \Sloway\utils::generate_id();
        if ($ratio) {
            $html = view($ops["view_site"], array(
                "id" => $id,
                "ajax" => pq($banner)->hasClass("rl_class_ajax"),
                "ratio" => $ratio,
                "image" => $image,
                "title" => pq($banner)->find(".rl_template_element[data-name=title]")->html(),
                "desc" => pq($banner)->find(".rl_template_element[data-name=desc]")->html(),
                "link" => self::link(pq($banner)->find(".rl_template_element[data-name=url]")->html()),
                "alt" => pq($banner)->find(".rl_template_element[data-name=img_alt]")->html(),
            ));
        } else
            $html = "";

        pq($banner)->attr("id", $id);
        pq($banner)->children(".rl_template_span")->html($html);
    }
    public static function banner_list($list, $param, $ops)
    {
        $ajax = pq($list)->hasClass("rl_class_ajax");

        $span = pq($list)->children("ul");
        $style = pq($span)->attr("style");
        $items = pq($span)->children("li");

        $banners = array();
        foreach ($items as $item) {
            $itm = new genClass();
            $itm->image = path::gen("site.uploads", str_replace("media/uploads/", "", pq($item)->attr("data-path")));
            $itm->title = pq($item)->children("[data-name=title]")->html();
            $itm->desc = pq($item)->children("[data-name=desc]")->html();
            $itm->link = self::link(pq($item)->children("[data-name=url]")->html());
            $itm->alt = pq($item)->children("[data-name=alt]")->html();

            $banners[] = $itm;
        }

        if (count($banners)) {
            $html = "<div class='rl_template_span' style='$style'>";
            $html .= view($ops["view_site"], array(
                "items" => $banners,
                "ajax" =>  pq($list)->hasClass("rl_class_ajax"),
                "item_width" => pq($list)->attr("data_attr_itm_width"),
                "item_spacing" => pq($list)->attr("data_attr_itm_spacing"),
                "ratio" => pq($list)->attr("data_attr_itm_ratio"),
            ));
            $html .= "</div>";
        } else
            $html = "";

        pq($list)->html($html);
    }
    public static function section($section, $param)
    {
        $onclick = "return rl_section_toggle.apply(this)";
        $style = "height: 0px; overflow: hidden";

        $div = pq($section)->children(".rl_template_span")->children("div");
        $div->children(".rl_section_title")->attr("onclick", $onclick);
        $div->children(".rl_section_content")->attr("style", $style);
    }
    public static function frame($frame, $param)
    {
        $url = self::link(pq($frame)->children(".rl_template_element[data-name=url]")->html());

        if ($url) {
            $a = "<a class='rl_frame_link' href='$url->url' target='$url->trg'></a>";
            pq($frame)->append($a);
        }
    }
    public static function loader($name, $elem, $class)
    {
        $content = pq($elem)->htmlOuter();

        $html = buffer::view("Templates/Loader", array("name" => $name, "content" => $content, "class" => $class));
        pq($elem)->replaceWith($html);
    }

    public static function compile_pass($doc, $priority)
    {
        foreach (config::get("templates.templates") as $name => $ops) {
            //if (!isset($ops["compiler"])) continue;
            if (v($ops, "priority", false) != $priority) continue;

            $loader = v($ops, "loader", false);
            $compiler = v($ops, "compiler", false);


            // if (!is_callable($compiler)) continue;

            $elems = pq($doc)->find(".rl_template_" . $name);
            foreach ($elems as $elem) {
                $tags_str = pq($elem)->children("[data-name=tags]")->html();
                $tags = self::parse_tags($tags_str);
                foreach ($tags as $key => $val) {
                    if ($key == "class") {
                        pq($elem)->addClass($val);
                    } else
                        pq($elem)->attr($key, $val);
                }

                if ($loader)
                    self::loader($name, $elem, v($ops, "loader_class", ""));
                else
                if (is_callable($compiler))
                    call_user_func($compiler, $elem, v($ops, "compiler_param"), $ops);
            }
        }
    }
    public static function compile($content, $ops = null)
    {
        require_once MODPATH . "Core/Classes/phpQuery.php";

        $content_id = null;
        if (preg_match('~<ins.*?data-cid=.([\/.a-z0-9:_\-\s\%20]+).*?><\/ins>~si', $content, $m))
            $content_id = $m[1];

        if (config::get("templates.cache") && $content_id && $res = cache($content_id)) {
            return $res;
        }

        $content = self::convert_image_urls($content);

        $doc = \phpQuery::newDocument($content);
        //  High priority
        self::compile_pass($doc, true);
        //  Normal priority
        self::compile_pass($doc, false);

        $res = $doc->html();

        if (config::get("templates.cache") && $content_id)
            cache()->save($content_id, $res, 3600);

        return $res;
    }

    protected static function convert_image_urls($content)
    {
        $mode = getenv("MODE");
        if(!$mode) return $content;

        if ($mode === "development") {
            $prefix = getenv("DEV_PROJECT_URL");
        } else if ($mode === "production") {
            $prefix = getenv("PROJECT_URL");
        }

        if(!$prefix) return $content;

        $pattern = '/(src=["\']|background-image:\s*url\(["\']).*?(media\/uploads\/[^"\')]+)(["\')])/';

        return preg_replace_callback($pattern, function ($matches) use ($prefix) {
            return $matches[1] . $prefix . $matches[2] . $matches[3];
        }, $content);
    }
}
