<?php

require_once "../app/presto.php";
// require_once "../app/<YOUR PRESTO FUNCTIONS>.php";

// route request to figure out function name and variables
list($func, $vars) = presto_route(
    $_SERVER["REQUEST_METHOD"],
    $_SERVER["REQUEST_URI"]
);

// override $_REQUEST variables with URI variables
$request_vars = array_merge($_REQUEST, $vars);

// generate $body and $headers
list($body, $headers) = presto_encode($func, $request_vars);

// send
presto_send($body, $headers);