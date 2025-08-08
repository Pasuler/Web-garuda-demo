<?php
/**
 * Path Configuration untuk Upload Files
 * 
 * Gunakan konstanta ini untuk memastikan konsistensi path di seluruh aplikasi
 */

// Base upload directory (relatif dari root website)
define('UPLOAD_BASE_DIR', 'uploads/');

// Receipt upload directory
define('RECEIPT_UPLOAD_DIR', UPLOAD_BASE_DIR . 'receipts/');

// Hotel image directory  
define('HOTEL_UPLOAD_DIR', UPLOAD_BASE_DIR . 'hotels/');

// Function untuk mendapatkan absolute path dari root
function getUploadPath($relative_path) {
    return __DIR__ . '/../' . $relative_path;
}

// Function untuk mendapatkan URL path untuk browser
function getUploadUrl($filename, $type = 'receipt') {
    switch ($type) {
        case 'receipt':
            return RECEIPT_UPLOAD_DIR . $filename;
        case 'hotel':
            return HOTEL_UPLOAD_DIR . $filename;
        default:
            return UPLOAD_BASE_DIR . $filename;
    }
}

// Ensure directories exist
function ensureUploadDirectories() {
    $dirs = [
        getUploadPath(RECEIPT_UPLOAD_DIR),
        getUploadPath(HOTEL_UPLOAD_DIR)
    ];
    
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// Call on include
ensureUploadDirectories();
?>
