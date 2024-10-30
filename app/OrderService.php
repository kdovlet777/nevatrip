<?php

namespace App;

use PDO;
use Exception;

class OrderService
{
    private $db;

    /**
     * OrderService constructor.
     * Инициализирует соединение с базой данных.
     */
    public function __construct()
    {
        $this->db = DB::connection();
    }

    /**
     * Создает новый заказ.
     *
     * @param int $event_id ID события.
     * @param string $event_date Дата и время события.
     * @param array $tickets Массив билетов с типами и количеством.
     * @return array Сообщение об успешном создании заказа или ошибка.
     * @throws Exception Если возникла ошибка при создании заказа.
     */
    public function createOrder($event_id, $event_date, $tickets)
    {
        try {
            // Пытаемся забронировать билет
            $barcode = $this->attemptBooking($event_id, $event_date, $tickets);
            // Подтверждаем заказ
            $this->confirmOrder($barcode);
            // Сохраняем заказ в базе данных
            $this->saveOrderToDB($event_id, $event_date, $tickets, $barcode);
            return ["message" => "Order successfully created and confirmed."];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Пытается забронировать билет с уникальным баркодом.
     *
     * @param int $event_id ID события.
     * @param string $event_date Дата и время события.
     * @param array $tickets Массив билетов с типами и количеством.
     * @return string Уникальный баркод для заказа.
     * @throws Exception Если не удалось забронировать заказ.
     */
    private function attemptBooking($event_id, $event_date, $tickets)
    {
        $maxAttempts = 5; // Максимальное количество попыток генерации штрих-кода
        for ($i = 0; $i < $maxAttempts; $i++) {
            $barcode = $this->generateBarcode(); // Генерация штрих-кода

            // Проверка уникальности штрих-кода в базе данных
            if (!$this->isBarcodeUnique($barcode)) {
                continue; // Если не уникален, пробуем снова
            }

            // Отправляем запрос на бронирование
            $response = $this->mockApiBook("https://api.site.com/book", [
                'event_id' => $event_id,
                'event_date' => $event_date,
                'tickets' => $tickets,
                'barcode' => $barcode
            ]);

            if (isset($response['message'])) {
                return $barcode; // Возвращаем уникальный штрих-код
            } elseif (isset($response['error'])) {
                continue; // Повторяем попытку, если есть ошибка
            } else {
                throw new Exception("Unexpected booking error: " . $response['error']);
            }
        }

        throw new Exception("Failed to book order after multiple attempts.");
    }

    /**
     * Проверяет уникальность штрих-кода в базе данных.
     *
     * Этот метод принимает штрих-код в качестве аргумента и выполняет SQL-запрос к таблице заказов,
     * чтобы определить, существует ли уже этот штрих-код. Если штрих-код не найден, метод
     * возвращает true, указывая на то, что штрих-код уникален и может быть использован для нового заказа.
     * Если штрих-код уже существует, метод возвращает false.
     *
     * @param string $barcode Штрих-код, который необходимо проверить на уникальность.
     *                        Должен быть строкой, содержащей уникальные символы, которые
     *                        будут использоваться для идентификации заказа.
     *
     * @return bool Возвращает true, если штрих-код уникален (то есть не существует в базе данных),
     *              или false, если штрих-код уже присутствует в таблице заказов.
     */
    private function isBarcodeUnique($barcode)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE barcode = :barcode");
        $stmt->execute([':barcode' => $barcode]);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Подтверждает заказ по баркоду.
     *
     * @param string $barcode Уникальный баркод заказа.
     * @throws Exception Если подтверждение заказа не удалось.
     */
    private function confirmOrder($barcode)
    {
        // Мокаем API запрос на подтверждение заказа
        $response = $this->mockApiApprove("https://api.site.com/approve", ['barcode' => $barcode]);

        if (isset($response['error'])) {
            throw new Exception("Order approval failed: " . $response['error']);
        } elseif ($response['message'] !== 'order successfully approved') {
            throw new Exception("Unexpected response from approval API.");
        }
    }

    /**
     * Сохраняет заказ в базе данных.
     *
     * @param int $event_id ID события.
     * @param string $event_date Дата и время события.
     * @param array $tickets Массив билетов с типами и количеством.
     * @param string $barcode Уникальный баркод заказа.
     * @throws Exception Если произошла ошибка при сохранении в БД.
     */
    private function saveOrderToDB($event_id, $event_date, $tickets, $barcode)
    {
        $this->db->beginTransaction(); // Начинаем транзакцию
        
        try {
            // Сохранение заказа в таблице orders
            $stmt = $this->db->prepare("INSERT INTO orders (event_id, event_date) VALUES (:event_id, :event_date)");
            $stmt->execute([':event_id' => $event_id, ':event_date' => $event_date]);
            $orderId = $this->db->lastInsertId(); // Получаем ID последнего вставленного заказа

            // Добавление билетов в таблицу tickets
            foreach ($tickets as $ticket) {
                $typeId = $this->getTicketTypeId($ticket['type_name'], $ticket['price']);
                $this->insertTickets($orderId, $typeId, $ticket['quantity']);
            }

            $this->db->commit(); // Завершаем транзакцию
        } catch (Exception $e) {
            $this->db->rollBack(); // Откатываем транзакцию в случае ошибки
            throw $e;
        }
    }

    /**
     * Вставляет билеты в таблицу tickets.
     *
     * @param int $orderId ID заказа.
     * @param int $typeId ID типа билета.
     * @param int $quantity Количество билетов.
     */
    private function insertTickets($orderId, $typeId, $quantity)
    {
        $stmt = $this->db->prepare("INSERT INTO tickets (order_id, type_id, barcode) VALUES (:order_id, :type_id, :barcode)");
        for ($i = 0; $i < $quantity; $i++) {
            $stmt->execute([
                ':order_id' => $orderId,
                ':type_id' => $typeId,
                ':barcode' => $this->generateBarcode() // Генерация уникального баркода для каждого билета
            ]);
        }
    }

    /**
     * Получает ID типа билета по его названию и цене. Если тип не существует, создаёт новый.
     *
     * @param string $typeName Название типа билета.
     * @param int $price Цена типа билета.
     * @return int ID типа билета.
     */
    private function getTicketTypeId($typeName, $price)
    {
        $stmt = $this->db->prepare("SELECT id FROM ticket_types WHERE type_name = :type_name AND price = :price");
        $stmt->execute([':type_name' => $typeName, ':price' => $price]);
        $typeId = $stmt->fetchColumn();

        if (!$typeId) {
            // Если тип билета не найден, создаем новый
            $stmt = $this->db->prepare("INSERT INTO ticket_types (type_name, price) VALUES (:type_name, :price)");
            $stmt->execute([':type_name' => $typeName, ':price' => $price]);
            $typeId = $this->db->lastInsertId();
        }

        return $typeId; // Возвращаем ID типа билета
    }

    /**
     * Генерирует уникальный баркод.
     *
     * @return string Уникальный баркод.
     */
    private function generateBarcode()
    {
        return strtoupper(bin2hex(random_bytes(8))); // Генерация случайной строки длиной 16 символов
    }

    /**
     * Мокаем API запрос на бронирование.
     *
     * @param string $url URL для запроса.
     * @param array $data Данные запроса.
     * @return array Ответ от API.
     */
    private function mockApiBook($url, $data)
    {
        $responses = [
            ["message" => "order successfully booked"],
            ["error" => "barcode already exists"]
        ];

        return $responses[array_rand($responses)]; // Возвращаем случайный ответ
    }

    /**
     * Мокаем API запрос на подтверждение.
     *
     * @param string $url URL для запроса.
     * @param array $data Данные запроса.
     * @return array Ответ от API.
     */
    private function mockApiApprove($url, $data)
    {
        $responses = [
            ["message" => "order successfully approved"],
            ["error" => "event cancelled"],
            ["error" => "no tickets"],
            ["error" => "no seats"],
            ["error" => "fan removed"]
        ];

        return $responses[array_rand($responses)]; // Возвращаем случайный ответ
    }
}