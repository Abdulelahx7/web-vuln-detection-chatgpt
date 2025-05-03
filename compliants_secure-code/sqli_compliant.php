<?php

include '../mysql_login.php';

if (isset($_GET['id'])){
  $id = $_GET['id'];

   if ( is_numeric($id) == true){
    try{ 

      $dbh = new PDO('mysql:host=localhost;dbname=MARKET', $username, $password);
      

      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      

      $q = "SELECT pname 
          FROM Products
          WHERE pid = :id";

      $sth = $dbh->prepare($q);
   
      $sth->bindParam(':id', $id);
      
      $sth->execute();
    
      $sth->setFetchMode(PDO::FETCH_ASSOC);

      $result = $sth->fetchColumn();

      print( htmlentities($result) );
      

      $dbh = null;
    }
    catch(PDOException $e){
 
      error_log('PDOException - ' . $e->getMessage(), 0);

      http_response_code(500);
      die('Error establishing connection with database');
    }
   } else{
 
    http_response_code(400);
    die('Error processing bad or malformed request');
   }
}
else{

    echo "no get";
}