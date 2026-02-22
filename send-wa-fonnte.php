<?php
// send-wa-fonnte.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Tidak ada data']);
    exit;
}

// ========== KONFIGURASI FONNTE ==========
// GANTI DENGAN API KEY ANDA DARI DASHBOARD FONNTE
$api_key = 'CFfRBpYZhEYJ55eaR3ZE'; 

$target = $input['promotorWA'];
$message = $input['message'];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'https://api.fonnte.com/send',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => [
        'target' => $target,
        'message' => $message,
        'countryCode' => '62',
        'delay' => '0',
        'typing' => false
    ],
    CURLOPT_HTTPHEADER => [
        'Authorization: ' . $api_key
    ],
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

$result = json_decode($response, true);

if ($http_code == 200 && isset($result['status']) && $result['status'] == true) {
    echo json_encode(['success' => true, 'response' => $result]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $result['reason'] ?? 'Gagal kirim WA',
        'http_code' => $http_code
    ]);
}
?>