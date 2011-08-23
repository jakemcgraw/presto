<?php

/**
 * Maps an HTTP request method and URI to function
 *
 * @param string $method HTTP request method (head, get, post, put, delete)
 * @param string $request HTTP path
 * @param string $base_url Prefix HTTP paths (optional)
 * @return mixed Function name on success, FALSE on error
 * @author Jake McGraw <social@jakemcgraw.com>
 */
function mapd_route($method, $request, $base_url=null)
{
    $allowed_methods = array("get", "post", "put", "delete", "head");
    $method = strtolower($method);
    
    if (false === in_array($method, $allowed_methods)) {
        return false;
    }
    
    $orig_request = $request;
    
    $request = (null !== $base_url) ?
        preg_replace('{^'.$base_url.'}i', "", $request) : $request;
    
    $parts = explode("/", strtolower($request));
    $parts = array_filter($parts, function($n){
        return !empty($n);
    });
    
    if (empty($parts)) {
        return "mapd_{$method}_index";
    }
    
    $parts = array_map(function($n) {
        return preg_replace_callback('/-(\w)/', function($m){
            return strtoupper($m[1]);
        }, $n);
    }, $parts);
    
    array_unshift($parts, "mapd", $method);
    return implode("_", $parts);
}

/**
 * Executes a function based on URI, HTTP request method
 *
 * @param string $func Function to execute, provided by mapd_route()
 * @param array $vars Parameters to pass to mapd function (optional)
 * @return mixed String or array on success, FALSE on error
 * @author Jake McGraw <social@jakemcgraw.com>
 */
function mapd_exec($func, array $vars=array())
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
 * @param string $func Function to execute, provided by mapd_route()
 * @param array $vars Parameters to pass to mapd function (optional)
 * @return mixed String or array on success, FALSE on error
 * @author Jake McGraw <social@jakemcgraw.com>
 */
function mapd_exec_json($func, array $vars=array())
{
    ob_start();
    list($success, $result) = mapd_exec($func, $vars);
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
    
    // try to encode response
    if (false === ($json = @json_encode($response))) {
        
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