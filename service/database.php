<?php

    $hostname="localhost";
    $username= "root";
    $password= "";
    $dbname= "shopku_testing";

    $db=mysqli_connect($hostname, $username, $password, $dbname);
    if($db->connect_error){
        echo "Gagal terhubung dengan database";
        die();
    }
   
?>