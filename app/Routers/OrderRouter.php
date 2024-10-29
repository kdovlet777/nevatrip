<?php

namespace App\Routers;

use App\Controllers\OrderController;

class OrderRouter
{
	public static function route($url)
	{
        $requestMethod = $_SERVER['REQUEST_METHOD'];
		$controller = false;
		if ($url == "/") {
			$controller = new OrderController;
			$action = 'actionList';
			$args = [1];
		} elseif ($url == "/create-order" && $requestMethod == "POST") {
            $controller = new OrderController;
            $action = 'createOrder';

            $event_id = $_POST['event_id'] ?? null;
            $event_date = $_POST['event_date'] ?? null;
            $ticket_adult_price = $_POST['ticket_adult_price'] ?? null;
            $ticket_adult_quantity = $_POST['ticket_adult_quantity'] ?? null;
            $ticket_kid_price = $_POST['ticket_kid_price'] ?? null;
            $ticket_kid_quantity = $_POST['ticket_kid_quantity'] ?? null;

            $args = [
                $event_id,
                $event_date,
                $ticket_adult_price,
                $ticket_adult_quantity,
                $ticket_kid_price,
                $ticket_kid_quantity
            ];
        }

        if ($controller) {
            return array(
                "controller" => $controller,
                "action" => $action,
                "args" => $args
            );
        } return false;
    }
}