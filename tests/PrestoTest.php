<?php

require_once "PHPUnit/Framework/TestCase.php";

require_once "../lib/presto.php";
require_once "../app/demo.php";

class PrestoTest extends PHPUnit_Framework_TestCase
{
    public function testRoute()
    {
        list($func) = presto_route("GET", "/");
        $this->assertEquals("presto_get_index_index", $func);
        
        list($func) = presto_route("GET", "////");
        $this->assertEquals("presto_get_index_index", $func);
        
        list($func) = presto_route("GET", "/foo");
        $this->assertEquals("presto_get_foo_index", $func);
        
        foreach(array("GET", "POST", "PUT", "DELETE", "HEAD") as $method) {
            list($func) = presto_route($method, "/foo/bar");
            $this->assertEquals("presto_" . strtolower($method) . "_foo_bar", $func);
            
            list($func) = presto_route(strtolower($method), "/foo/bar");
            $this->assertEquals("presto_" . strtolower($method) . "_foo_bar", $func);
        }
        
        list($func) = presto_route("GET", "/fOO/bAR");
        $this->assertEquals("presto_get_foo_bar", $func);
        
        list($func) = presto_route("GET", "/foo/bar?hello=world");
        $this->assertEquals("presto_get_foo_bar", $func);
        
        list($func) = presto_route("FAIL", "/foo/bar");
        $this->assertFalse($func);
        
        list($func) = presto_route("GET", "/foo!@#$%^&*()+=/bar.;:'\"/");
        $this->assertEquals("presto_get_foo_bar", $func);
    }
    
    public function testRouteWithBaseUrl()
    {
        list($func) = presto_route("GET", "/api/", "/api");
        $this->assertEquals("presto_get_index_index", $func);
        
        list($func) = presto_route("GET", "/api/", "/api/");
        $this->assertEquals("presto_get_index_index", $func);
        
        list($func) = presto_route("GET", "/api/foo", "/api");
        $this->assertEquals("presto_get_foo_index", $func);
        
        list($func) = presto_route("GET", "/api/foo/bar", "/api");
        $this->assertEquals("presto_get_foo_bar", $func);
    }
    
    public function testRouteVariables()
    {
        foreach(array("/", "/foo", "/foo/bar", "/foo/bar/", "/foo///bar//") as $uri) {
            list($func, $vars) = presto_route("GET", $uri);
            $this->assertEmpty($vars);
        }
        
        list($func, $vars) = presto_route("GET", "/foo/bar/hello");
        $this->assertEquals("presto_get_foo_bar", $func);
        $this->assertEquals(array("hello"), $vars);

        list($func, $vars) = presto_route("GET", "/foo/bar/hello/");
        $this->assertEquals("presto_get_foo_bar", $func);
        $this->assertEquals(array("hello"), $vars);
        
        list($func, $vars) = presto_route("GET", "/foo///bar///hello");
        $this->assertEquals("presto_get_foo_bar", $func);
        $this->assertEquals(array("hello"), $vars);
        
        list($func, $vars) = presto_route("GET", "/foo/bar/hello/world");
        $this->assertEquals("presto_get_foo_bar", $func);
        $this->assertEquals(array("hello" => "world"), $vars);
        
        list($func, $vars) = presto_route("GET", "/foo/bar/a/b/c");
        $this->assertEquals("presto_get_foo_bar", $func);
        $this->assertEquals(array("a" => "b", "c"), $vars);
        
        list($func, $vars) = presto_route("GET", "/foo/bar/a/b/c/d");
        $this->assertEquals("presto_get_foo_bar", $func);
        $this->assertEquals(array("a" => "b", "c" => "d"), $vars);
        
        list($func, $vars) = presto_route("GET", "/foo/bar/a/B/c/D");
        $this->assertEquals("presto_get_foo_bar", $func);
        $this->assertEquals(array("a" => "b", "c" => "d"), $vars);
        
        list($func, $vars) = presto_route("GET", "/foo/bar/hello/world-spam");
        $this->assertEquals("presto_get_foo_bar", $func);
        $this->assertEquals(array("hello" => "world-spam"), $vars);
    }
    
    public function testRouteFiletype()
    {
        list($func, $vars) = presto_route("GET", "/foo/bar.js");
        $this->assertEquals("presto_get_foo_bar", $func);
        $this->assertEquals(array("_presto_filetype" => "js"), $vars);
        
        list($func, $vars) = presto_route("GET", "/foo/bar/hello.js");
        $this->assertEquals("presto_get_foo_bar", $func);
        $this->assertEquals(array("hello", "_presto_filetype" => "js"), $vars);
        
        list($func, $vars) = presto_route("GET", "/foo/bar/hello/world.js");
        $this->assertEquals("presto_get_foo_bar", $func);
        $this->assertEquals(array("hello" => "world", "_presto_filetype" => "js"), $vars);
    }
    
    public function testExec()
    {
        foreach(array("", 0, 1, true, false, array(), new stdClass, "will_fail") as $bad_input) {
            list($success, $result) = presto_exec($bad_input);
            $this->assertFalse($success);
            $this->assertInternalType("array", $result);
            $this->assertEquals(405, $result["http"]["errno"]);
        }
        
        list($success, $result) = presto_exec("presto_will_fail");
        $this->assertFalse($success);
        $this->assertInternalType("array", $result);
        $this->assertEquals(404, $result["http"]["errno"]);
        
        list($success, $result) = presto_exec("presto_get_demo_echo", array("echo" => "hello"));
        $this->assertTrue($success);
        $this->assertEquals("hello", $result);
        
        list($success, $result) = presto_exec("presto_get_demo_time");
        $this->assertTrue($success);
        $this->assertInternalType("string", $result);
        
        list($func, $vars) = presto_route("GET", "/demo/echo/");
        list($success, $result) = presto_exec($func, $vars);
        $this->assertTrue($success);
        $this->assertEquals("empty", $result);
        
        list($func, $vars) = presto_route("GET", "/demo/echo/echo/hello");
        list($success, $result) = presto_exec($func, $vars);
        $this->assertTrue($success);
        $this->assertEquals("hello", $result);
    }
    
    public function testEncode()
    {
        list($func) = presto_route("GET", "/demo/http-error/");
        list($body, $headers) = presto_encode($func);
        $this->assertContains("HTTP/1.0 599 Failboat", $headers);
        $response = @json_decode($body, true);
        $this->assertInternalType("array", $response);
        $this->assertArrayHasKey("result", $response);
        $this->assertEquals("false", $response["success"]);
        $this->assertEquals(array("error" => "Failboat", "errno" => 599), $response["result"]);
        
        // json
        list($func, $vars) = presto_route("GET", "/demo/echo/");
        list($body, $headers) = presto_encode($func, $vars, "json");
        $this->assertContains("Content-type: application/json", $headers);
        $response = @json_decode($body, true);
        $this->assertInternalType("array", $response);
        $this->assertArrayHasKey("result", $response);
        $this->assertEquals("true", $response["success"]);
        $this->assertEquals("empty", $response["result"]);
        
        list($func, $vars) = presto_route("GET", "/demo/echo.json");
        list($body, $headers) = presto_encode($func, $vars);
        $this->assertContains("Content-type: application/json", $headers);
        $response = @json_decode($body, true);
        $this->assertInternalType("array", $response);
        $this->assertArrayHasKey("result", $response);
        $this->assertEquals("true", $response["success"]);
        $this->assertEquals("empty", $response["result"]);
        
        // xml
        list($func, $vars) = presto_route("GET", "/demo/echo/");
        list($body, $headers) = presto_encode($func, $vars, "xml");
        $this->assertContains("Content-type: text/xml", $headers);
        $this->assertTag(array("tag" => "response", "child" => array("tag" => "success", "content" => "true")), $body);
        $this->assertTag(array("tag" => "response", "child" => array("tag" => "result", "content" => "empty")), $body);
        
        list($func, $vars) = presto_route("GET", "/demo/echo.xml");
        list($body, $headers) = presto_encode($func, $vars);
        $this->assertContains("Content-type: text/xml", $headers);
        $this->assertTag(array("tag" => "response", "child" => array("tag" => "success", "content" => "true")), $body);
        $this->assertTag(array("tag" => "response", "child" => array("tag" => "result", "content" => "empty")), $body);
        
        // jsonp
        list($func, $vars) = presto_route("GET", "/demo/echo/");
        $callback = "test";
        list($body, $headers) = presto_encode($func, array_merge(array("callback" => $callback), $vars), "js");
        $this->assertContains("Content-type: text/javascript", $headers);
        $this->assertStringStartsWith($callback . "(", $body);

        list($func, $vars) = presto_route("GET", "/demo/echo.js");
        $callback = "test";
        list($body, $headers) = presto_encode($func, array_merge(array("callback" => $callback), $vars));
        $this->assertContains("Content-type: text/javascript", $headers);
        $this->assertStringStartsWith($callback . "(", $body);
    }
}