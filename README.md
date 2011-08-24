## presto: A micro framework for mapping HTTP requests to PHP function calls

### Example

    <?php

    // HTTP GET / maps to
    presto_get_index_index();

    // HTTP GET /foo maps to
    presto_get_foo_index();

    // HTTP GET /foo/bar maps to
    presto_get_foo_bar();

    // HTTP GET /foo/bar-bar maps to
    presto_get_foo_barBar();

    // HTTP POST /foo/bar maps to
    presto_post_foo_bar(array(/* post variables */));

    // HTTP GET /foo/bar/12345 maps to
    presto_get_foo_bar(array(12345));

    // HTTP POST /foo/bar/hello maps to 
    presto_post_foo_bar(array("hello", /* post variables */));

    // HTTP GET /foo/bar/hello/world/monkey maps to
    presto_get_foo_bar(array("hello" => "world", "monkey"));

## Install

    TODO
 
