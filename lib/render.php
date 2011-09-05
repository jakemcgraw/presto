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
    foreach($collection as $render_key => $render_collection) {
        if (!is_array($render_collection)) continue;
        $result .= render($render_template, array_merge($render_collection, array(
            "render_key" => $render_key, "render_collection" => $render_collection,
        )));
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

class RenderException extends Exception {}