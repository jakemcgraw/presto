<?php

require_once "../app/presto.php";
// require_once "../app/<YOUR PRESTO FUNCTIONS>.php";

list($func, $vars) = presto_route(
    $_SERVER["REQUEST_METHOD"],
    $_SERVER["REQUEST_URI"]
);

presto_exec_json($func, array_merge($_REQUEST, $vars));
