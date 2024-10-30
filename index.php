<?php

require 'autoload.php';
use App\OrderService;

$orderService = new OrderService();

try {
    $event_id = 123;
    $event_date = '2024-11-15';
    
    $tickets = [
        ['type_name' => 'взрослый', 'price' => 100.00, 'quantity' => 2],
        ['type_name' => 'детский', 'price' => 50.00, 'quantity' => 3],
        ['type_name' => 'льготный', 'price' => 80.00, 'quantity' => 1],
        ['type_name' => 'групповой', 'price' => 75.00, 'quantity' => 4],
    ]; 
    
    // После выполнения данного заказа должно создаться 10 баркодов
    $result = $orderService->createOrder($event_id, $event_date, $tickets);
    
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}