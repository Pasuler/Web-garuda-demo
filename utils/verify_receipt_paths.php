<?php
require_once 'includes/config.php';

echo "=== VERIFIKASI PATH BUKTI PEMBAYARAN ===\n\n";

// 1. Check database receipts
echo "1. Memeriksa data bukti pembayaran di database:\n";
$stmt = $pdo->query("SELECT id, id_booking, receipt_image, payment_status FROM payments WHERE receipt_image IS NOT NULL ORDER BY id DESC LIMIT 10");
while ($row = $stmt->fetch()) {
    echo "  Payment ID: {$row['id']} | Booking: {$row['id_booking']} | File: {$row['receipt_image']} | Status: {$row['payment_status']}\n";
    
    // Check if file exists
    $file_path = "uploads/receipts/" . $row['receipt_image'];
    if (file_exists($file_path)) {
        echo "    ✅ File exists: $file_path\n";
    } else {
        echo "    ❌ File NOT found: $file_path\n";
        
        // Check in old location
        $old_path = "pages/booking/uploads/receipts/" . $row['receipt_image'];
        if (file_exists($old_path)) {
            echo "    📁 Found in old location: $old_path\n";
        }
    }
    echo "\n";
}

// 2. Check directory structure
echo "\n2. Memeriksa struktur direktori:\n";
$dirs_to_check = [
    'uploads/receipts',
    'uploads/payment_receipts',
    'pages/booking/uploads/receipts'
];

foreach ($dirs_to_check as $dir) {
    echo "  Checking: $dir\n";
    if (file_exists($dir) && is_dir($dir)) {
        echo "    ✅ Directory exists\n";
        $files = glob($dir . "/*");
        echo "    📊 Files count: " . count($files) . "\n";
        if (count($files) > 0) {
            foreach (array_slice($files, 0, 3) as $file) {
                echo "      - " . basename($file) . "\n";
            }
            if (count($files) > 3) {
                echo "      ... and " . (count($files) - 3) . " more files\n";
            }
        }
    } else {
        echo "    ❌ Directory NOT exists\n";
    }
    echo "\n";
}

// 3. Check permissions
echo "3. Memeriksa permissions:\n";
$main_upload_dir = 'uploads/receipts';
if (file_exists($main_upload_dir)) {
    echo "  Directory: $main_upload_dir\n";
    echo "    Readable: " . (is_readable($main_upload_dir) ? '✅ YES' : '❌ NO') . "\n";
    echo "    Writable: " . (is_writable($main_upload_dir) ? '✅ YES' : '❌ NO') . "\n";
    echo "    Permissions: " . substr(sprintf('%o', fileperms($main_upload_dir)), -4) . "\n";
}

echo "\n=== SELESAI ===\n";
?>
