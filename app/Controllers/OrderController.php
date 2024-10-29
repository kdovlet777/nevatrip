<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrderModel;

class OrderController extends BaseController
{
    protected $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel(); // Используем модель без параметров
    }

    public function actionList()
    {
        $orders = $this->orderModel->getAllOrders();
        return $orders; // Возвращаем массив всех заказов
    }
    
    public function createOrder($event_id, $event_date, $ticket_adult_price, $ticket_adult_quantity, $ticket_kid_price, $ticket_kid_quantity)
    {
        $barcode = $this->generateUniqueBarcode();

        if ($this->mockBookOrder($event_id, $event_date, $ticket_adult_price, $ticket_adult_quantity, $ticket_kid_price, $ticket_kid_quantity, $barcode)) {
            if ($this->mockConfirmOrder($barcode)) {
                $equal_price = ($ticket_adult_price * $ticket_adult_quantity) + ($ticket_kid_price * $ticket_kid_quantity);
                $this->orderModel->saveOrder($event_id, $event_date, $ticket_adult_price, $ticket_adult_quantity, $ticket_kid_price, $ticket_kid_quantity, $barcode, $equal_price);
                return "Order successfully created and approved.";
            }
        }

        return "Order could not be processed.";
    }

    private function mockBookOrder($event_id, $event_date, $ticket_adult_price, $ticket_adult_quantity, $ticket_kid_price, $ticket_kid_quantity, &$barcode)
    {
        // Проверяем, существует ли баркод перед тем, как делать бронирование
        if ($this->orderModel->barcodeExists($barcode)) {
            return ['error' => 'barcode already exists'];
        }

        // Мокируемый ответ: случайный выбор между успешным бронированием и ошибкой "barcode already exists"
        $responses = [
            ['message' => 'order successfully booked'],
            ['error' => 'barcode already exists']
        ];

        $response = $responses[array_rand($responses)]; // случайный выбор ответа

        if (isset($response['message']) && $response['message'] === 'order successfully booked') {
            return true;
        }

        // Если произошла ошибка, генерируем новый баркод
        if (isset($response['error']) && $response['error'] === 'barcode already exists') {
            $barcode = $this->generateUniqueBarcode();
        }

        return false;
    }

    private function mockConfirmOrder($barcode)
    {
        // Мокируемый ответ на подтверждение: случайный выбор между успешным подтверждением и различными ошибками
        $responses = [
            ['message' => 'order successfully approved'],
            ['error' => 'event cancelled'],
            ['error' => 'no tickets'],
            ['error' => 'no seats'],
            ['error' => 'fan removed']
        ];

        $response = $responses[array_rand($responses)]; // случайный выбор ответа

        if (isset($response['message']) && $response['message'] === 'order successfully approved') {
            return true;
        }

        if (isset($response['error'])) {
            throw new Exception("Failed to approve order: " . $response['error']);
        }

        return false;
    }
}