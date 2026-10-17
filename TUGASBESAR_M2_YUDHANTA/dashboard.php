<?php
    session_start();
    if(isset($_POST["logout"])){
        session_unset();
        session_destroy();
        header("location: index.php");
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    />
    <style>
        #map { height: 450px;
        width: 100%;
        }
    </style>
</head>
<body>
    <?php include "layout/header.html"?>
    <h1>SELAMAT DATANG <?= $_SESSION["username"].$_SESSION["id_pengguna"] ?> DI WEBSITE KAMI</h1>
    <?php include "layout/footer.html"?>
      <h2>Peta Lokasi Pengiriman</h2>

    <!-- Tempat peta ditampilkan -->
    <div id="map"></div>
    <p>Latitude: <span id="lat">-</span></p>
    <p>Longitude: <span id="lng">-</span></p>
    <button onclick="simpan()">Simpan Lokasi</button>
    <!-- Mengambil JavaScript Leaflet -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
const map = L.map('map').setView([-6.2, 106.8], 10);

L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
}).addTo(map);

let lat, lng;
let marker;

map.on('click', function(e) {
    lat = e.latlng.lat;
    lng = e.latlng.lng;

    document.getElementById('lat').textContent = lat;
    document.getElementById('lng').textContent = lng;

    if (marker) map.removeLayer(marker);
    marker = L.marker([lat, lng]).addTo(map);
});

function simpan() {
    if (lat === undefined) {
        alert('Klik peta dahulu!');
        return;
    }

    fetch('simpan.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'lat=' + lat + '&lng=' + lng
    })
    .then(response => response.text())
    .then(hasil => alert(hasil));
}
</script>

    <form action="dashboard.php" method="POST">
        <button type="submit" name="logout">Logout</button>
    </form>
    
</body>
</html>