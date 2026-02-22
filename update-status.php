<?php
// update-status.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['order_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'error' => 'Data tidak lengkap']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $order_id = $input['order_id'];
    $status = $input['status'];
    $payment_id = $input['payment_id'] ?? null;
    $payment_method = $input['payment_method'] ?? null;
    
    // Update status di tabel bookings
    $stmt = $pdo->prepare("UPDATE bookings SET status = ?, payment_id = ?, payment_method = ? WHERE order_id = ?");
    $stmt->execute([$status, $payment_id, $payment_method, $order_id]);
    
    // Jika status paid, simpan juga ke tabel payments
    if ($status == 'paid' && isset($input['payment_id'])) {
        // Cari booking_id
        $booking = $pdo->prepare("SELECT id FROM bookings WHERE order_id = ?");
        $booking->execute([$order_id]);
        $booking_id = $booking->fetchColumn();
        
        if ($booking_id) {
            $stmt2 = $pdo->prepare("
                INSERT INTO payments 
                (booking_id, order_id, transaction_id, payment_type, gross_amount, transaction_status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt2->execute([
                $booking_id,
                $order_id,
                $input['payment_id'],
                $payment_method ?? 'unknown',
                $input['amount'] ?? 0,
                'settlement'
            ]);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Status updated']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>