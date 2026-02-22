<?php
// send-notification.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'No input data']);
    exit;
}

$response = [
    'success' => true,
    'wa_sent' => false,
    'email_promotor_sent' => false,
    'email_customer_sent' => false
];

// ========== 1. KIRIM WHATSAPP KE PROMOTOR (via FONNTE) ==========
// Ganti dengan API key FONNTE Anda (daftar di fonnte.com)
$fonnte_api_key = 'YOUR_FONNTE_API_KEY'; // Dapatkan dari fonnte.com

$wa_promotor = $input['promotorWA']; // 6285782959429
$wa_message = $input['waMessage'];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'https://api.fonnte.com/send',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => [
        'target' => $wa_promotor,
        'message' => $wa_message,
        'countryCode' => '62', // kode negara Indonesia
    ],
    CURLOPT_HTTPHEADER => [
        'Authorization: ' . $fonnte_api_key
    ],
]);

$wa_response = curl_exec($curl);
$wa_http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($wa_http_code == 200) {
    $response['wa_sent'] = true;
}

// ========== 2. KIRIM EMAIL via PHP MAIL (lebih reliable) ==========
$to_promotor = $input['promotorEmail'];
$to_customer = $input['customerEmail'];
$subject = $input['subject'];
$message = $input['emailMessage'];
$headers = "From: STIFIn Official <noreply@stifin.com>\r\n";
$headers .= "Reply-To: " . $input['promotorEmail'] . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

// Kirim ke promotor
if (mail($to_promotor, $subject . ' - Notifikasi Promotor', nl2br($message), $headers)) {
    $response['email_promotor_sent'] = true;
}

// Kirim ke customer
if (mail($to_customer, $subject . ' - Konfirmasi Booking', nl2br($message), $headers)) {
    $response['email_customer_sent'] = true;
}

// ========== 3. SIMPAN KE DATABASE (opsional) ==========
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications 
        (order_id, wa_sent, email_promotor_sent, email_customer_sent, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $input['orderId'],
        $response['wa_sent'] ? 1 : 0,
        $response['email_promotor_sent'] ? 1 : 0,
        $response['email_customer_sent'] ? 1 : 0
    ]);
    
} catch (Exception $e) {
    // Abaikan error database
}

echo json_encode($response);
?>