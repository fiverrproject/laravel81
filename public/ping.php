<?php
/**
 * Open: https://resumesmaker.com/server/public/ping.php
 * If you see "LARAVEL_PUBLIC_OK", this file is executed (not Node/Angular).
 */
header('Content-Type: text/plain; charset=utf-8');
echo 'LARAVEL_PUBLIC_OK';
echo "\npath=".__DIR__;
echo "\nindex_exists=".(file_exists(__DIR__.'/index.php') ? 'yes' : 'NO_UPLOAD_INDEX_PHP');
