<?php
// midtrans-create-transaction.php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Konfigurasi Midtrans dari file config
$server_key = MIDTRANS_IS_PRODUCTION ? MIDTRANS_SERVER_KEY_PRODUCTION : MIDTRANS_SERVER_KEY_SANDBOX;
$is_production = MIDTRANS_IS_PRODUCTION;

// Ambil data dari request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Data customer dari form booking
$customer = $input['customer'];
$order_id = $input['order_id'];
$amount = $input['amount'];
$items = $input['items'];

// Parameter untuk Midtrans
$params = [
    'transaction_details' => [
        'order_id' => $order_id,
        'gross_amount' => $amount
    ],
    'customer_details' => [
        'first_name' => $customer['nama'],
        'email' => $customer['email'],
        'phone' => $customer['noWa'],
        'billing_address' => [
            'first_name' => $customer['nama'],
            'address' => $customer['alamat']
        ]
    ],
    'item_details' => $items,
    'enabled_payments' => [
        "credit_card",
        "mandiri_clickpay",
        "bca_klikbca",
        "bca_klikpay",
        "bri_epay",
        "echannel",
        "permata_va",
        "bca_va",
        "bni_va",
        "bri_va",
        "other_va",
        "gopay",
        "indomaret",
        "danamon_online",
        "akulaku",
        "shopeepay"
    ],
    'credit_card' => [
        'secure' => true
    ]
];

// Encode credentials untuk Basic Auth
$auth = base64_encode($server_key . ':');

// Tentukan URL berdasarkan environment
$url = $is_production 
    ? 'https://app.midtrans.com/snap/v1/transactions' 
    : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

// Inisialisasi CURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . $auth
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// Eksekusi CURL
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Cek error
if (curl_error($ch)) {
    echo json_encode(['error' => 'CURL Error: ' . curl_error($ch)]);
    exit;
}

curl_close($ch);

// Decode response
$result = json_decode($response, true);

// Cek response dari Midtrans
if ($http_code == 201 && isset($result['token'])) {
    // Sukses mendapatkan token
    echo json_encode([
        'success' => true,
        'token' => $result['token'],
        'redirect_url' => $result['redirect_url']
    ]);
} else {
    // Error
    echo json_encode([
        'success' => false,
        'error' => $result['error_messages'] ?? 'Unknown error occurred',
        'http_code' => $http_code
    ]);
}
?>