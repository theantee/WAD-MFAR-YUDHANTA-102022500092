<?php
    include "service/database.php";
    session_start();
    $loginMessege="";
    if(isset($_SESSION["is_login"])){
        header("location: dashboard.php");
    }
    if(isset($_POST["login"])){
        $username = $_POST["username"];
        $password = $_POST["password"];

        $sql="SELECT * FROM user WHERE username='$username' AND password='$password'";      
        $result = $db->query($sql);
        if($result->num_rows > 0){
            $data = $result->fetch_assoc();
            $_SESSION['username'] = $data['username'];
            $_SESSION['id_pengguna'] = (int)$data['id_pengguna'];
            $_SESSION['is_login'] = true;
            header("location: index.php");
        }
        else{
            $loginMessege= "Akun tidak ditemukan";
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
    <h3>masuk akun</h3>
    <i><?= $loginMessege ?></i>
    <form action="login.php" method="POST">
        <input type="text" placeholder="username" name="username"/>
        <input type="password" placeholder="password" name="password"/>
        <button type="submit" name="login">masuk sekarang</button>
    </form>
    <?php include "layout/footer.html"?>
</body>
</html>