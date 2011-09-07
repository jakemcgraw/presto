<?php

function presto_get_index_index()
{
    return "index index";
}

function presto_get_demo_index()
{
    return "demo index";
}

function presto_get_demo_echo($vars)
{
    $echo = isset($vars["echo"]) ? $vars["echo"] : "empty";
    return $echo;
}

function presto_get_demo_time()
{
    return (string) time();
}

function presto_get_demo_alwaysFails0()
{
    return false;
}

function presto_get_demo_alwaysFails1()
{
    return array(false);
}

function presto_get_demo_alwaysFails3()
{
    return array(false, "whoops");
}

function presto_get_demo_nothing()
{
}

function presto_get_demo_empty()
{
    return;
}

function presto_get_demo_null()
{
    return null;
}

function presto_get_demo_true()
{
    return true;
}

function presto_get_demo_trueArray()
{
    return array(true);
}

function presto_get_demo_int0()
{
    return 0;
}

function presto_get_demo_emptyString()
{
    return "";
}

function presto_get_demo_string0()
{
    return "0";
}

function presto_get_demo_array()
{
    return array();
}

function presto_get_demo_singleElement()
{
    return array("hello world");
}

function presto_get_demo_multipleElements()
{
    return array("hello world", "foobar");
}

function presto_get_demo_multipleKeys()
{
    return array(true, "hello" => "world");
}

function presto_get_demo_httpError()
{
    return array(false, presto_http_response(599, "Failboat"));
}

function presto_get_demo_render()
{
    require_once LIB_DIR . "/render.php";
    return render_layout("demo.phtml", array("title" => "Test", "hello" => "world"));
}

function presto_get_demo_redirect()
{
    return presto_redirect("http://foobar.com");
}
