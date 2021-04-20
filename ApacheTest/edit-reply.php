<?php
  session_start();
  mb_language("Japanese");
  mb_internal_encoding("UTF-8");
  header("Contetnt-type: text/html; charset=utf-8");



  // クリックジャッキング対策
  header('X-FRAME-OPTIONS: SAMEORIGIN');

  //CSRF対策
  //推測されにくいランダムな文字列を生成、格納（あとでPOSTがsendされたとき実行）
  function setToken() {                             
    $token = sha1(uniqid(mt_rand(), true));         
    $_SESSION['token'] = $token;
  }

  //POSTに格納されたtokenが異なるとエラーを返す
  function checkToken() {
    if (empty($_SESSION["token"]) || ($_SESSION["token"] != $_POST["token"]) ){  
      echo "不正なPOSTが行われました。";
      exit;
    }
  }

  // function setToken_2() {                             
  //   $token_2 = sha1(uniqid(mt_rand(), true));         
  //   $_SESSION_2['token'] = $token_2;
  // }


  // function checkToken_2() {
  //   if (empty($_SESSION["token"]) || ($_SESSION_2["token"] != $_POST["token"]) ){  
  //     echo "不正なPOSTが行われました。";
  //     exit;
  //   }
  // }


  function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
  }

  //変数宣言


  //親コメントid
  $parentid = 0;
  //子コメントid
  $childid = 0;
  // 親コメントと子コメントをヒモづけるid
  $parentid = 0;

  $err_msg = "";

  //親コメント格納配列
  $dataArr;
  //子コメント格納配列
  $dataArrChild;

  $pagination;

  //データベース接続用ファイルを呼び出す
  require_once("./db.php");

  // 編集したいコメントの連番をGETで取得
  $editTarget = $_GET["reply"];

  //データベース接続(pdo)
  try{    
      $pdo = new PDO($dsn, $dbuser, $password); 
      $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  
  } catch (PDOException $e) {
      exit('データベースに接続できませんでした。' . $e->getMessage());
  } 

//返信（子コメント）表示
  // $mysqli = new mysqli($host, $dbuser, $password, $dbName);
  // if ($mysqli->connect_error) {
  //   echo $mysqli->connect_error;
  //   exit();
  // }
  // if( $mysqli->connect_errno ) {
  //   echo $mysqli->connect_errno . ' : ' . $mysqli->connect_error;
  // }
  //文字コード指定
  // $mysqli->set_charset('utf8'); 

  //テーブル指定、データ取得
  $sqlChild = "SELECT * FROM bbs_reply"; 
  if($dbreply = $pdo->query($sqlChild)){
    while( $reply = $dbreply->fetch(PDO::FETCH_ASSOC) ) {
      $arrChild = array(
        "childid"=>$reply["childid"],

        "parentid"=>$reply["parentid"],

        "user"=>$reply["user_name"],
      
        "postedAt"=>$reply["date"],
      
        "message"=>$reply["message"]       
      );
      $dataArrChild[]= $arrChild;
    } 
    $dbreply=null;    
  }
  



//返信(子コメント)送信用
  if (isset($_POST["send"] ) ===  true) {
    checkToken();
    if(!empty($_POST["message"])) {   
      $user = (isset( $_POST["user"] ) === true) ? $_POST["user"] : "";  
      $message = (isset( $_POST["message"] ) === true) ? trim($_POST["message"]) : "";    
      $postedAt = date("Y-m-d H:i:s");      
      $parentId = (isset( $_POST["parentid"] ) === true) ? $_POST["parentid"] : "";

      $message = h(str_replace("\t", " ", $message));
      $user = h(str_replace("\t", " ", $user));
      
      if ($user === "")$user = "名無しさん";

      try {
        $st = $pdo->prepare("UPDATE bbs_reply SET parentid=?, user_name=?, date=?, message=? WHERE childid = ?");
        $st->execute(array("$parentId", "$user", "$postedAt", "$message", "$editTarget"));
      } catch (Exception $e) {
        echo "エラーがありました。";
        echo $e->getMessage();
        exit();
      }
      header('Location: ./result.php');   
    } else {
      $err_msg = "エラー！本文を入力して下さい。";
    }
  } else {
    setToken();
  }

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>MyBBS(田中 愛智)</title>
  <link rel="stylesheet" type="text/css" href="./css/style.css">
  <link rel="stylesheet" type="text/css" href="./css/toggle.css">
</head>
<body>
  <h1>MyBBS</h1>
  <h2>編集機能</h2>
  <div class="container">
  <?php foreach ($dataArrChild as $data) : ?>
    <?php if ($data["childid"] === $editTarget) : ?>
      <form class="send-form" action="" method="post">
        投稿者 : <input type="text" name="user" value="<?php echo h($data["user"]); ?>"><br>
        本文 : <br>
        <textarea name="message" placeholder=""><?php echo h($data["message"]); ?></textarea>
        <input name="send" type="submit" value="投稿">
        <input type="hidden" name="parentid" value="<?php echo h($data["parentid"]); ?>">
        <input type="hidden" name="token" value="<?php echo h($_SESSION['token']); ?>">
      </form> <!-- send-form -->
    <?php endif; ?>
  <?php endforeach; ?> 
  </div>


  <script type="text/javascript" src="./js/main.js"></script>
</body>
</html>