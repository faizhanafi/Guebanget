<?php
// cek-status.php
require_once 'config.php';

$order_id = $_GET['order_id'] ?? '';

if (!$order_id) {
    die('Order ID tidak ditemukan');
}

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        die('Booking tidak ditemukan');
    }
    
    // Ambil detail payment terakhir
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$order_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Status Pembayaran STIFIn</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body {
                font-family: 'Nunito', sans-serif;
                background: linear-gradient(135deg, #fff5f5, #ffe6e6);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .card {
                background: white;
                border-radius: 40px;
                padding: 40px;
                max-width: 600px;
                width: 100%;
                box-shadow: 0 20px 40px rgba(178,34,34,0.1);
                text-align: center;
            }
            .status-icon {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                font-size: 40px;
            }
            .status-paid {
                background: #d4edda;
                color: #155724;
            }
            .status-pending {
                background: #fff3cd;
                color: #856404;
            }
            .status-failed {
                background: #f8d7da;
                color: #721c24;
            }
            h1 {
                color: #b22222;
                margin-bottom: 20px;
            }
            .detail {
                background: #fff5f5;
                border-radius: 20px;
                padding: 20px;
                margin: 20px 0;
                text-align: left;
            }
            .detail-row {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px solid #ffcdcd;
            }
            .detail-row:last-child {
                border-bottom: none;
            }
            .label {
                font-weight: 600;
                color: #64748b;
            }
            .value {
                color: #b22222;
                font-weight: 700;
            }
            .btn {
                background: #b22222;
                color: white;
                border: none;
                padding: 15px 30px;
                border-radius: 30px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                margin-top: 20px;
                transition: all 0.3s ease;
            }
            .btn:hover {
                background: #8b1a1a;
                transform: translateY(-3px);
                box-shadow: 0 10px 20px rgba(178,34,34,0.2);
            }
        </style>
    </head>
    <body>
        <div class="card">
            <?php
            $status_class = '';
            $status_text = '';
            $status_icon = '';
            
            switch ($booking['status']) {
                case 'paid':
                    $status_class = 'status-paid';
                    $status_text = 'Pembayaran Berhasil';
                    $status_icon = '✓';
                    break;
                case 'pending':
                    $status_class = 'status-pending';
                    $status_text = 'Menunggu Pembayaran';
                    $status_icon = '⏳';
                    break;
                case 'failed':
                case 'cancelled':
                    $status_class = 'status-failed';
                    $status_text = 'Pembayaran Gagal';
                    $status_icon = '✗';
                    break;
                default:
                    $status_class = 'status-pending';
                    $status_text = 'Menunggu Pembayaran';
                    $status_icon = '⏳';
            }
            ?>
            
            <div class="status-icon <?php echo $status_class; ?>">
                <?php echo $status_icon; ?>
            </div>
            
            <h1><?php echo $status_text; ?></h1>
            
            <div class="detail">
                <div class="detail-row">
                    <span class="label">Order ID</span>
                    <span class="value"><?php echo htmlspecialchars($booking['order_id']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Nama</span>
                    <span class="value"><?php echo htmlspecialchars($booking['nama']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Paket</span>
                    <span class="value"><?php echo htmlspecialchars($booking['paket']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Jumlah Orang</span>
                    <span class="value"><?php echo $booking['jumlah_orang']; ?> orang</span>
                </div>
                <div class="detail-row">
                    <span class="label">Total</span>
                    <span class="value">Rp <?php echo number_format($booking['total_harga']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">DP Dibayar</span>
                    <span class="value">Rp <?php echo number_format($booking['dp_amount']); ?></span>
                </div>
                <?php if ($payment): ?>
                <div class="detail-row">
                    <span class="label">Metode</span>
                    <span class="value"><?php echo htmlspecialchars($payment['payment_type']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Waktu</span>
                    <span class="value"><?php echo date('d M Y H:i', strtotime($payment['transaction_time'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($booking['status'] === 'paid'): ?>
                <p>Terima kasih! Promotor akan segera menghubungi Anda untuk jadwal tes.</p>
                <!-- Modified WhatsApp link using wa.link shortener with fixed number 085782959429 -->
                <a href="https://wa.link/p70i6w" class="btn" target="_blank">
                    <i class="fab fa-whatsapp"></i> Hubungi Promotor
                </a>
            <?php elseif ($booking['status'] === 'pending'): ?>
                <p>Silakan selesaikan pembayaran Anda. DP Rp 100.000</p>
                <button class="btn" onclick="window.location.reload()">Refresh Status</button>
            <?php else: ?>
                <p>Pembayaran gagal. Silakan coba lagi.</p>
                <a href="index.html#harga" class="btn">Booking Ulang</a>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>