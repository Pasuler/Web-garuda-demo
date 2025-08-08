<?php
require_once 'includes/config.php';

// Untuk testing saja - menampilkan satu bukti pembayaran yang ada
$stmt = $pdo->query("SELECT id, receipt_image FROM payments WHERE receipt_image IS NOT NULL LIMIT 1");
$payment = $stmt->fetch();

if ($payment && file_exists("uploads/receipts/" . $payment['receipt_image'])) {
    echo "✅ Test berhasil! Bukti pembayaran dapat diakses.\n";
    echo "Payment ID: " . $payment['id'] . "\n";
    echo "File: " . $payment['receipt_image'] . "\n";
    echo "Path: uploads/receipts/" . $payment['receipt_image'] . "\n";
    echo "File size: " . formatBytes(filesize("uploads/receipts/" . $payment['receipt_image'])) . "\n";
} else {
    echo "❌ Test gagal atau tidak ada file bukti pembayaran.\n";
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
?>
