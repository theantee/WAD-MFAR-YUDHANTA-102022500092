
<?php
session_start();
include 'service/database.php';


$lat = $_POST['lat'];
$lng = $_POST['lng'];
$id=$_SESSION['id_pengguna'];

$sql = "INSERT INTO lokasi_tersimpan (latitude, longitude,id_pengguna)
        VALUES (?, ?, ?)"; 

$stmt = mysqli_prepare($db, $sql);

mysqli_stmt_bind_param($stmt, "ddi", $lat, $lng, $id);

mysqli_stmt_execute($stmt);

echo "Lokasi berhasil disimpan!";
?>
