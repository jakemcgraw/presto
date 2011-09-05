<?php

require_once "../lib/presto.php";
require_once "../app/api/demo.php";

$base_url = "/";
$force_filetype = null; // json, js (jsonp) and xml supported
$with_request = true; // return object representing request in response


// route request to figure out function name and variables
list($func, $vars) = presto_route(
    $_SERVER["REQUEST_METHOD"],
    $_SERVER["REQUEST_URI"],
    $base_url
);

// override $_REQUEST variables with URI variables
$request_vars = array_merge($_REQUEST, $vars);

// generate $body and $headers
list($body, $headers) = presto_encode(
    $func, $request_vars,
    $force_filetype,
    $with_request
);

// send to browser
presto_send($body, $headers);
