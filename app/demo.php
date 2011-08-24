<?php

function presto_get_index_index()
{
    return array(true, "index index");
}

function presto_get_demo_index()
{
    return array(true, "demo index");
}

function presto_get_demo_echo($vars)
{
    $echo = isset($vars["echo"]) ? $vars["echo"] : "empty";
    return array(true, $echo);
}

function presto_get_demo_time()
{
    return array(true, (string) time());
}

function presto_get_demo_httpError()
{
    return array(false, array(
        "http" => array(
            "error" => "Failboat",
            "errno" => 599,
        ),
    ));
}