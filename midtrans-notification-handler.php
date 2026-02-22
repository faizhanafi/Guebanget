<?php
// midtrans-notification-handler.php
require_once 'config.php';

header('Content-Type: application/json');

// Ambil notifikasi dari Midtrans
$notification = json_decode(file_get_contents('php://input'), true);

if (!$notification) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid notification']);
    exit;
}

// Verifikasi signature (untuk keamanan)
$order_id = $notification['order_id'];
$status_code = $notification['status_code'];
$gross_amount = $notification['gross_amount'];
$signature_key = $notification['signature_key'];

// Buat signature untuk verifikasi
$signature = hash('sha512', $order_id . $status_code . $gross_amount . MIDTRANS_SERVER_KEY_SANDBOX);

if ($signature !== $signature_key) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Proses notifikasi berdasarkan status
$transaction_status = $notification['transaction_status'];
$transaction_id = $notification['transaction_id'];
$payment_type = $notification['payment_type'];
$transaction_time = $notification['transaction_time'];

// Update status di database
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Cari booking berdasarkan order_id
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($booking) {
        // Update status berdasarkan notifikasi
        $new_status = '';
        switch ($transaction_status) {
            case 'capture':
            case 'settlement':
                $new_status = 'paid';
                break;
            case 'pending':
                $new_status = 'pending';
                break;
            case 'deny':
            case 'expire':
            case 'cancel':
                $new_status = 'failed';
                break;
            default:
                $new_status = 'initiated';
        }
        
        // Update booking status
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE order_id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        // Simpan detail payment
        $stmt = $pdo->prepare("
            INSERT INTO payments 
            (booking_id, order_id, transaction_id, payment_type, gross_amount, transaction_time, transaction_status, raw_response) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $booking['id'],
            $order_id,
            $transaction_id,
            $payment_type,
            $gross_amount,
            $transaction_time,
            $transaction_status,
            json_encode($notification)
        ]);
        
        // Kirim notifikasi ke promotor via WhatsApp
        $message = "🔔 *Update Status Pembayaran*\n\n";
        $message .= "Order ID: $order_id\n";
        $message .= "Status: " . strtoupper($new_status) . "\n";
        $message .= "Nama: " . $booking['nama'] . "\n";
        $message .= "Paket: " . $booking['paket'] . "\n";
        $message .= "Total: Rp " . number_format($booking['total_harga']) . "\n\n";
        $message .= "Lihat detail: " . (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . "/admin.php";
        
        // Kirim notifikasi ke WhatsApp menggunakan link (untuk dibuka manual oleh sistem)
        // Catatan: Untuk notifikasi otomatis, Anda memerlukan API WhatsApp Business atau layanan seperti Fonnte, Wablas, dll.
        // Ini hanya akan membuat link yang bisa dibuka di browser
        $whatsapp_link = "https://wa.link/p70i6w?text=" . urlencode($message);
        
        // Opsional: Jika ingin mengirim notifikasi otomatis, gunakan API pihak ketiga
        // Contoh menggunakan file_get_contents untuk membuka link (tidak direkomendasikan untuk production)
        // file_get_contents($whatsapp_link);
        
        // Atau simpan link ke log untuk dibuka manual
        error_log("WhatsApp notification link: " . $whatsapp_link);
    }
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>