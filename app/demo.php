<?php

function presto_get_demo_echo($vars)
{
    $echo = isset($vars["echo"]) ? $vars["echo"] : "empty";
    return array(true, $echo);
}

function presto_get_demo_time()
{
    return array(true, time());
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