<?php
  function db_connect($host, $dbuser, $password, $dbName) {
    $mysqli = new mysqli($host, $dbuser, $password, $dbName);
    if ($mysqli->connect_error) {
      echo $mysqli->connect_error;
      exit();
    }
    if( $mysqli->connect_errno ) {
      echo $mysqli->connect_errno . ' : ' . $mysqli->connect_error;
    }
    //文字コード指定
    $mysqli->set_charset('utf8'); 

    return $mysqli;
  } 

  // function db_connect($dsn, $dbuser, $password) {
  //   try {
  //     $pdo = new PDO($dsn, $dbuser, $password);                         
  //     $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
  //     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  //     return $pdo;
  //   }catch(PDOException $e) {
  //     echo 'DB接続エラー: ' . $e->getMessage();
  // }

?>