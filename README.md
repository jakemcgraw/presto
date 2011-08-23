## mapd: Map HTTP requests to PHP functions

### Example

GET /foo/bar => mapd_get_foo_bar();

GET /foo/bar-spam => mapd_get_foo_barSpam();

POST /foo/bar => mapd_post_foo_bar($post_vars);
