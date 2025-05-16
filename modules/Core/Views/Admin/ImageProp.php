<?php

namespace Sloway;

echo Admin::Field(et("Title"), acontrol::edit("title", $title));
//echo Admin::Field(et("Content"), Admin::HtmlEditor("desc", $desc, array("size" => "small")));
echo Admin::Field(et("URL"), acontrol::edit("url", $url));
echo Admin::Field(et("Alt"), acontrol::edit("alt", $alt));
