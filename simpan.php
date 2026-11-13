<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Gunakan POST untuk menyimpan pemanggilan.');
}

foreach (['nama', 'nomor', 'dokter', 'spesialis'] as $field) {
    if (!isset($_POST[$field]) || !is_string($_POST[$field]) || trim($_POST[$field]) === '') {
        http_response_code(422);
        exit('Data pemanggilan tidak lengkap: ' . $field);
    }
}

$call_id = (int) floor(microtime(true) * 1000000);

$data = [
  "call_id"   => $call_id,
  "nama"      => $_POST['nama'] ?? '',
  "nomor"     => $_POST['nomor'] ?? '',
  "dokter"    => $_POST['dokter'] ?? '',
  "spesialis" => $_POST['spesialis'] ?? ''
];

$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($json === false || file_put_contents(__DIR__ . '/data-antrian.json', $json, LOCK_EX) === false) {
    http_response_code(500);
    exit('Gagal menyimpan data-antrian.json. Periksa izin tulis pengguna PHP dan log server.');
}
if (file_put_contents(__DIR__ . '/trigger.txt', (string)$call_id, LOCK_EX) === false) {
    http_response_code(500);
    exit('Data tersimpan, tetapi gagal menulis trigger.txt. Periksa izin tulis pengguna PHP dan log server.');
}

echo "sukses";
