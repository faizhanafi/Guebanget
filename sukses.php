<?php
// sukses.php
$order_id = $_GET['order_id'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pembayaran Berhasil - STIFIn</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #fff5f5, #ffe6e6);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        .success-card {
            background: white;
            border-radius: 40px;
            padding: 50px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 30px 60px rgba(178,34,34,0.15);
            text-align: center;
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: #b22222;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            margin: 0 auto 30px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(178,34,34,0.7);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(178,34,34,0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(178,34,34,0);
            }
        }
        h1 {
            color: #b22222;
            font-size: 2rem;
            margin-bottom: 20px;
        }
        p {
            color: #64748b;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .order-id {
            background: #fff5f5;
            padding: 15px;
            border-radius: 20px;
            margin: 20px 0;
            font-weight: 600;
            color: #b22222;
            border: 2px dashed #b22222;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            background: #b22222;
            color: white;
            padding: 15px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn:hover {
            background: #8b1a1a;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(178,34,34,0.2);
        }
        .btn-outline {
            background: white;
            color: #b22222;
            border: 2px solid #b22222;
        }
        .btn-outline:hover {
            background: #b22222;
            color: white;
        }
        .whatsapp-btn {
            background: #25D366;
        }
        .whatsapp-btn:hover {
            background: #128C7E;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        
        <h1>Pembayaran Berhasil!</h1>
        
        <p>Terima kasih telah melakukan booking Tes STIFIn.<br>
        Tim promotor kami akan segera menghubungi Anda untuk konfirmasi jadwal.</p>
        
        <?php if ($order_id): ?>
        <div class="order-id">
            Order ID: <strong><?php echo htmlspecialchars($order_id); ?></strong>
        </div>
        <?php endif; ?>
        
        <div class="btn-group">
            <a href="cek-status.php?order_id=<?php echo urlencode($order_id); ?>" class="btn btn-outline">
                <i class="fas fa-search"></i> Cek Status
            </a>
            <!-- Modified WhatsApp link using wa.link shortener with fixed number 085782959429 -->
            <a href="https://wa.link/p70i6w" class="btn whatsapp-btn" target="_blank">
                <i class="fab fa-whatsapp"></i> Hubungi Promotor
            </a>
            <a href="index.html" class="btn">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>