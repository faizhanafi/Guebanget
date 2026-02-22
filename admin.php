<?php
// admin.php - Admin Panel Ringan untuk STIFIn dengan Fitur Export Excel
require_once 'config.php';

session_start();

// Simple authentication
$admin_password = 'admin123'; // Ganti sesuai keinginan

// Cek login
if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['password'])) {
        if ($_POST['password'] === $admin_password) {
            $_SESSION['admin_logged_in'] = true;
        } else {
            $login_error = 'Password salah!';
        }
    }
    
    if (!isset($_SESSION['admin_logged_in'])) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Login Admin - STIFIn</title>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: #b22222;
                    height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0;
                }
                .login-box {
                    background: white;
                    border-radius: 10px;
                    padding: 40px;
                    width: 350px;
                    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
                    text-align: center;
                }
                .login-box h1 {
                    color: #b22222;
                    margin-bottom: 30px;
                }
                .login-box input {
                    width: 100%;
                    padding: 12px;
                    margin-bottom: 15px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    box-sizing: border-box;
                }
                .login-box button {
                    background: #b22222;
                    color: white;
                    border: none;
                    padding: 12px 30px;
                    border-radius: 5px;
                    cursor: pointer;
                    width: 100%;
                    font-size: 16px;
                }
                .login-box button:hover {
                    background: #8b1a1a;
                }
                .error {
                    color: #dc2626;
                    margin-bottom: 15px;
                }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h1>🔐 Admin STIFIn</h1>
                <?php if (isset($login_error)): ?>
                    <div class="error">❌ <?php echo $login_error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Login</button>
                </form>
                <p style="margin-top: 20px; color: #666;">Password: <strong>admin123</strong></p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Koneksi database dengan PDO
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Hitung statistik
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
        'paid' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='paid'")->fetchColumn(),
        'pending' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn(),
        'failed' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('failed','cancelled')")->fetchColumn(),
        'initiated' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='initiated'")->fetchColumn(),
        'total_pendapatan' => $pdo->query("SELECT COALESCE(SUM(total_harga),0) FROM bookings WHERE status='paid'")->fetchColumn()
    ];
    
    // Ambil semua data booking untuk keperluan export
    $stmt_all = $pdo->query("
        SELECT b.*, 
               (SELECT GROUP_CONCAT(payment_type) FROM payments WHERE order_id = b.order_id) as payment_types
        FROM bookings b
        ORDER BY b.created_at DESC
    ");
    $all_bookings = $stmt_all->fetchAll();
    
    // Pagination untuk tampilan
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Hitung total halaman
    $total_rows = count($all_bookings);
    $total_pages = ceil($total_rows / $limit);
    
    // Ambil data untuk halaman saat ini
    $bookings = array_slice($all_bookings, $offset, $limit);
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// ========== HANDLE EXPORT EXCEL ==========
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Set header untuk download file Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="stifin_transactions_'.date('Y-m-d').'.xls"');
    header('Cache-Control: max-age=0');
    
    // Buat output Excel (format HTML table)
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Export Data STIFIn</title>';
    echo '<style>';
    echo 'th { background: #b22222; color: white; font-weight: bold; }';
    echo 'td, th { border: 1px solid #ccc; padding: 8px; }';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<h2>Data Transaksi STIFIn - ' . date('d/m/Y H:i') . '</h2>';
    echo '<table>';
    
    // Header kolom - TAMBAHKAN TANGGAL LAHIR
    echo '<tr>';
    echo '<th>No</th>';
    echo '<th>Waktu</th>';
    echo '<th>Tanggal Lahir</th>'; // KOLOM BARU
    echo '<th>Order ID</th>';
    echo '<th>Nama</th>';
    echo '<th>Email</th>';
    echo '<th>No. WA</th>';
    echo '<th>Alamat</th>';
    echo '<th>Paket</th>';
    echo '<th>Jumlah Orang</th>';
    echo '<th>Total</th>';
    echo '<th>DP</th>';
    echo '<th>Sisa</th>';
    echo '<th>Status</th>';
    echo '<th>Payment ID</th>';
    echo '<th>Metode</th>';
    echo '<th>Payment Types</th>';
    echo '</tr>';
    
    // Data
    $no = 1;
    foreach ($all_bookings as $b) {
        // Format status
        $status_text = '';
        switch ($b['status']) {
            case 'paid': $status_text = 'LUNAS'; break;
            case 'pending': $status_text = 'PENDING'; break;
            case 'failed': $status_text = 'GAGAL'; break;
            case 'cancelled': $status_text = 'BATAL'; break;
            default: $status_text = 'BARU';
        }
        
        // Format tanggal lahir
        $tanggal_lahir = isset($b['tanggal_lahir']) && $b['tanggal_lahir'] ? date('d/m/Y', strtotime($b['tanggal_lahir'])) : '-';
        
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . date('d/m/Y H:i', strtotime($b['created_at'])) . '</td>';
        echo '<td>' . $tanggal_lahir . '</td>'; // DATA BARU
        echo '<td>' . $b['order_id'] . '</td>';
        echo '<td>' . htmlspecialchars($b['nama']) . '</td>';
        echo '<td>' . $b['email'] . '</td>';
        echo '<td>' . $b['no_wa'] . '</td>';
        echo '<td>' . htmlspecialchars($b['alamat']) . '</td>';
        echo '<td>' . $b['paket'] . '</td>';
        echo '<td>' . $b['jumlah_orang'] . '</td>';
        echo '<td>' . $b['total_harga'] . '</td>';
        echo '<td>' . $b['dp_amount'] . '</td>';
        echo '<td>' . $b['sisa_amount'] . '</td>';
        echo '<td>' . $status_text . '</td>';
        echo '<td>' . ($b['payment_id'] ?? '-') . '</td>';
        echo '<td>' . ($b['payment_method'] ?? '-') . '</td>';
        echo '<td>' . ($b['payment_types'] ?? '-') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '<p><em>Dicetak pada: ' . date('d/m/Y H:i:s') . '</em></p>';
    echo '</body>';
    echo '</html>';
    exit;
}

// ========== HANDLE EXPORT CSV ==========
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Set header untuk download file CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="stifin_transactions_'.date('Y-m-d').'.csv"');
    
    // Buat file output
    $output = fopen('php://output', 'w');
    
    // Set header CSV (UTF-8 untuk support karakter Indonesia)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM untuk UTF-8
    
    // Header kolom - TAMBAHKAN TANGGAL LAHIR
    fputcsv($output, [
        'No', 'Waktu', 'Tanggal Lahir', 'Order ID', 'Nama', 'Email', 'No. WA', 'Alamat',
        'Paket', 'Jumlah Orang', 'Total', 'DP', 'Sisa', 'Status',
        'Payment ID', 'Metode', 'Payment Types'
    ]);
    
    // Data
    $no = 1;
    foreach ($all_bookings as $b) {
        // Format status
        $status_text = '';
        switch ($b['status']) {
            case 'paid': $status_text = 'LUNAS'; break;
            case 'pending': $status_text = 'PENDING'; break;
            case 'failed': $status_text = 'GAGAL'; break;
            case 'cancelled': $status_text = 'BATAL'; break;
            default: $status_text = 'BARU';
        }
        
        // Format tanggal lahir
        $tanggal_lahir = isset($b['tanggal_lahir']) && $b['tanggal_lahir'] ? date('d/m/Y', strtotime($b['tanggal_lahir'])) : '-';
        
        fputcsv($output, [
            $no++,
            date('d/m/Y H:i', strtotime($b['created_at'])),
            $tanggal_lahir, // DATA BARU
            $b['order_id'],
            $b['nama'],
            $b['email'],
            $b['no_wa'],
            $b['alamat'],
            $b['paket'],
            $b['jumlah_orang'],
            $b['total_harga'],
            $b['dp_amount'],
            $b['sisa_amount'],
            $status_text,
            $b['payment_id'] ?? '-',
            $b['payment_method'] ?? '-',
            $b['payment_types'] ?? '-'
        ]);
    }
    
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin STIFIn - Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            color: #b22222;
            font-size: 24px;
        }
        .header .date {
            color: #666;
        }
        .logout-btn {
            background: #b22222;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: #8b1a1a;
        }
        
        /* Stats Cards */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            font-size: 28px;
            color: #b22222;
            margin-bottom: 5px;
        }
        .stat-card p {
            color: #666;
            font-size: 14px;
        }
        
        /* Export Section */
        .export-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .export-title {
            font-size: 18px;
            font-weight: bold;
            color: #b22222;
        }
        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .export-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .excel-btn {
            background: #1e6f3f;
            color: white;
        }
        .excel-btn:hover {
            background: #0f4d2b;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }
        .csv-btn {
            background: #ff9900;
            color: white;
        }
        .csv-btn:hover {
            background: #cc7a00;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }
        .print-btn {
            background: #4a6fa5;
            color: white;
        }
        .print-btn:hover {
            background: #2f4d7a;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .filter-section input, .filter-section select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            flex: 1;
            min-width: 150px;
        }
        .filter-section button {
            padding: 10px 20px;
            background: #b22222;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .filter-section button:hover {
            background: #8b1a1a;
        }
        .reset-btn {
            background: #666 !important;
        }
        
        /* Table */
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th {
            background: #b22222;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background: #fff5f5;
        }
        
        /* Status Badge */
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
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
        .status-initiated {
            background: #e2e3e5;
            color: #383d41;
        }
        
        /* Payment Badge */
        .payment-badge {
            background: #e9f0f9;
            color: #1e3a8a;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            margin: 2px;
            display: inline-block;
        }
        
        /* Action Buttons */
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            margin: 0 2px;
        }
        .view-btn {
            background: #b22222;
            color: white;
        }
        .wa-btn {
            background: #25D366;
            color: white;
        }
        
        /* Pagination */
        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .pagination a {
            padding: 8px 12px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .pagination a.active {
            background: #b22222;
            color: white;
            border-color: #b22222;
        }
        .pagination a:hover {
            background: #b22222;
            color: white;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h2 {
            color: #b22222;
        }
        .close-btn {
            cursor: pointer;
            font-size: 24px;
            color: #666;
        }
        .detail-row {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
        }
        .detail-label {
            font-weight: bold;
            width: 120px;
            color: #666;
        }
        .detail-value {
            flex: 1;
        }
        
        /* Info Export */
        .export-info {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            color: #004085;
            font-size: 13px;
            margin-top: 10px;
        }

        /* Tanggal Lahir Badge */
        .tanggal-lahir-badge {
            background: #f0f0f0;
            color: #333;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>📊 STIFIn Admin Dashboard</h1>
                <div class="date"><?php echo date('d F Y H:i'); ?></div>
            </div>
            <div>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Transaksi</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['paid']; ?></h3>
                <p>✅ Sukses</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['pending']; ?></h3>
                <p>⏳ Pending</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['failed']; ?></h3>
                <p>❌ Gagal</p>
            </div>
            <div class="stat-card">
                <h3>Rp <?php echo number_format($stats['total_pendapatan']); ?></h3>
                <p>💰 Pendapatan</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total'] > 0 ? round(($stats['paid']/$stats['total'])*100) : 0; ?>%</h3>
                <p>📈 Success Rate</p>
            </div>
        </div>
        
        <!-- Export Section -->
        <div class="export-section">
            <div class="export-title">
                <i class="fas fa-download"></i> Export Data Transaksi
            </div>
            <div class="export-buttons">
                <a href="?export=excel" class="export-btn excel-btn">
                    <i class="fas fa-file-excel"></i> Export to Excel (XLS)
                </a>
                <a href="?export=csv" class="export-btn csv-btn">
                    <i class="fas fa-file-csv"></i> Export to CSV
                </a>
                <button class="export-btn print-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        
        <!-- Info Export -->
        <div class="export-info">
            <i class="fas fa-info-circle"></i> 
            Data yang diexport adalah <strong>SEMUA transaksi</strong> (tanpa filter). Format Excel mendukung file .xls yang bisa dibuka di Microsoft Excel atau LibreOffice Calc.
        </div>
        
        <!-- Filter -->
        <div class="filter-section">
            <input type="text" id="searchInput" placeholder="Cari nama/email/order ID...">
            <select id="statusFilter">
                <option value="all">Semua Status</option>
                <option value="paid">Sukses</option>
                <option value="pending">Pending</option>
                <option value="failed">Gagal</option>
                <option value="initiated">Baru</option>
            </select>
            <button onclick="applyFilter()">🔍 Cari</button>
            <button class="reset-btn" onclick="resetFilter()">↺ Reset</button>
        </div>
        
        <!-- Table -->
        <div class="table-container">
            <table id="transactionTable">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Tanggal Lahir</th> <!-- KOLOM BARU -->
                        <th>Order ID</th>
                        <th>Nama</th>
                        <th>Kontak</th>
                        <th>Paket</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Metode</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="10" style="text-align:center; padding:50px;">
                            Belum ada data transaksi
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                        <tr class="transaction-row" 
                            data-status="<?php echo $b['status']; ?>"
                            data-search="<?php echo strtolower($b['nama'].' '.$b['email'].' '.$b['order_id']); ?>">
                            <td><?php echo date('d/m/Y H:i', strtotime($b['created_at'])); ?></td>
                            <td>
                                <?php 
                                if (isset($b['tanggal_lahir']) && $b['tanggal_lahir']) {
                                    echo '<span class="tanggal-lahir-badge"><i class="fas fa-birthday-cake"></i> ' . date('d/m/Y', strtotime($b['tanggal_lahir'])) . '</span>';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><strong><?php echo $b['order_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($b['nama']); ?></td>
                            <td>
                                <?php echo $b['email']; ?><br>
                                <small><?php echo $b['no_wa']; ?></small>
                            </td>
                            <td><?php echo $b['paket']; ?> (<?php echo $b['jumlah_orang']; ?> org)</td>
                            <td>Rp <?php echo number_format($b['total_harga']); ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                $status_text = '';
                                switch ($b['status']) {
                                    case 'paid': $status_class = 'status-paid'; $status_text = '✅ Sukses'; break;
                                    case 'pending': $status_class = 'status-pending'; $status_text = '⏳ Pending'; break;
                                    case 'failed': $status_class = 'status-failed'; $status_text = '❌ Gagal'; break;
                                    case 'cancelled': $status_class = 'status-failed'; $status_text = '✖ Batal'; break;
                                    default: $status_class = 'status-initiated'; $status_text = '🆕 Baru';
                                }
                                ?>
                                <span class="status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                            <td>
                                <?php 
                                if ($b['payment_types']) {
                                    $methods = explode(',', $b['payment_types']);
                                    foreach ($methods as $m) {
                                        $m = trim($m);
                                        $icon = '💰';
                                        if (strpos($m, 'gopay') !== false) $icon = '📱';
                                        elseif (strpos($m, 'va') !== false) $icon = '🏦';
                                        elseif (strpos($m, 'credit') !== false) $icon = '💳';
                                        echo "<span class='payment-badge'>$icon $m</span>";
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <button class="action-btn view-btn" onclick="viewDetail('<?php echo $b['order_id']; ?>')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="https://wa.me/<?php echo $b['no_wa']; ?>" target="_blank" class="action-btn wa-btn">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?>">«</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" <?php echo $i == $page ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>">»</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal Detail -->
    <div class="modal" id="detailModal" onclick="if(event.target==this) closeModal()">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📋 Detail Transaksi</h2>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalBody">Loading...</div>
        </div>
    </div>
    
    <script>
        // Data bookings untuk modal
        const bookings = <?php echo json_encode($all_bookings); ?>;
        
        // Filter function
        function applyFilter() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const status = document.getElementById('statusFilter').value;
            const rows = document.getElementsByClassName('transaction-row');
            
            for (let row of rows) {
                let show = true;
                const rowSearch = row.getAttribute('data-search');
                const rowStatus = row.getAttribute('data-status');
                
                if (search && !rowSearch.includes(search)) show = false;
                if (status !== 'all' && rowStatus !== status) show = false;
                
                row.style.display = show ? '' : 'none';
            }
        }
        
        function resetFilter() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = 'all';
            const rows = document.getElementsByClassName('transaction-row');
            for (let row of rows) row.style.display = '';
        }
        
        // View detail
        function viewDetail(orderId) {
            const data = bookings.find(b => b.order_id === orderId);
            if (!data) return;
            
            // Format tanggal lahir
            const tanggalLahir = data.tanggal_lahir ? new Date(data.tanggal_lahir).toLocaleDateString('id-ID') : '-';
            
            const modalBody = `
                <div class="detail-row">
                    <span class="detail-label">Order ID</span>
                    <span class="detail-value"><strong>${data.order_id}</strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Waktu</span>
                    <span class="detail-value">${new Date(data.created_at).toLocaleString('id-ID')}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nama</span>
                    <span class="detail-value">${data.nama}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tanggal Lahir</span>
                    <span class="detail-value">${tanggalLahir}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span class="detail-value">${data.email}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">No. WA</span>
                    <span class="detail-value">${data.no_wa}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Alamat</span>
                    <span class="detail-value">${data.alamat}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Paket</span>
                    <span class="detail-value">${data.paket}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Jumlah</span>
                    <span class="detail-value">${data.jumlah_orang} orang</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total</span>
                    <span class="detail-value">Rp ${Number(data.total_harga).toLocaleString()}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">DP</span>
                    <span class="detail-value">Rp ${Number(data.dp_amount).toLocaleString()}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Sisa</span>
                    <span class="detail-value">Rp ${Number(data.sisa_amount).toLocaleString()}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">${data.status}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment ID</span>
                    <span class="detail-value">${data.payment_id || '-'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Metode</span>
                    <span class="detail-value">${data.payment_method || '-'}</span>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <a href="https://wa.me/${data.no_wa}" target="_blank" class="action-btn wa-btn" style="padding: 8px 15px; background: #25D366; color: white; text-decoration: none; border-radius: 4px;">
                        <i class="fab fa-whatsapp"></i> Hubungi Customer
                    </a>
                </div>
            `;
            
            document.getElementById('modalBody').innerHTML = modalBody;
            document.getElementById('detailModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('detailModal').classList.remove('active');
        }
    </script>
</body>
</html>