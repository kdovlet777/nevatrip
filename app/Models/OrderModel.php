<?php

namespace App\Models;

use App\DB;
use PDO;

class OrderModel
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = DB::connection(); // Получаем соединение из DB
    }

    public function getAllOrders()
    {
        $stmt = $this->pdo->query("SELECT * FROM orders");
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Возвращаем все заказы как ассоциативный массив
    }

    public function barcodeExists($barcode)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders WHERE barcode = :barcode");
        $stmt->execute([':barcode' => $barcode]);
        return $stmt->fetchColumn() > 0; // Возвращаем true, если баркод существует
    }

    public function saveOrder($event_id, $event_date, $ticket_adult_price, $ticket_adult_quantity, $ticket_kid_price, $ticket_kid_quantity, $barcode, $equal_price)
    {
        $stmt = $this->pdo->prepare("INSERT INTO orders (event_id, event_date, ticket_adult_price, ticket_adult_quantity, ticket_kid_price, ticket_kid_quantity, barcode, equal_price, created) VALUES (:event_id, :event_date, :ticket_adult_price, :ticket_adult_quantity, :ticket_kid_price, :ticket_kid_quantity, :barcode, :equal_price, NOW())");
        $stmt->execute([
            ':event_id' => $event_id,
            ':event_date' => $event_date,
            ':ticket_adult_price' => $ticket_adult_price,
            ':ticket_adult_quantity' => $ticket_adult_quantity,
            ':ticket_kid_price' => $ticket_kid_price,
            ':ticket_kid_quantity' => $ticket_kid_quantity,
            ':barcode' => $barcode,
            ':equal_price' => $equal_price,
        ]);
    }
}