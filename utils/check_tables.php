<?php
require_once 'includes/config.php';

try {
    echo "Checking payments table structure:\n";
    $stmt = $pdo->query('DESCRIBE payments');
    while ($row = $stmt->fetch()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\nChecking chats table structure:\n";
    $stmt = $pdo->query('DESCRIBE chats');
    while ($row = $stmt->fetch()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\nChecking uploads directory:\n";
    $upload_dir = 'uploads/receipts/';
    if (file_exists($upload_dir)) {
        echo "- Directory exists: YES\n";
        echo "- Directory permissions: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "\n";
    } else {
        echo "- Directory exists: NO\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
