<?php
    include "service/database.php";
    session_start();
    $registerMessege="";
    if(isset($_SESSION["is_login"])){
        header("location: dashboard.php");
    }
    if(isset($_POST["register"])){
        $username = $_POST["username"];
        $password = $_POST["password"];

        $sql="INSERT INTO user (username, password) VALUES ('$username','$password')";
        
        try{
        if($db->query($sql)){
            $registerMessege= "Akun berhasil dibuat, silahkan masuk";
        }
        else{
            $registerMessege= "Akun gagal dibuat, silahkan coba lagi";
        }
        }catch(mysqli_sql_exception){
            $registerMessege= "Username sudah ada, silahkan coba lagi";
        }
        $db->close();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php include "layout/header.php" ?>
    <h3>Daftar akun</h3>
    <i><?= $registerMessege ?></i>
    <form action="register.php" method="POST">
        <input type="text" placeholder="username" name="username"/>
        <input type="password" placeholder="password" name="password"/>
        <button type="submit" name="register">Daftar sekarang</button>
    </form>
    <?php include "layout/footer.html"?>
</body>
</html>