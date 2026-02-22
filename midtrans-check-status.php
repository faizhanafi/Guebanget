<?php
// midtrans-check-status.php
header('Content-Type: application/json');

$server_key = "SB-Mid-server-bkQa-8I-k8eAmY56nFwOq4Gp";
$order_id = $_GET['order_id'] ?? '';

if (!$order_id) {
    echo json_encode(['error' => 'Order ID required']);
    exit;
}

$auth = base64_encode($server_key . ':');
$url = "https://api.sandbox.midtrans.com/v2/" . $order_id . "/status";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Basic ' . $auth
]);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>