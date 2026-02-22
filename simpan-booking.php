<?php
// simpan-booking.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ambil data dari request
$input = json_decode(file_get_contents('php://input'), true);

// Log untuk debugging
error_log("Data masuk: " . print_r($input, true));

if (!$input) {
    echo json_encode([
        'success' => false, 
        'error' => 'Tidak ada data yang diterima',
        'raw_input' => file_get_contents('php://input')
    ]);
    exit;
}

// Validasi data yang diperlukan
$required = ['order_id', 'nama', 'tanggal_lahir', 'email', 'no_wa', 'alamat', 'paket', 'jumlahOrang', 'total', 'dp', 'sisa'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        echo json_encode([
            'success' => false, 
            'error' => "Field $field harus diisi"
        ]);
        exit;
    }
}

// VALIDASI LOGIKA PAKET
$paket = $input['paket'];
$jumlahOrang = intval($input['jumlahOrang']);

// Validasi paket Personal/Pasangan
if ($paket === 'Personal/Pasangan' && ($jumlahOrang < 1 || $jumlahOrang > 2)) {
    echo json_encode([
        'success' => false,
        'error' => 'Untuk paket Personal/Pasangan, jumlah orang minimal 1 dan maksimal 2 orang'
    ]);
    exit;
}

// Validasi paket Keluarga/Kelompok
if ($paket === 'Keluarga/Kelompok' && ($jumlahOrang < 3 || $jumlahOrang > 9)) {
    echo json_encode([
        'success' => false,
        'error' => 'Untuk paket Keluarga/Kelompok, jumlah orang minimal 3 dan maksimal 9 orang'
    ]);
    exit;
}

// Validasi paket Sekolah/Instansi
if ($paket === 'Sekolah/Instansi' && $jumlahOrang < 10) {
    echo json_encode([
        'success' => false,
        'error' => 'Untuk paket Sekolah/Instansi, jumlah orang minimal 10 orang'
    ]);
    exit;
}

// Validasi format tanggal lahir
$tanggalLahir = $input['tanggal_lahir'];
if (!preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $tanggalLahir)) {
    echo json_encode([
        'success' => false,
        'error' => 'Format tanggal lahir tidak valid'
    ]);
    exit;
}

try {
    // Koneksi ke database
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Cek apakah order_id sudah ada
    $check = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE order_id = ?");
    $check->execute([$input['order_id']]);
    if ($check->fetchColumn() > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Order ID sudah ada'
        ]);
        exit;
    }
    
    // Simpan ke database - PERHATIKAN: kolom tanggal_lahir ditambahkan
    $stmt = $pdo->prepare("
        INSERT INTO bookings 
        (order_id, nama, tanggal_lahir, email, no_wa, alamat, paket, jumlah_orang, total_harga, dp_amount, sisa_amount, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'initiated')
    ");
    
    $result = $stmt->execute([
        $input['order_id'],
        $input['nama'],
        $tanggalLahir, // FIELD BARU
        $input['email'],
        $input['no_wa'],
        $input['alamat'],
        $input['paket'],
        $input['jumlahOrang'],
        $input['total'],
        $input['dp'],
        $input['sisa']
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Data berhasil disimpan',
            'order_id' => $input['order_id']
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'Gagal menyimpan data'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>