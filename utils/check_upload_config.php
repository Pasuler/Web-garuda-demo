<?php
echo "PHP Upload Configuration:\n";
echo "file_uploads: " . (ini_get('file_uploads') ? 'On' : 'Off') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "upload_tmp_dir: " . (ini_get('upload_tmp_dir') ?: 'Default') . "\n";

echo "\nDirectory permissions:\n";
$upload_dir = 'uploads/receipts/';
if (file_exists($upload_dir)) {
    echo "uploads/receipts/ exists: YES\n";
    echo "uploads/receipts/ writable: " . (is_writable($upload_dir) ? 'YES' : 'NO') . "\n";
    echo "uploads/receipts/ permissions: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "\n";
} else {
    echo "uploads/receipts/ exists: NO\n";
}

$parent_dir = 'uploads/';
if (file_exists($parent_dir)) {
    echo "uploads/ exists: YES\n";
    echo "uploads/ writable: " . (is_writable($parent_dir) ? 'YES' : 'NO') . "\n";
    echo "uploads/ permissions: " . substr(sprintf('%o', fileperms($parent_dir)), -4) . "\n";
} else {
    echo "uploads/ exists: NO\n";
}
?>
