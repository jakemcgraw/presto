<?php

function render($render_template, array $render_params=array())
{
    static $render_template_dir;
    
    if (null === $render_template_dir) {
        if (!defined("RENDER_DIR")) {
            throw new RenderException("Missing required constant RENDER_DIR");
        }

        if (!is_dir(RENDER_DIR)) {
            throw new RenderException("Invalid directory specified by RENDER_DIR: (" . RENDER_DIR . ")");
        }
        
        $render_template_dir = realpath(RENDER_DIR);
    }
    
    $render_template_filename = $render_template_dir . "/" . $render_template;
    
    extract($render_params, EXTR_SKIP);
    ob_start();
    if (false === @include($render_template_filename)) {
        throw new RenderException("Invalid template ($render_template_filename)");
    }
    return ob_get_clean();
}

function render_partial($render_template, array $collection)
{
    $result = "";
    foreach($collection as $render_key => $render_item) {
        $params = array("render_key" => $render_key, "render_item" => $render_item);
        if (is_array($render_item)) {
            $params = array_merge($params, $render_item);
        }
        $result .= render($render_template, $params);
    }
    return $result;
}

function render_layout($render_template, array $params=array(), $render_layout=null)
{
    if (null === $render_layout) {
        $render_layout = "layout.phtml";
    }
    $params["content"] = render($render_template, $params);
    return render($render_layout, $params);
}

function render_static_add($name, $type=null)
{
    static $static = array();
    if (func_num_args() > 0){
        if (null === $type) {
            if (preg_match('/\.(js|css)$/', $name, $match)) {
                $type = $match[1];
            }
        }
        if (false === strpos($name, "/static")) {
            $name = "/static/$type/$name";
        }
        $static[] = array($name, $type);
    }
    return $static;
}

function render_static($type)
{
    $collection = array_map(function($var){ return $var[0]; }, array_filter(render_static_add(),
        function($var) use ($type) { return $var[1] == $type; }
    ));
    
    $unique = array();
    foreach($collection as $idx => $entry) {
        if (!in_array($entry, $unique)) {
            $unique[] = $entry;
        }
    }
    
    if (!empty($unique)) {
        return render_partial("partials/static_$type.phtml", $unique);
    }
    
    return "";
}

class RenderException extends Exception {}