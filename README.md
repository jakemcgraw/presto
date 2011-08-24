# presto: A micro framework for mapping HTTP requests to PHP function calls

## Features

### Pretty URLs

**GET /** maps to
```presto_get_index_index();```

**GET /foo** maps to

```presto_get_foo_index();```

**GET /foo/bar** maps to

```presto_get_foo_bar();```

**GET /foo/bar-foo** maps to

```presto_get_foo_barFoo();```

### URL Variables

**GET /foo/bar/12345** maps to

```presto_get_foo_bar(array("12345"));```

**GET /foo/bar/hello/world** maps to

```presto_get_foo_bar(array("hello" => "world"));```

**GET /foo/bar/hello/world/12345** maps to

```presto_get_foo_bar(array("hello" => "world", "12345"));```

### Filetype detection

**GET /foo/bar.json** maps to

```presto_get_foo_bar();```which outputs

```{"success":"true","result":...}```

**GET /foo/bar/hello.xml** maps to

```presto_get_foo_bar(array("hello"));```which outputs

```<?xml version="1.0"?><response><success>true</success><result>...</result></response>```

**GET /foo/bar/hello/world.js?callback=demo** maps to

```presto_get_foo_bar(array("hello" => "world", "callback" => "demo"));```which outputs

```demo( {"success":"true","result":...} );```

Currently supports JSON, XML and JSONP (requires callback parameter).

### HTTP Verbs

**POST /**

```presto_post_index_index();```

**PUT /**

```presto_put_index_index();```

**DELETE /**

```presto_delete_index_index();```

**HEAD /**

```presto_head_index_index();```
 
