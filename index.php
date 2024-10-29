<?php

include 'autoload.php';

use App\Routers\OrderRouter;

$url = $_SERVER['REQUEST_URI'];

$routes = [
    'order' => OrderRouter::class,
];

foreach ($routes as $key => $value) {
    if ($res = $value::route($url)) {
        call_user_func_array([$res['controller'], $res['action']], $res['args']);
        break;
    }
}

http_response_code(404);
echo "404 Not Found";