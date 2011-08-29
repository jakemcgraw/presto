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
    if (empty($func) || !is_string($func) || strpos($func, "presto_") !== 0) {
        return array(false, array(
            "http" => array(
                "error" => "Invalid Method",
                "errno" => 405,
            ),
        ));
    }
    
    /*
        TODO autoload
    */
    if (!function_exists($func)) {
        return array(false, array(
            "http" => array(
                "error" => "Not Found",
                "errno" => 404,
            ),
        ));
    }
    
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
    
    return array($success, $result);
}

function presto_send($body, array $headers=array())
{
    foreach($headers as $header) {
        header($header);
    }
    echo $body;
}

function presto_encode($func, array $vars=array(), $type=null, $with_request=true)
{
    $type_map = array(
        "js" => "jsonp", "json" => "json", "xml" => "xml",
    );
    
    if (null === $type) {
        if (isset($vars["_presto_filetype"])) {
            $type = $vars["_presto_filetype"];
        }
        else {
            $type = "json";
        }
    }
    
    if (!isset($type_map[$type])) {
        return array(
            "400\nInvalid Request, invalid API response type $type",
            array(
                "http" => "HTTP/1.0 400 Invalid Request",
            )
        );
    }
    
    $type = $type_map[$type];
    
    $encoder_func = "presto_encode_" . $type;
    if (!function_exists($encoder_func)) {
        return array(
            "500\nInternal Server Error, unhandled API response type $type",
            array(
                "http" => "HTTP/1.0 500 Internal Server Error",
            )
        );
    }
    
    ob_start();
    list($success, $result) = presto_exec($func, $vars);
    $output = ob_get_clean();
    
    // implicit success when not false
    
    $headers = array();
    
    $response = array(
        "success" => ($success ? "true" : "false"),
    );
    
    if ($with_request) {
        $response["request"] = array(
            "func" => str_replace("presto_", "", $func),
            "vars" => $vars,
        );
    }
    
    if (!$success) {
        if (isset($result["http"]["errno"])) {
            $headers["http"] = "HTTP/1.0 " . $result["http"]["errno"] . " " . $result["http"]["error"];
            $response["result"] = $result["http"];
        }
        else {
            $headers["http"] = "HTTP/1.0 500 Internal Server Error";
            $response["result"] = $result;
        }
    }
    else if (is_string($result) || is_array($result)) {
        $response["result"] = $result;
    }
    
    // shouldn't generate any output, captured here though
    if (!empty($output)) {
        $response["output"] = $output;
    }
    
    return $encoder_func($response, $headers);
}

function presto_encode_json(array $response, array $headers=array())
{
    // try to encode response
    $json = @json_encode($response);
    
    if (false === $json) {
        $headers["http"] = "HTTP/1.0 500 Internal Server Error";
        $json = "{\"success\":\"false\", \"error\":\"Unable to encode API response, JSON error\", \"errno\":\"".(500 + ((int) json_last_error()))."\"}";
    }
    
    $headers["content"] = "Content-type: application/json";
    
    return array($json, $headers);
}

function presto_encode_jsonp(array $response, array $headers=array())
{
    if (!isset($response["request"]["vars"]["callback"])) {
        $headers["http"] = "HTTP/1.0 400 Bad Request";
        $body = "400\nMissing required parameter 'callback'\n";
        return array($body, $headers);
    }
    
    $callback = $response["request"]["vars"]["callback"];
    list($json, $headers) = presto_encode_json($response, $headers);
    $headers["content"] = "Content-type: text/javascript";
    $js = $callback . "( " .  $json . " );\n";
    
    return array($js, $headers);
}

function presto_encode_xml(array $response, array $headers=array())
{
    $sxml = _presto_xml2array($response, new SimpleXMLElement('<response />'));
    $headers["content"] = "Content-type: text/xml";
    return array($sxml->asXML(), $headers);
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