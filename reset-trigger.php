<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
if (file_put_contents(__DIR__ . '/trigger.txt', '0', LOCK_EX) === false) {
    http_response_code(500);
    exit('Gagal mereset trigger.txt. Periksa izin tulis pengguna PHP dan log server.');
}
echo "reset";
