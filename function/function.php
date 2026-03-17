<?php 


// Koneksi ke database
$conn = mysqli_connect("localhost", "root", "s1ntluc14", "sik");

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Fungsi untuk query biasa (mengembalikan array hasil)
function query($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

// Fungsi untuk query dengan prepared statement (menghindari SQL Injection)
// function queryPrepared($query, $params = []) {
//     global $conn;
//     $stmt = mysqli_prepare($conn, $query);
    
//     if ($stmt === false) {
//         die('Query error: ' . mysqli_error($conn));
//     }

//     if (!empty($params)) {
//         $types = str_repeat('s', count($params)); // Semua parameter dianggap string
//         mysqli_stmt_bind_param($stmt, $types, ...$params);
//     }

//     mysqli_stmt_execute($stmt);
//     $result = mysqli_stmt_get_result($stmt);

//     $rows = [];
//     while ($row = mysqli_fetch_assoc($result)) {
//         $rows[] = $row;
//     }
    
//     mysqli_stmt_close($stmt);
//     return $rows;
// }

function queryPrepared($query, $params = []) {
    global $conn; // koneksi mysqli
    $stmt = $conn->prepare($query);
    if(!$stmt) {
        echo "Prepare failed: ".$conn->error.PHP_EOL;
        return false;
    }

    if($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    if(!$stmt->execute()) {
        echo "Execute failed: ".$stmt->error.PHP_EOL;
        return false;
    }

    // jika SELECT
    if(stripos(trim($query), "SELECT") === 0) {
        $result = $stmt->get_result();
        $rows = [];
        while($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    // untuk INSERT/UPDATE/DELETE
    return true;
}


function queryPreparedInsert($query, $params = []) {
    global $mysqli; // Pastikan variabel $mysqli sudah dideklarasikan sebagai koneksi MySQL

    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        die("Error prepare statement: " . $mysqli->error);
    }

    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // semua parameter sebagai string (bisa diganti jika perlu)
        $stmt->bind_param($types, ...$params);
    }

    $result = $stmt->execute();
    if (!$result) {
        die("Error execute statement: " . $stmt->error);
    }

    return $result;
}










function kirimTaskBPJS($kodebooking, $taskid, $waktu) {
    // Konfigurasi
    $cons_id    = "22020";
    $secretKey  = "3aLBB8C8D8";
    $user_key   = "1cae203f209aa3d28db949c8a3806069";
    $url        = "https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/updatewaktu";

    date_default_timezone_set('UTC');
    $timestamp = time();
    $signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secretKey, true));

    $headers = [
        "Content-Type: application/json",
        "X-cons-id: $cons_id",
        "X-timestamp: $timestamp",
        "X-signature: $signature",
        "user_key: $user_key"
    ];

    $body = json_encode([
        "kodebooking" => $kodebooking,
        "taskid"      => (int)$taskid,
        "waktu"       => (int)$waktu
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        curl_close($ch);
        return [
            "status" => false,
            "message" => curl_error($ch)
        ];
    }

    curl_close($ch);
    return json_decode($response, true); // Kembalikan sebagai array
}







function insertMCUToDB($conn, $payload) {
    $stmt = $conn->prepare("INSERT INTO pcare_MCU 
        (kdMCU, noKunjungan, keluhan, sistole, diastole, beratBadan, tinggiBadan, lingkarPerut, respRate, heartRate, terapi, kdDokter, tanggal_insert)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->bind_param(
        "issiiiiiisss",
        $payload['kdMCU'],
        $payload['noKunjungan'],
        $payload['keluhan'],
        $payload['sistole'],
        $payload['diastole'],
        $payload['beratBadan'],
        $payload['tinggiBadan'],
        $payload['lingkarPerut'],
        $payload['respRate'],
        $payload['heartRate'],
        $payload['terapi'],
        $payload['kdDokter']
    );

    if (!$stmt->execute()) {
        echo "<script>alert('Kirim BPJS berhasil, tapi gagal simpan lokal: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}












?>
