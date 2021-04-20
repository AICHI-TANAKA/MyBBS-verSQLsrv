<?php
  session_start();
  mb_language("Japanese");
  mb_internal_encoding("UTF-8");
  header("Contetnt-type: text/html; charset=utf-8");



  // クリックジャッキング対策
  header("X-FRAME-OPTIONS: SAMEORIGIN");

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

//   function setToken_2() {                             
//     $token_2 = sha1(uniqid(mt_rand(), true));         
//     $_SESSION2['token'] = $token_2;
//   }


//   function checkToken_2() {
//     if (empty($_SESSION2["token"]) || ($_SESSION2["token"] != $_POST["token"]) ){  
//       echo "不正なPOSTが行われました。";
//       exit;
//     }
//   }


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

  $pagecount = 0;

  //親コメント格納配列
  $dataArr=[];
  //子コメント格納配列
  $dataArrChild=[];
  // ページ数格納配列
  $pagination=[];

  //データベース接続用ファイルを呼び出す
  require_once("./db.php");
 
  // データベース接続
  require_once("./db_connect.php");

try{    
    $pdo = new PDO($dsn, $dbuser, $password); 
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  
} catch (PDOException $e) {
    exit('データベースに接続できませんでした。' . $e->getMessage());
} 

//親コメント取得
  //テーブル指定、データ取得
  $sql = "SELECT * FROM bbs_res ORDER BY id DESC" ; 
  if($dbres = $pdo->query($sql)){
    while( $res = $dbres->fetch(PDO::FETCH_ASSOC)) {
        $arr = array(
        
          "id"=>$res["id"],

          "user"=>$res["user_name"],
        
          "postedAt"=>$res["date"],
        
          "message"=>$res["message"]
        );
        $dataArr[]= $arr;
    } 
    $dbres=null;    
  }

  $parentid = count($dataArr); 

  // 2021年1月30日修正
  //$dataArr = array($dataArr);

//返信（子コメント）取得
  //テーブル指定、データ取得
  $sqlChild = "SELECT * FROM bbs_reply ORDER BY childid DESC"; 
  if($dbreply = $pdo->query($sqlChild)){
    while( $reply = $dbreply->fetch(PDO::FETCH_ASSOC) ) {
      $arrChild = array(

        "id"=>$reply["parentid"],

        "user"=>$reply["user_name"],
      
        "postedAt"=>$reply["date"],
      
        "message"=>$reply["message"],

        "childid"=>$reply["childid"]        
      );
      $dataArrChild[]= $arrChild;
    } 
    $dbreply=null;    
  }
  
  $childid = count($dataArrChild);

  



//投稿ボタンを押したときの処理(親コメント送信)
  if (isset($_POST["send"] ) ===  true) {
    checkToken();
    if (!empty($_POST["message"])) {   
      $user = (isset( $_POST["user"] ) === true) ? $_POST["user"] : "";  
      $message = (isset( $_POST["message"] ) === true) ? trim($_POST["message"]) : "";      
      $postedAt = date("Y-m-d H:i:s");
      $parentid++;
      $message = str_replace("\t", " ", $message);
      $message = h($message);
      $user = str_replace("\t", " ", $user);

      if ($user === "")$user = "名無しさん";
      
      try {
        $st = $pdo->prepare("INSERT INTO bbs_res VALUES(?, ?, ?, ?)");
        $st->execute(array("$parentid", "$user", "$postedAt", "$message"));
      } catch (Exception $e) {
        echo "エラーがありました。";
        echo $e->getMessage();
        exit();
      }
      header("Location: ./index.php");   
    } else {
      $err_msg = "本文を入力して下さい。";
    }
  } else {
    setToken();
  }


//返信(子コメント)送信用
  if (isset($_POST["reply"] ) ===  true) {
    // checkToken_2();
    if ($message !== "") {   
      $user = (isset( $_POST["user"] ) === true) ? $_POST["user"] : "";  
      $message = (isset( $_POST["message"] ) === true) ? trim($_POST["message"]) : "";    
      $childid++;
      $postedAt = date("Y-m-d H:i:s");      
      $parentid = (isset( $_POST["id"] ) === true) ? $_POST["id"] : "";
      $message = str_replace("\t", " ", $message);
      $user = str_replace("\t", " ", $user);
      
      if ($user === "")$user = "名無しさん";

      try {
        $st = $pdo->prepare("INSERT INTO bbs_reply VALUES(?, ?, ?, ?, ?)");
        $st->execute(array("$parentid", "$user", "$postedAt", "$message", "$childid"));
      } catch (Exception $e) {
        echo "<span class='error'>エラーがありました。</span><br>";
        echo $e->getMessage();
        exit();
      }
      header("Location: ./index.php");   
    } else {
      $err_msg = "エラー！本文を入力して下さい。";
    }
  } else {
    // setToken_2();
  }


//ページネーションを作ろう
  $perPage = 5;
  $currentPageNumber = 0;
  $totalPage = ceil(count($dataArr)/$perPage);
  if(!empty($_GET["pagecount"])){
    $currentPageNumber = $_GET["pagecount"];
  }

  $displayStartNumber;
  
  if($currentPageNumber == 0){
    // ページが指定されていないとき（初訪問時） 
    $currentPageNumber = 1;
    // コンテンツ配列内のいくつ目から取り出すかを指定する数字 
    $displayStartNumber = 0;
  }else{
    // ページの指定があったとき（ページングリンクからの遷移） 
    $displayStartNumber = ($currentPageNumber -1) * $perPage;
   }

  //第一引数のリストの中から第二引数番目から第三引数件数分を抽出し配列で返す
  $dataArr_page = array_slice($dataArr,$displayStartNumber,$perPage);

  if($totalPage >= 2) {
    for($i = 1; $i <= $totalPage; $i++){
      $pagination[] = $i;
    }
  }



//親コメント削除機能
  if(!empty($_GET["delete"])) {
    $deleteTarget = $_GET["delete"];

    if($deleteTarget){
        try {
            // 親コメントの削除
            $st = $pdo->prepare("DELETE FROM bbs_res WHERE id = ?");
            $st->execute(array($deleteTarget));

            //親コメントの連番を振り直す
            $sql = "UPDATE bbs_res 
              SET bbs_res.id = b.Newid
              FROM bbs_res
              inner join(SELECT id,
              (ROW_NUMBER() OVER(ORDER BY date, date ASC))as Newid
              FROM bbs_res)as b
              on bbs_res.id = b.id;"; 
            $pdo->query($sql);


            // 子コメントの削除
            $st = $pdo->prepare("DELETE FROM bbs_reply WHERE parentid = ?");
            $st->execute(array($deleteTarget));

            header("Location: ./index.php");

            } catch (Exception $e) {
            echo "エラーがありました。";
            echo $e->getMessage();
            exit();
          }
    }
  }

// 子コメント削除機能
  if(!empty($_GET["deletereply"])) {
    $deleteReply = $_GET["deletereply"];

    if($deleteReply){
        try {
            //子コメントの削除 
            $st = $pdo->prepare("DELETE FROM bbs_reply WHERE childid = ?");
            $st->execute(array($deleteReply));

            //子コメントの連番を振り直す
            $sql = "SET @i := 0";
            $sql2 = "UPDATE bbs_reply SET childid = (@i := @i + 1)";    
            $pdo->query($sql);
            $pdo->query($sql2);

            header("Location: ./index.php");

            } catch (Exception $e) {
            echo "エラーがありました。";
            echo $e->getMessage();
            exit();
          }
    }
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
  <h2>投稿一覧（<?php echo h(count($dataArr)); ?>件）</h2>
  <?php echo $err_msg; ?> 
  <div class="container">
    <?php if(count($dataArr)):?>
      <?php foreach ($dataArr_page as $data) : ?>
        <div class="post-wrapper toggle-wrap">
            <input class="hidden" type="checkbox" name="toggle" id="toggle_checkbox<?php echo h($data["id"]); ?>" onclick="func1()">
            <label class="toggle-button" for="toggle_checkbox<?php echo h($data["id"]); ?>" onclick="func1()">
              <li class="post"><?php echo h($data["user"]); ?> / <?php echo h($data["postedAt"]); ?>              
              <a class="delete-button" href="?delete=<?php echo h($data["id"]); ?>">削除</a> / 
              <a class="edit-button" href="./edit-res.php?res=<?php echo h($data["id"]); ?>">編集</a>              
              <br><?php echo h($data["message"])?></li>
            </label>

          <!-- 返信があれば表示 -->
            <div class="toggle-content hidden" id="content_<?php echo h($data["id"]); ?>">
              <?php foreach ($dataArrChild as $rep) : ?>
                <?php if ($rep["id"] === $data["id"]) : ?>
                <li class="reply"><?php echo h($rep["user"]); ?> / <?php echo h($rep["postedAt"]); ?>
                <a class="delete-button" href="?deletereply=<?php echo h($rep["childid"]); ?>">削除</a> / 
                <a class="edit-button" href="./edit-reply.php?reply=<?php echo h($rep["childid"]); ?>">編集</a>
                <br><?php echo h($rep["message"]); ?></li>
                <?php endif; ?>
              <?php endforeach; ?>

              <form action="" method="post">
                投稿者:<input type="text" name="user" value="<?php if(!empty($user)) echo h($user); ?>"><br>
                本文:<br>
                <textarea name="message" placeholder="" required=""></textarea>
                <input name="reply" type="submit" value="返信">
                <!-- <input type="hidden" name="token" value="<?php //echo h($_SESSION2['token']); ?>"> -->
                <input type="hidden" name="id" value="<?php echo h($data["id"]); ?>">        
              </form>
            </div>  <!-- toggle-content -->
        </div>  <!-- post-wrapper -->    
      <?php endforeach; ?>
    <?php else :?>
        <li>まだ投稿はありません</li>
    <?php endif; ?>


    <div class="pagination">
      <?php if ($currentPageNumber > 1) : ?>
        <a href="?pagecount=<?php echo h($currentPageNumber - 1); ?>"><</a>
      <?php endif; ?>
      <?php foreach ($pagination as $pagecount) : ?>
        <?php if ($currentPageNumber == $pagecount) : ?>
          <a class="currentPage" href="?pagecount=<?php echo h($pagecount); ?>"><?php echo h($pagecount); ?></a>
        <?php else: ?>
          <a href="?pagecount=<?php echo h($pagecount) ?>"><?php echo h($pagecount); ?></a>
        <?php endif; ?>
      <?php endforeach?>
      <?php if ($currentPageNumber < $totalPage) : ?>
        <a href="?pagecount=<?php echo h($currentPageNumber + 1); ?>">></a>
      <?php endif; ?>
    </div>  <!-- pagination -->


    <!-- 送信フォーム -->
    <form class="send-form" action="" method="post">
      投稿者 : <input type="text" name="user" value="<?php if(!empty($user))echo h($user); ?>"><br>
      本文 : <br>
      <textarea name="message" placeholder="" required=""></textarea>
      <input name="send" type="submit" value="投稿">
      <input type="hidden" name="token" value="<?php echo h($_SESSION['token']); ?>">
    </form> <!-- send-form -->
   
  </div>

  <script src="https://code.jquery.com/jquery-3.3.1.js"></script>
  <script type="application/json" id="parentid">
    <?php print json_encode($parentid); ?>
  </script>
  <script type="text/javascript" src="./js/main.js"></script>
</body>
</html>