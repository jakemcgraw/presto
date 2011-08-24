<?php

require_once "PHPUnit/Framework/TestCase.php";
require_once "../app/presto.php";

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
        $this->assertEquals(array("_filetype" => "js"), $vars);
        
        list($func, $vars) = presto_route("GET", "/foo/bar/hello.js");
        $this->assertEquals("presto_get_foo_bar", $func);
        $this->assertEquals(array("hello.js", "_filetype" => "js"), $vars);
        
        list($func, $vars) = presto_route("GET", "/foo/bar/hello/world.js");
        $this->assertEquals("presto_get_foo_bar", $func);
        $this->assertEquals(array("hello" => "world.js", "_filetype" => "js"), $vars);
    }
}