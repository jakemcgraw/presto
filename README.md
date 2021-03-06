# presto: A micro framework for mapping HTTP requests to PHP function calls

## Features

### Pretty URLs

* GET **/**  
```presto_get_index_index();```

* GET **/foo**  
```presto_get_foo_index();```

* GET **/foo/bar**  
```presto_get_foo_bar();```

* GET **/foo/bar-foo**  
```presto_get_foo_barFoo();```

### URL Variables

* GET **/foo/bar/12345**  
```presto_get_foo_bar( ["12345"] );```

* GET **/foo/bar/hello/world**  
```presto_get_foo_bar( ["hello" => "world"] );```

* GET **/foo/bar/hello/world/12345**  
```presto_get_foo_bar( ["hello" => "world", "12345"] );```

### Filetype detection

Supports HTML, XML, JSON and JSONP (requires _callback_ parameter)

* GET **/foo/bar.json**  
```presto_get_foo_bar();```  
```{"success":"true","result":...}```

* GET **/foo/bar/hello.html**  
```presto_get_foo_bar( ["hello"] );```  
```<!DOCTYPE html><html> ... </html>```

* GET **/foo/bar/hello.xml**  
```presto_get_foo_bar( ["hello"] );```  
```<?xml version="1.0"?><response><success>true</success><result>...</result></response>```

* GET **/foo/bar/hello/world.js?callback=demo**  
```presto_get_foo_bar( ["hello" => "world", "callback" => "demo"] );```  
```demo( {"success":"true","result":...} );```

### HTTP Verbs

* POST **/**  
```presto_post_index_index();```

* PUT **/**  
```presto_put_index_index();```

* DELETE **/**  
```presto_delete_index_index();```

* HEAD **/**  
```presto_head_index_index();```
 
## Return values

There are three types of return values for presto functions:

1. Implicit success (return any non-FALSE value)
2. Explicit success (return array, first element is TRUE)
3. Failure (return FALSE or array, first element is FALSE)

```php
<?php

// --- Success ---

// implicit
// {success: "true", result: /* int time */}
function presto_get_demo_time() { return time(); }

// explicit
// {success: "true", result: {/* int time */}}
function presto_get_demo_time() { return array(true, time()); }

// All the same
// {success: "true", result: {time: /* int time */}}

// implicit
function presto_get_demo_time() { return array("time" => time()); }

// explicit
function presto_get_demo_time() { return array(true, "time" => time()); }

// explicit
function presto_get_demo_time() { return array(true, array("time" => time())); }

// --- Failure ---

// {success: "false", result: "empty"}
function presto_get_demo_fail() { return false; }

// {success: "false", result: "empty"}
function presto_get_demo_fail() { return array(false); }

// {success: "false", result: "Whoops!"}
function presto_get_demo_fail() { return array(false, "Whoops!"); }

// --- Gotchas ---

// falsie != failure
// expected: {success: "false", result: "Whoops!"}
// actual: {success: "true", result: [0, "Whoops!"]}
function presto_get_demo_fail() { return array(0, "Whoops!"); }

// truthie != true
// expected: {success: "true", result: "hello world"}
// actual: {success: "true", result: [1, "Whoops!"]}
function presto_get_demo_success() { return array(1, "Whoops!"); }

```

## Requirements

* PHP 5.3+
* Apache w/ mod_rewrite