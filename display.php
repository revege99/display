<?php
require_once __DIR__ . '/function/function.php';

$hari_ini = date('l');
$hari_map = [
  'Sunday' => 'AKHAD',
  'Monday' => 'SENIN',
  'Tuesday' => 'SELASA',
  'Wednesday' => 'RABU',
  'Thursday' => 'KAMIS',
  'Friday' => 'JUMAT',
  'Saturday' => 'SABTU'
];
$hari = $hari_map[$hari_ini];
$tanggal_hari_ini = date('Y-m-d');

// === Bagian untuk refresh dokter pindah ke atas ===
if(isset($_GET['refresh_dokter'])){
  $query = "
    SELECT d.kd_dokter, d.nm_dokter, p.nm_poli
    FROM jadwal j
    JOIN dokter d ON j.kd_dokter = d.kd_dokter
    JOIN poliklinik p ON j.kd_poli = p.kd_poli
    WHERE j.hari_kerja = '$hari'
    ORDER BY p.nm_poli, d.nm_dokter
  ";
  $result = $conn->query($query);

  if($result && $result->num_rows > 0){
    foreach($result as $dok){
      $kd = $dok['kd_dokter'];
      $res = $conn->query("SELECT noReg FROM antripoli WHERE kd_dokter='$kd' AND tanggal='$tanggal_hari_ini' ORDER BY CAST(noReg AS UNSIGNED)");
      $noRegList = [];
      while($r = $res->fetch_assoc()) $noRegList[] = $r['noReg'];

      echo "<div class='col-md-4 col-sm-6 col-12 doctor-card'>";
      echo "<div class='p-3 border rounded bg-light'>";
      echo "<strong>{$dok['nm_dokter']}</strong><br>";
      echo "<span class='text-muted'>{$dok['nm_poli']}</span><hr>";
      if($noRegList){
        foreach($noRegList as $n) echo "<div>$n</div>";
      } else {
        echo "<div class='text-secondary'>Belum ada antrian</div>";
      }
      echo "</div></div>";
    }
  } else {
    echo "<p class='text-muted'>Tidak ada jadwal dokter hari ini</p>";
  }
  exit;
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Pemanggilan Antrean</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/pemanggilan.css">


  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,700;1,700&display=swap" rel="stylesheet">


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.responsivevoice.org/responsivevoice.js?key=jgnnOkio"></script>
<style>
body { font-family: 'Quicksand', sans-serif; background: #fafafa; }
.bg-light { background-color: #f8f9fa !important; transition: 0.2s; }
.bg-light:hover { background-color: #e9ecef !important; }
.doctor-card { min-height: 120px; margin-bottom: 15px; }
</style>
</head>
<body>
<div class="container-fluid">
  <div class="top">
      <div class="header">
        <div class="logo">
          <img src="img/logo.png" alt="Logo RS">
        </div>
        <div class="namers">
          <h3>Klinik Rawat Jalan</h3>
          <p>Santa Lucia</p>
        </div>
      </div>
      <div class="date">
        <span id="tanggal"></span> | </i><span id="jam"></span>
      </div>

      <div class="information">
        <h2 class="text-center">Antrian Poliklinik</h2>
      </div>
    </div>

  <!-- Panggilan Aktif -->
  <div id="callDisplay" class="text-center mb-4">
    <h1 id="displayNomor" class="fw-bold"></h1>
    <h2 id="displayNama"></h2>
    <h4 id="displayDokter"></h4>
    <h4 id="displayPoli"></h4>
  </div>

  <hr>

  <!-- Daftar Dokter & Antrian -->
  <div id="daftarDokterContainer" class="row justify-content-center text-center">
    <p>Loading daftar dokter & antrian...</p>
  </div>
</div>

<audio id="notifSound" src="ding.mp3" preload="auto"></audio>

<script>
// ===== Tanggal & Jam =====
function updateTanggalJam() {
  const now = new Date();
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  document.getElementById('tanggal').textContent = now.toLocaleDateString('id-ID', options);
  document.getElementById('jam').textContent = now.toLocaleTimeString('id-ID');
}
setInterval(updateTanggalJam, 1000);
updateTanggalJam();

// ===== Variabel =====
let antrianPemanggilan = [];
let sedangMemanggil = false;
let lastCallId = 0;

// ===== Fungsi Panggilan =====
function prosesPemanggilanBerikutnya() {
  if (antrianPemanggilan.length === 0) {
    sedangMemanggil = false;
    return;
  }

  sedangMemanggil = true;
  const data = antrianPemanggilan.shift();
  const namaMentah = data.nama.replace(/\s*\(.*?\)\s*/g, "");
  const namaBersih = formatNama(namaMentah);

  document.getElementById("displayNomor").innerText = data.nomor;
  // document.getElementById("displayNama").innerText = data.nama;
  // Hilangkan teks dalam tanda kurung, misal " (1 Hr)"

document.getElementById("displayNama").innerText = namaBersih;

  document.getElementById("displayDokter").innerText = data.dokter;
  document.getElementById("displayPoli").innerText = data.spesialis;

  document.getElementById('notifSound').play();

  const teks = `Nomor antrian ${data.nomor}, atas nama ${namaBersih}, silakan menuju ${data.spesialis}, bersama ${data.dokter}.`;


  responsiveVoice.speak(teks, "Indonesian Female", {
    rate: 0.9,
    onend: () => {
      prosesPemanggilanBerikutnya();
    }
  });
}

// ===== Cek Trigger =====
async function checkTrigger() {
  try {
    const trigger = await fetch("trigger.txt?" + new Date().getTime()).then(r => r.text());
    const triggerId = parseInt(trigger.trim());

    if (triggerId > 0 && triggerId !== lastCallId) {
      const data = await fetch("data-antrian.json?" + new Date().getTime()).then(r => r.json());

      if (data.call_id && data.call_id !== lastCallId) {
        lastCallId = data.call_id;
        antrianPemanggilan.push(data);
        if (!sedangMemanggil) prosesPemanggilanBerikutnya();
      }

      await fetch("reset-trigger.php");
    }
  } catch (e) {
    console.error("Error checkTrigger:", e);
  }

  setTimeout(checkTrigger, 1500);
}
document.addEventListener("DOMContentLoaded", checkTrigger);

// ===== Refresh Daftar Dokter & Antrian =====
async function refreshDaftarDokter() {
  try {
    const response = await fetch("?refresh_dokter=1&time=" + new Date().getTime());
    const html = await response.text();
    document.getElementById("daftarDokterContainer").innerHTML = html;
  } catch(e) { console.error("Error refresh daftar dokter:", e); }
  setTimeout(refreshDaftarDokter, 5000);
}
document.addEventListener("DOMContentLoaded", refreshDaftarDokter);
</script>

<?php
// ===== Bagian untuk render daftar dokter =====
if(isset($_GET['refresh_dokter'])){
  $query = "
    SELECT d.kd_dokter, d.nm_dokter, p.nm_poli
    FROM jadwal j
    JOIN dokter d ON j.kd_dokter = d.kd_dokter
    JOIN poliklinik p ON j.kd_poli = p.kd_poli
    WHERE j.hari_kerja = '$hari'
    ORDER BY p.nm_poli, d.nm_dokter
  ";
  $result = $conn->query($query);

  if($result && $result->num_rows > 0){
    foreach($result as $dok){
      $kd = $dok['kd_dokter'];
      $res = $conn->query("SELECT noReg FROM antripoli WHERE kd_dokter='$kd' AND tanggal='$tanggal_hari_ini' ORDER BY CAST(noReg AS UNSIGNED)");
      $noRegList = [];
      while($r = $res->fetch_assoc()) $noRegList[] = $r['noReg'];

      echo "<div class='col-md-4 col-sm-6 col-12 doctor-card'>";
      echo "<div class='p-3 border rounded bg-light'>";
      echo "<strong>{$dok['nm_dokter']}</strong><br>";
      echo "<span class='text-muted'>{$dok['nm_poli']}</span><hr>";
      if($noRegList){
        foreach($noRegList as $n) echo "<div>$n</div>";
      } else {
        echo "<div class='text-secondary'>Belum ada antrian</div>";
      }
      echo "</div></div>";
    }
  } else {
    echo "<p class='text-muted'>Tidak ada jadwal dokter hari ini</p>";
  }
  exit;
}
?>
</body>
</html>

<script src="js/script.js"></script>