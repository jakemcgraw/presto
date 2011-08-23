<?php

require_once "../app/mapd.php";

mapd_exec_json(
    mapd_route(
        $_SERVER["REQUEST_METHOD"],
        $_SERVER["REQUEST_URI"],
        null
    ),
    $_POST
);

