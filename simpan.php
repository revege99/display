<?php
$call_id = time(); // ID unik setiap pemanggilan

$data = [
  "call_id"   => $call_id,
  "nama"      => $_POST['nama'] ?? '',
  "nomor"     => $_POST['nomor'] ?? '',
  "dokter"    => $_POST['dokter'] ?? '',
  "spesialis" => $_POST['spesialis'] ?? ''
];

file_put_contents("data-antrian.json", json_encode($data));
file_put_contents("trigger.txt", (string)$call_id); // trigger SELALU berubah

echo "sukses";