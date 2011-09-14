<?php

/**
 * Maps an HTTP request method and URI to function
 *
 * @param string $method HTTP request method (head, get, post, put, delete)
 * @param string $request HTTP path
 * @param string $base_url Prefix HTTP paths (optional)
 * @param array $headers HTTP request headers (optional)
 * @return mixed [presto function, variables] on success, FALSE on error
 * @author Jake McGraw <social@jakemcgraw.com>
 */
function presto_route($method, $request, $base_url=null, array $headers=array())
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
    // generate _presto_filetype from request path
    if (!empty($vars)) {
        $vals = array_values($vars);
        $last = $vals[count($vals)-1];
        $keys = array_keys($vars);
        $vars[$keys[count($keys)-1]] = preg_replace('/\.[^.]+$/', "", $last);
    }
    else {
        for($i=1; $i>-1; $i--) {
            if (false !== strpos($parts[$i], ".")) {
                $last = $parts[$i];
                $parts[$i] = preg_replace('/\.[^.]+$/', "", $last);
                break;
            }
        }
    }
    
    // check for filetype
    if (isset($last) && false !== strpos($last, ".")) {
        if (preg_match("/\.(\w+)$/", $last, $match)) {
            $vars["_presto_filetype"] = $match[1];
        }
    }
    
    // check for X-Requested-With
    foreach($headers as $field => $value) {
        if ("x-requested-with" == strtolower($field)) {
            if ("xmlhttprequest" == strtolower($value)) {
                $vars["_presto_ajax"] = true;
            }
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
function presto_exec($func, array $vars=array(), $throw_exception=false)
{
    if (empty($func) || !is_string($func) || strpos($func, "presto_") !== 0) {
        $success = false;
        $result = presto_http_response(405, "Method Not Allowed");
    }
    else if (!function_exists($func)) {
        $success = false;
        $result = presto_http_response(404, "Not Found");
    }
    else {
        $return = $func($vars);

        // allow lazy returns false, [false], [false,...] maps to failure
        // everything else is success

        if (is_array($return) && isset($return[0]) && is_bool($return[0])) {
            $success = $return[0];
            array_shift($return);
            if (null !== $return) {
                if (0 == count($return)) {
                    $result = null;
                }
                else if (1 === count($return) && array_key_exists(0, $return)) {
                    $result = $return[0];
                }
                else {
                    $result = $return;
                }
            }
            else {
                $result = null;
            }
        }
        else if (is_bool($return)) {
            $success = $return;
            $result = null;
        }
        else {
            $success = true;
            $result = $return;
        }

        // null and empty 

        if (!isset($result)) {
            $result = "empty";
        }
    }
    
    if ($throw_exception && !$success) {
        
        $message = "Error occurred, exception thrown";
        $code = -1;
        
        if (is_array($result)) {
            if (array_key_exists("error", $result)) {
                if (is_array($result["error"])) {
                    if (array_key_exists("code", $result["error"])) {
                        $code = (int) $result["error"]["code"];
                    }
                    if (array_key_exists("message", $result["error"])) {
                        $message = $result["error"]["message"];
                    }
                    else {
                        $message = implode(" ", $result["error"]);
                    }
                }
                else {
                    $message = $result["error"];
                }
            }
            
            // no redirect
            if (array_key_exists("_presto_http", $result)) {
                unset($result["_presto_http"]);
            }
        }
        else {
            $message = $result;
        }
        
        throw new PrestoException($message, $code);
    }
    
    return array($success, $result);
}

function presto_send($body, array $headers=array())
{
    foreach($headers as $header) {
        header($header);
    }
    echo $body;
}

function presto_encode($func, array $vars=array(), $type="json", $with_request=true)
{
    $type_map = array(
        "js" => "jsonp", "json" => "json", "xml" => "xml", "htm" => "html", "html" => "html"
    );
    
    // requested type overrides default
    if (isset($vars["_presto_filetype"])) {
        $type = $vars["_presto_filetype"];
    }
    // no requested type, use default
    else {
        $vars["_presto_filetype"] = $type;
    }
    
    if (!isset($type_map[$type])) {
        return array(
            "Invalid Request, invalid API response type " . htmlspecialchars($type),
            array("HTTP/1.1 400 Invalid Request")
        );
    }
    
    $type = $type_map[$type];
    
    $encoder_func = "presto_encode_" . $type;
    if (!function_exists($encoder_func)) {
        return array(
            "Internal Server Error, un-handled API response type " . htmlspecialchars($type),
            array("HTTP/1.1 500 Internal Server Error")
        );
    }
    
    if ($type == "jsonp") {
        if (!isset($vars["callback"])) {
            return array(
                "Invalid Request, JSONP requires 'callback' parameter",
                array("HTTP/1.1 400 Invalid Request")
            );
        }
        $callback = $vars["callback"];
    }
    
    ob_start();
    list($success, $result) = presto_exec($func, $vars);
    $output = ob_get_clean();
    
    $headers = array();
    
    $response = array(
        "success" => ($success ? "true" : "false"),
    );
    
    if ($with_request) {
        $response["request"] = array(
            "function" => $func, "vars" => $vars,
        );
    }
    
    // check for _presto_http in result
    if (is_array($result)) {
        if (isset($result["_presto_http"])) {
            foreach($result["_presto_http"] as $header => $value) {
                if (is_string($header)) {
                    $headers[] = "$header: $value";
                }
                else {
                    $headers[] = $value;
                }
            }
            unset($result["_presto_http"]);
        }
    }
    
    $response["result"] = $result;
    
    if (!empty($output)) {
        $response["output"] = $output;
    }
    
    if ($type == "jsonp") {
        return $encoder_func($response, $headers, $callback);
    }
    
    return $encoder_func($response, $headers);
}

function presto_encode_json(array $response, array $headers=array())
{
    // try to encode response
    $json = @json_encode($response);
    
    if (false === $json) {
        $headers[] = "HTTP/1.1 500 Internal Server Error";
        $json = "{\"success\":\"false\", \"error\":\"Unable to encode API response, JSON error\", \"errno\":\"".(500 + ((int) json_last_error()))."\"}";
    }
    
    $headers[] = "Content-type: application/json";
    
    return array($json, $headers);
}

function presto_encode_jsonp(array $response, array $headers=array(), $callback)
{
    list($json, $headers) = presto_encode_json($response, $headers);
    
    $js = $callback . "( " .  $json . " );\n";
    
    $headers[] = "Content-type: text/javascript";
    
    return array($js, $headers);
}

function presto_encode_xml(array $response, array $headers=array())
{
    $sxml = _presto_xml2array($response, new SimpleXMLElement('<response />'));
    
    $xml = $sxml->asXML();
    
    $headers[] = "Content-type: text/xml";
    
    return array($xml, $headers);
}

function presto_encode_html(array $response, array $headers=array())
{
    if (!is_string($response["result"])) {
        foreach($headers as $header) {
            if (strpos($header, "Location:") === 0) {
                $html = "<!DOCTYPE html><html><body><h1>Redirecting you to <a href=\"$header\">$header</a></h1></body></html>";
                break;
            }
        }
        if (!isset($html)) {
            throw new PrestoException("HTML responses support strings only");
        }
    }
    else {
        $html = $response["result"];
    }
    
    $headers[] = "Content-type: text/html;charset=UTF-8";
    
    return array($html, $headers);
}

function presto_check_filetype(array $vars, array $supported_types=array())
{
    return isset($vars["_presto_filetype"]) &&
        in_array($vars["_presto_filetype"], $supported_types);
}

function presto_check_filetype_html($vars)
{
    if (!presto_check_filetype($vars, array("htm", "html"))) {
        return array(false, presto_http_response(404, "Not Found"));
    }
}

function presto_redirect($location)
{
    return presto_http_response(302, "Found", $location);
}

function presto_http_response($code=200, $message="OK", $location=null)
{
    
    if (null !== $location) {
        $response = array(
            "HTTP/1.1 302 Found",
            "Location" => $location
        );
    }
    else {
        $response = array(
            "HTTP/1.1 $code $message"
        );
    }
    
    $return = array("_presto_http" => $response);
    
    if ($code > 399) {
        $return["error"] = "$code $message";
    }
    
    return $return;
}

function presto_whitelist($func, array $whitelist=array())
{
    if (!function_exists($func)) {
        return true;
    }
    if (empty($whitelist)) {
        return false;
    }
    $pattern = "/(" . implode("|", $whitelist) . ")$/";
    return preg_match($pattern, $func);
}


function presto_func_to_path($func, array $vars=array())
{
    $func = preg_replace('/^presto_(' . implode("|", array("head", "get", "post", "put", "delete")) . ')_/', "", $func);
    $parts = explode("_", $func);
    foreach($parts as $idx => $entry) {
        if ($entry == "index") {
            unset($parts[$idx]);
        }
        else {
            $parts[$idx] = preg_replace_callback('/[A-Z]/', function($match){ return "-" . strtolower($match[1]); }, $entry);
        }
    }
    foreach($vars as $idx => $entry) {
        if (!is_int($idx)) {
            $parts[] = $idx;
        }
        $parts[] = $entry;
    }
    
    return "/" . implode("/", $parts);
}

/**
 * Convert array into SimpleXMLElement
 *
 * @param array $arr 
 * @param SimpleXMLElement $sxml 
 * @return SimpleXMLElement
 * @author onokazu
 * @link http://stackoverflow.com/questions/1397036/how-to-convert-array-to-simplexml/3289602#3289602
 */
function _presto_xml2array(array $arr, SimpleXMLElement $sxml)
{
    foreach ($arr as $k => $v) {
        if (is_array($v)) {
            _presto_xml2array($v, $sxml->addChild($k));
        }
        else if (is_int($k)) {
            $sxml->addChild("var", $v);
        }
        else {
            $sxml->addChild($k, $v);
        }
    }
    
    return $sxml;
}

class PrestoException extends Exception {}