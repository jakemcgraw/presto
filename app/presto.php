<?php

/**
 * Maps an HTTP request method and URI to function
 *
 * @param string $method HTTP request method (head, get, post, put, delete)
 * @param string $request HTTP path
 * @param string $base_url Prefix HTTP paths (optional)
 * @return mixed [presto function, variables] on success, FALSE on error
 * @author Jake McGraw <social@jakemcgraw.com>
 */
function presto_route($method, $request, $base_url=null)
{
    $allowed_methods = array("get", "post", "put", "delete", "head");
    $method = strtolower($method);
    
    if (false === in_array($method, $allowed_methods)) {
        return array(false, array());
    }
    
    $orig_request = $request;
    
    // adios get parameters
    if (strpos($request, "?") !== false) {
        list($request) = explode("?", $request);
    }
    
    // goodbye base url
    $request = (null !== $base_url) ?
        preg_replace('{^'.$base_url.'}i', "", $request) : $request;
    
    $parts = explode("/", strtolower($request));
    
    // see ya empty paths
    $parts = array_values(array_filter($parts, function($n){
        return !empty($n);
    }));
    
    $vars = array();
    
    // process uri parameters
    if (count($parts) > 2) {
        $tmp = array_slice($parts, 2);
        $cnt = count($tmp);
        for ($i=0; $i<$cnt; $i++) {
            if ($i == $cnt-1) {
                $vars[] = $tmp[$i];
            }
            else {
                $vars[$tmp[$i]] = $tmp[++$i];
            }
        }
        // get rid of uri parameters from $parts, now in $vars
        $parts = array_slice($parts, 0, 2);
    }
    // not long enough for uri parameters, inject index
    else if (count($parts) < 2) {
        do {
            $parts[] = "index";
        }
        while(count($parts) < 2);
    }
    
    // convert "foo-bar" to "fooBar"
    $parts = array_map(function($n) {
        return preg_replace_callback('/-(\w)/', function($m){
            return strtoupper($m[1]);
        }, $n);
    }, $parts);
    
    // last variable is allowed to indicate filetype
    if (!empty($vars)) {
        $vals = array_values($vars);
        $last = $vals[count($vals)-1];
    }
    else {
        $last = $parts[1];
        $parts[1] = preg_replace('/\.[^.]+$/', "", $last);
    }
    
    // check for filetype
    if (false !== strpos($last, ".")) {
        if (preg_match("/\.(\w+)$/", $last, $match)) {
            $vars["_filetype"] = $match[1];
        }
    }
    
    // remove invalid characters
    $parts = array_map(function($n) {
        return preg_replace('/[^A-Za-z0-9]/', "", $n);
    }, $parts);
    
    array_unshift($parts, "presto", $method);
    
    // generate function name
    $func = implode("_", $parts);
    
    return array($func, $vars);
}

/**
 * Executes a function based on URI, HTTP request method
 *
 * @param string $func Function to execute, provided by presto_route()
 * @param array $vars Parameters to pass to presto function (optional)
 * @return mixed String or array on success, FALSE on error
 * @author Jake McGraw <social@jakemcgraw.com>
 */
function presto_exec($func, array $vars=array())
{
    if (false === $func || !is_string($func)) {
        return array(false, array(
            "http" => array(
                "error" => "Invalid Method",
                "errno" => 405,
            ),
        ));
    }
    
    if (!function_exists($func)) {
        return array(false, array(
            "http" => array(
                "error" => "Not Found",
                "errno" => 404,
            ),
        ));
    }
    
    $result = $func($vars);
}

/**
 * Executes a function based on URI, HTTP request, sends JSON response
 *
 * @param string $func Function to execute, provided by presto_route()
 * @param array $vars Parameters to pass to presto function (optional)
 * @return mixed String or array on success, FALSE on error
 * @author Jake McGraw <social@jakemcgraw.com>
 */
function presto_exec_json($func, array $vars=array())
{
    ob_start();
    list($success, $result) = presto_exec($func, $vars);
    $output = ob_get_clean();
    
    $response = array();
    
    if (!$success) {
        if (isset($result["http"]["errno"])) {
            header("HTTP/1.0 " . $result["http"]["errno"] . " " . $result["http"]["error"]);
            $response = $result["http"];
        }
        else {
            header("HTTP/1.0 500 Internal Server Error");
            $response = $result;
        }
    }
    
    // shouldn't generate any output, captured here though
    if (!empty($output)) {
        $response["output"] = $output;
    }
    
    if ($success && (is_string($result) || is_array($result))) {
        $response["result"] = $result;
    }

    $json = "";
    
    // try to encode response
    if (!empty($response) && (false === ($json = @json_encode($response)))) {
        
        // error encoding response
        if ($success) {
            header("HTTP/1.0 500 Internal Server Error");
        }
    
        $response = array(
            "error" => "Unable to encode API response",
            "errno" => 500 + ((int) json_last_error())
        );
    
        $json = json_encode($response);
    }
    
    header("Content-type: application/json");
    echo $json;
    
    return $result;
}


function presto_exec_jsonp($func, array $vars=array())
{
    /*
        TODO 
    */
}

function presto_exec_html($func, array $vars=array())
{
    /*
        TODO 
    */
}

function presto_exec_xml($func, array $vars=array())
{
    /*
        TODO 
    */
}

function presot_exec_txt($func, array $vars=array())
{
    /*
        TODO 
    */
}