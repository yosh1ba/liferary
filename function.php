<?php
//================================
// ログ
//================================
//ログを取るか
ini_set('log_errors','on');
//ログの出力ファイルを指定
ini_set('error_log','php.log');

//================================
// デバッグ
//================================
//デバッグフラグ
$debug_flg = false;
//デバッグログ関数
function debug($str){
  global $debug_flg;
  if(!empty($debug_flg)){
    error_log('デバッグ：'.$str);
  }
}

//================================
// セッション準備・セッション有効期限を延ばす
//================================
//セッションファイルの置き場を変更する（/var/tmp/以下に置くと30日は削除されない）
session_save_path("/var/tmp/");
//ガーベージコレクションが削除するセッションの有効期限を設定（30日以上経っているものに対してだけ１００分の１の確率で削除）
ini_set('session.gc_maxlifetime', 60*60*24*30);
//ブラウザを閉じても削除されないようにクッキー自体の有効期限を延ばす
ini_set('session.cookie_lifetime', 60*60*24*30);
//セッションを使う
session_start();
//現在のセッションIDを新しく生成したものと置き換える（なりすましのセキュリティ対策）
session_regenerate_id();

//================================
// 画面表示処理開始ログ吐き出し関数
//================================
function debugLogStart(){
    debug('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> 画面表示処理開始');
    debug('セッションID：'.session_id());
    debug('セッション変数の中身：'.print_r($_SESSION,true));
    debug('現在日時タイムスタンプ：'.time());
    if(!empty($_SESSION['login_date']) && !empty($_SESSION['login_limit'])){
      debug( 'ログイン期限日時タイムスタンプ：'.( $_SESSION['login_date'] + $_SESSION['login_limit'] ) );
    }
}

//================================
// 定数
//================================
//エラーメッセージを定数に設定
define('MSG_REQ', '入力必須です');
define('MSG_CMN', 'エラーが発生しました。しばらく経ってからやり直してください。');
define('MSG_EMAIL', 'Emailの形式で入力して下さい');
define('MSG_EMAIL_DUP','このメールアドレスは既に登録されています');
define('MSG_MIN_LEN', '入力文字数が不足しています');
define('MSG_MAX_LEN', '入力可能文字数をオーバーしています'); 
define('MSG_HALF', '半角英数字で入力してください');
define('MSG_UNMATCH', 'パスワードが一致しません');
define('MSG_UNMATCH_EOB', 'メールアドレスまたはパスワードが一致しません');
define('MSG_NOCHANGE', '古いパスワードと同じパスワードは設定できません');
define('MSG_AUTH', '認証キーが一致しません');
define('MSG_LIMIT', '有効期限が切れています');
define('MSG_SELECT', 'カテゴリを正しく入力してください');
define('MSG_ISBN', 'ISBNコードを入力してください');
define('MSG_BOOK', '書籍情報がヒットしませんでした');
define('SUC_PROF', 'プロフィールを更新しました');
define('SUC_PASS', 'パスワードを変更しました。');
define('SUC_MAIL', 'メールを送信しました');
define('SUC_SUBMIT', '登録しました');
define('SUC_DELETE', '削除しました');
define('SUC_MES', 'メッセージを送信しました');

//================================
// グローバル変数
//================================
//エラーメッセージ格納用の配列
$err_msg = array();

//================================
// バリデーション関数
//================================

// バリデーションチェック（未入力チェック）
function validRequired($str,$key){
  if($str === ''){
    global $err_msg;
    $err_msg[$key] = MSG_REQ;
  }
}

// バリデーション関数（Email形式チェック）
function validEmail($str, $key = 'email'){
  if(!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $str)){
    global $err_msg;
    $err_msg[$key] = MSG_EMAIL;
  }
}

// バリデーション関数（Email重複チェック）
function validEmailDup($str, $key){
  global $err_msg;
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    // SQL文作成
    $sql = 'SELECT count(*) FROM users WHERE email = :email AND is_deleted = 0 ';
    $data = array(':email' => $str);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);
    // クエリ結果の取得
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    // 判定
    if(!empty(array_shift($result))){
      $err_msg[$key] = MSG_EMAIL_DUP;
    }
  } catch (Exception $e){
    error_log('エラー発生：'. $e->getMessage());
    $err_msg['common'] = MSG_CMN;
  }
}

// バリデーションチェック（半角英数字チェック）
function validHalf($str, $key){
  if(!preg_match("/^[a-zA-Z0-9]+$/", $str)){
    global $err_msg;
    $err_msg[$key] = MSG_HALF;
  }
}

// バリデーション関数（同値チェック）
function validMatch($str1, $str2, $key){
  if($str1 !== $str2){
    global $err_msg;
    $err_msg[$key] = MSG_UNMATCH;
  }
}

// バリデーション関数（最小文字数チェック）
function validMinLen($str, $key, $min){
  if(mb_strlen($str) < $min){
    global $err_msg;
    $err_msg[$key] = MSG_MIN_LEN;
  }
}

// バリデーション関数（最大文字数チェック）
function validMaxLen($str, $key, $max){
  debug('最大文字数チェック中！：'. $max);
  if(mb_strlen($str) > $max){
    global $err_msg;
    $err_msg[$key] = MSG_MAX_LEN;
  }
}

// パスワードチェック
function validPass($str, $key, $min = 8, $max = 255){
  // 半角英数字チェック
  validHalf($str, $key);
  // 最小文字数チェック
  validMinLen($str, $key, $min);
  // 最大文字数チェック
  validMaxLen($str, $key, $max);
}

// バリデーション関数（selectチェック）
function validSelect($str, $key){
  if(!preg_match("/^[1-9]+$/", $str)){
    global $err_msg;
    $err_msg[$key] = MSG_SELECT;
  }
}

// バリデーションチェック（書籍情報の有無）
function searchBook($isbn){
  debug('DBに書籍情報が存在するかチェックします。');
  debug('ISBN：'.$isbn);
  global $err_msg;
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    // SQL文作成
    $sql = 'SELECT count(*) FROM books WHERE isbn = :isbn AND is_deleted = 0 ';
    $data = array(':isbn' => $isbn);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);
    // クエリ結果の取得
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    // 判定
    if(!empty(array_shift($result))){
      // 既に書籍情報がDBに存在する場合
      return true;
    } else{
      return false;
    }
  } catch (Exception $e){
    error_log('エラー発生：'. $e->getMessage());
    $err_msg['common'] = MSG_CMN;
  }
}

//================================
// ログイン認証
//================================
function isLogin(){
  // ログインしている場合
  if( !empty($_SESSION['login_date']) ){
    debug('ログイン済みユーザーです。');

    // 現在日時が最終ログイン日時＋有効期限を超えていた場合
    if( ($_SESSION['login_date'] + $_SESSION['login_limit']) < time()){
      debug('ログイン有効期限オーバーです。');

      // セッションを削除（ログアウトする）
      session_destroy();
      return false;
    }else{
      debug('ログイン有効期限以内です。');
      return true;
    }

  }else{
    debug('未ログインユーザーです。');
    return false;
  }
}

//================================
// データベース
//================================
//DB接続関数
function dbConnect(){
  // DBへの接続準備
  // ローカル用
  // $dsn = 'mysql:dbname=liferary;host=localhost;charset=utf8';
  // $user = 'root';
  // $password = 'root';

  require('dbConnect.php');

  $options = array(
    // SQL実行失敗時にはエラーコードのみ設定
    PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
    // デフォルトフェッチモードを連想配列形式に設定
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // バッファードクエリを使う(一度に結果セットをすべて取得し、サーバー負荷を軽減)
    // SELECTで得た結果に対してもrowCountメソッドを使えるようにする
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
  );
  // PDOオブジェクト生成（DBへ接続）
  $dbh = new PDO($dsn, $user, $password, $options);
  return $dbh;
}

// クエリ実行関数
function queryPost($dbh, $sql, $data){
  // クエリ作成
  $stmt = $dbh->prepare($sql);
  // プレースホルダーに値をセットし、SQL文を実行
  if(!$stmt->execute($data)){
    global $err_msg;
    debug('クエリに失敗しました。');
    debug('失敗したSQL：'.print_r($stmt, true));
    $err_msg['common'] = MSG_CMN;
    return 0;
  }
  debug('クエリ成功');
  return $stmt;
}

// ユーザー情報取得
function getUser($u_id){
  debug('ユーザー情報を取得します');
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    // SQL文を作成
    $sql = 'SELECT * FROM users WHERE id = :u_id AND is_deleted = 0';
    $data = array(':u_id' => $u_id);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    // クエリ成功の場合
    if($stmt){
      return $stmt->fetch(PDO::FETCH_ASSOC);
    } else{
      return false;
    }
  } catch(Exception $e){
    error_log('エラー発生：'.$e->getMessage());
  }
}

// 年齢カテゴリー取得
function getAgeCategory(){
  debug('年齢カテゴリー情報を取得します');
  // 例外処理
  try {
    // DB接続
    $dbh = dbConnect();
    // SQL文作成
    $sql = 'SELECT * FROM ages';
    $data = array();
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    // クエリ成功の場合
    if($stmt){
      return $stmt->fetchAll();
    } else {
      return false;
    }
  } catch(Exception $e){
    error_log('エラー発生：'.$e->getMessage());
  }
}

// 年代カテゴリー取得
function getPeriodCategory(){
  debug('年代カテゴリー情報を取得します');
  // 例外処理
  try {
    // DB接続
    $dbh = dbConnect();
    // SQL文作成
    $sql = 'SELECT * FROM periods';
    $data = array();
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    // クエリ成功の場合
    if($stmt){
      return $stmt->fetchAll();
    } else {
      return false;
    }
  } catch(Exception $e){
    error_log('エラー発生：'.$e->getMessage());
  }
}

// 年代カテゴリーから一つを取得
function getPeriodCategoryOne($p_id){
  debug('該当する年代情報を取得します。');
  // 例外
  try {
    $dbh = dbConnect();
    $sql = 'SELECT per.name FROM periods AS per LEFT JOIN posts AS pos ON per.id = pos.period_id WHERE pos.id = :p_id AND pos.is_deleted = 0';
    $data = array(':p_id' => $p_id);

    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    if($stmt){
      // クエリ結果のデータを１レコード返却
      return $stmt->fetch(PDO::FETCH_ASSOC);
    }else{
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生:' . $e->getMessage());
  }
}


// 投稿番号取得
function getPostId($u_id){
  debug('投稿IDを取得します');
  debug('ユーザーID：'.$u_id);
  // 例外処理
  try {
    $dbh = dbConnect();
    $sql = 'SELECT id FROM posts WHERE user_id = :u_id AND is_deleted = 0';
    $data = array(':u_id' => $u_id) ;

    $stmt = queryPost($dbh, $sql, $data);

    // クエリ実行結果
    if($stmt){
      global $err_msg;
      // クエリ結果の１レコードを返却
      return $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
      return false;
    }
  } catch(Exception $e){
    error_log('エラー発生：'.$e->getMessage());
    $err_msg['common'] = MSG_CMN;
  }
}

// 投稿情報取得
function getPost($u_id, $p_id){
  debug('投稿情報を取得します。');
  debug('ユーザーID：'.$u_id);
  debug('投稿ID：'.$p_id);
  // 例外処理
  try{
    global $err_msg;
    // DB接続
    $dbh = dbConnect();
    // SQL文作成
    $sql = 'SELECT * FROM posts WHERE id = :p_id AND user_id = :u_id AND is_deleted = 0';
    $data = array(
      ':u_id' => $u_id,
      ':p_id' => $p_id
    );
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    // クエリ実行結果
    if($stmt){
      // クエリ結果の１レコードを返却
      return $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
      return false;
    }
  } catch(Exception $e){
    error_log('エラー発生：'.$e->getMessage());
    $err_msg['common'] = MSG_CMN;
  }
}

// DBから投稿情報の一覧を取得
function getPostList($currentMinNum = 1, $period, $span = 5, $sort){
  debug('投稿情報の一覧を取得します');
  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    // 一覧件数取得用SQL作成
    $sql = 'SELECT id FROM posts WHERE is_deleted = 0';
    // 年代に指定がある場合は条件文を追加
    if(!empty($period)){
      $sql .= ' AND period_id = :period_id';
      // データ流し込み
      $data = array(':period_id' => $period);
    } else {
      $data = array();
    }
    // クエリ実行
    debug('一覧件数取得用SQL：'.$sql);
    $stmt = queryPost($dbh, $sql, $data);
    // クエリ成功の場合
    if($stmt){
      // 総レコード数を取得
      $rst['total'] = $stmt->rowCount();
      debug('総レコード件数：'.$rst['total']);
      // 総ページ数を取得
      $rst['total_page'] = ceil($rst['total']/$span);
    } else {
      return false;
    }

    // ページング用SQL文作成
    $sql = 'SELECT pos.id AS post_id, b.title, b.author, b.picture, u.name AS user_name, pos.comment, per.name AS period_name, pos.created_on AS post_date 
    FROM posts AS pos
    LEFT JOIN books AS b ON pos.isbn = b.isbn
    LEFT JOIN users AS u ON pos.user_id = u.id
    LEFT JOIN periods AS per ON pos.period_id = per.id
    WHERE pos.is_deleted = 0';
    // 年代に指定がある場合は条件文を追加
    if(!empty($period)){
      $sql .= ' AND pos.period_id = :period_id';
      // データ流し込み
      $data = array(':period_id' => $period);
    } else{
      $data = array();
    }
    // ソート順
    if(!empty($sort)){
      switch($sort){
        case 1:
          $sql .= ' ORDER BY pos.created_on ASC';
          break;
        case 2:
          $sql .= ' ORDER BY pos.created_on DESC';
          break;
      }
    }    

    // 1ページあたりの表示件数を設定
    $sql .= ' LIMIT '.$span.' OFFSET '.$currentMinNum;

    // クエリ実行
    debug('ページング用SQL：'.$sql);
    $stmt = queryPost($dbh, $sql, $data);

    if($stmt){
      // クエリ結果の全レコードを格納
      $rst['data'] = $stmt->fetchAll();
      return $rst;
    } else{
      return false;
    }
  } catch(Exception $e){
    error_log('エラー発生：'.$e->getMessage());
  }
}

// DBから投稿データを取得
function getPostOne($p_id){
  debug('投稿情報と書籍情報を取得します。');
  debug('投稿ID：'.$p_id);

  // 例外処理
  try {
    // DBへ接続
    $dbh = dbConnect();
    // SQL文作成
    $sql = 'SELECT p.id AS post_id, user_id, period_id, detail, title, author, published_on, picture 
            FROM posts AS p LEFT JOIN books AS b ON p.isbn = b.isbn 
            WHERE p.id = :p_id AND p.is_deleted = 0 AND b.is_deleted = 0';
    $data = array(':p_id' => $p_id);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    if($stmt){
      // クエリ結果のデータを１レコード返却
      return $stmt->fetch(PDO::FETCH_ASSOC);
    }else{
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生:' . $e->getMessage());
  }
}


// 書籍情報取得（API）
function getBookData($isbn){
  debug('書籍情報を取得します。');
  debug('ISBN：'.$isbn);

  // APIの基本となるURL
  $base_url = 'https://www.googleapis.com/books/v1/volumes?q=isbn:';
  // 基本となるURLにISBNコードを付け足す
  $url = $base_url.$isbn;
  // 書籍情報を取得
  $json = file_get_contents($url);
  // デコード（$jsonをオブジェクトに変換する）
  $data = json_decode($json,true);

  if(($data['totalItems']) === 0){
    $books = null;
  }else{
    // 書籍情報を格納
    $books = $data['items'][0]['volumeInfo'];
  }
  // 書籍情報を返す
  return $books;
}

// メッセージ情報取得
function getMessage($p_id){
  debug('メッセージ情報を取得します。');
  debug('投稿ID：'.$p_id);
  // 例外処理
  try {
    $dbh = dbConnect();
    $sql = 'SELECT m.id, m.post_id, `user_id`, u.name, u.icon, `message` FROM messages AS m LEFT JOIN users AS u ON `user_id` = u.id WHERE post_id = :p_id AND m.is_deleted =0';
    $data = array(':p_id' => $p_id);

    $stmt = queryPost($dbh, $sql, $data);

    // クエリ成功の場合
    if($stmt){
      return $stmt->fetchAll();
    } else {
      return false;
    }
  } catch(Exception $e){
    error_log('エラー発生：'.$e->getMessage());
  }
}

// 自分が送信したメッセージ情報を取得
function getMyMessage($u_id){
  debug('自分が送信したメッセージ情報を取得します。');
  try {
    $dbh = dbConnect();
    $sql = 'SELECT m.post_id, m.created_on, u.name, m.message FROM messages AS m
      LEFT JOIN posts AS p ON m.post_id = p.id
      LEFT JOIN users AS u ON p.user_id = u.id
      WHERE m.is_deleted = 0
      AND m.user_id = :u_id
      ORDER BY m.created_on ASC
      LIMIT 5';
    $data = array(':u_id' => $u_id);
    $stmt = queryPost($dbh, $sql, $data);
    if($stmt){
      return $stmt->fetchAll();
    } else {
      return false;
    }
  } catch(Exception $e){
    error_log('エラー発生：'.$e->getMessage());
  }
}

// お気に入り情報があるか判定
function isLike($u_id, $p_id){
  debug('お気に入り情報があるか判定します。');
  debug('ユーザーID：'.$u_id);
  debug('商品ID：'.$p_id);

  // 例外処理
  try {
    $dbh = dbConnect();
    $sql = 'SELECT * FROM likes WHERE post_id = :p_id AND user_id = :u_id';
    $data = array(':p_id' => $p_id, ':u_id' => $u_id);
    $stmt = queryPost($dbh, $sql, $data);
    debug('like用SQL:'.print_r($stmt,true));

    if($stmt->rowCount()){
      debug('お気に入りです。');
      return true;
    } else{
      debug('特に気に入ってません');
      return false;
    }
  } catch(Exception $e){
    error_log('エラー発生：'.$e->getMessage());
  }
}

// 自分がお気に入りに入れた投稿情報を取得
function getMyLike($u_id){
  debug('自分のお気に入りデータを取得します');
  try {
    $dbh = dbConnect();
    $sql = 'SELECT l.id AS like_id, l.post_id AS post_id, u.name, b.picture FROM likes AS l
      LEFT JOIN posts AS p ON l.post_id = p.id
      LEFT JOIN users AS u ON p.user_id = u.id
      LEFT JOIN books AS b ON p.isbn = b.isbn
      WHERE l.user_id = :u_id
      AND l.is_deleted = 0
      LIMIT 10';
    $data = array('u_id' => $u_id);
    $stmt = queryPost($dbh, $sql, $data);
    if($stmt){
      return $stmt->fetchAll();
    } else {
      return false;
    }
  } catch(Exception $e){
    error_log('エラー発生：'.$e->getMessage());
  }
}

// 自分の投稿情報を取得する
function getMyPost($u_id){
  debug('自分の投稿情報を取得します。');
  try {
    $dbh = dbConnect();
    $sql = 'SELECT pos.id AS post_id, b.title, b.author, b.picture, u.name AS user_name, pos.comment, per.name AS period_name, pos.created_on AS post_date 
      FROM posts AS pos
      LEFT JOIN books AS b ON pos.isbn = b.isbn
      LEFT JOIN users AS u ON pos.user_id = u.id
      LEFT JOIN periods AS per ON pos.period_id = per.id
      WHERE pos.user_id = :u_id AND pos.is_deleted = 0';
    $data = array(':u_id' => $u_id);
    $stmt = queryPost($dbh, $sql, $data);
    if($stmt){
      return $stmt->fetch(PDO::FETCH_ASSOC);
    } else{
      return false;
    }
  } catch(Exception $e){
    error_log('エラー発生：'.$e->getMessage());
  }
}


//================================
// メール送信
//================================
function sendMail($from, $to, $subject, $comment){
  if(!empty($to) && !empty($subject) && !empty($comment)){
    // 文字化けしないように設定
    mb_language('Japanese');
    mb_internal_encoding('UTF-8');

    // メール送信
    $result = mb_send_mail($to, $subject, $comment, "From: ".$from);
    // 送信結果を判定
    if($result){
      debug('メールを送信しました');
    } else{
      debug('【エラー発生】メールの送信に失敗しました');
    }
  }
}

//================================
// その他
//================================
// サニタイズ
function sanitize($str){
  return htmlspecialchars($str,ENT_QUOTES);
}
// フォーム入力保持
function getFormData($str, $isGet = false){
  if($isGet){
    $method = $_GET;
  } else{
    $method = $_POST;
  }
  global $dbFormData;
  global $err_msg;
  // ユーザデータがある場合
  if(!empty($dbFormData)){
    // フォームにエラーがある場合
    if(!empty($err_msg[$str])){
      // POSTにデータがある場合、POSTの値を再表示させる
      if(isset($method[$str])){
        return sanitize($method[$str]);
      // POSTにデータがない場合、DBの値を再表示させる
      }else {
        return sanitize($dbFormData[$str]);
      }
    // フォームにエラーがない場合、DBの値との差異をみる
    }else {
      // POSTにデータがありかつ、DBの値と異なる場合
      if(isset($method[$str]) && $method[$str] !== $dbFormData[$str]){
        return sanitize($method[$str]);
      // POST内容とDBに差異がなければDBの値を再表示する
      } else {
        return sanitize($dbFormData[$str]);
      }
    }
  // フォームへの入力がない場合
  } else{
    if(isset($method[$str])){
      return sanitize($method[$str]);
    }
  }
}

// フォーム入力保持（書籍情報）
function getFormData2($str){
  global $dbFormData;
  global $err_msg;
  // ユーザデータがある場合
  if(!empty($dbFormData)){
    // フォームにエラーがある場合
    if(!empty($err_msg[$str])){
      // POSTにデータがある場合、POSTの値を再表示させる
      if(isset($_POST[$str])){
        return sanitize($_POST[$str]);
      // POSTにデータがない場合、DBの値を再表示させる
      }else {
        return sanitize($dbFormData[$str]);
      }
    // フォームにエラーがない場合、DBの値との差異をみる
    }else {
      // POSTにデータがありかつ、DBの値と異なる場合
      if(isset($_POST[$str]) && $_POST[$str] !== $dbFormData[$str]){
        return sanitize($_POST[$str]);
      // POST内容とDBに差異がなければDBの値を再表示する
      } else {
        return sanitize($dbFormData[$str]);
      }
    }
  // フォームへの入力がない場合
  } else{
    if(isset($_POST[$str])){
      return sanitize($_POST[$str]);
    }
  }
}


// 画像アップロード
function uploadImg($file, $key){
  debug('画像アップロード処理開始');
  debug('FILE情報：'.print_r($file,true));

  // FILE情報が存在する場合
  if(isset($file['error']) && is_int($file['error'])){
    try {
      switch ($file['error']){
        case UPLOAD_ERR_OK: //エラーなしの場合、switchをエスケープする
          break;
        case UPLOAD_ERR_NO_FILE:  //ファイル未選択の場合
          throw new RuntimeException('ファイルが選択されていません');
        case UPLOAD_ERR_INI_SIZE: //php.ini定義の最大サイズが超過した場合
          throw new RuntimeException('ファイルサイズが大きすぎます');
        case UPLOAD_ERR_FORM_SIZE:  // フォーム定義の最大サイズを超過した場合
          throw new RuntimeException('ファイルサイズが大きすぎます');
        default:  // その他の場合
          throw new RuntimeException('その他のエラーが発生しました');
      }

      // ファイル形式のチェック
      $type = @exif_imagetype($file['tmp_name']);
      // in_array関数の第三引数にtrueを設定すると、厳密にチェックしてくれるため必ずつける
      if(!in_array($type, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG], true)){
        throw new RuntimeException('画像形式が未対応です');
      }

      // ファイルデータからSHA-1ハッシュを取ってファイル名を決定し、ファイルを保存する
      // ハッシュ化しておかないとアップロードされたファイル名そのままで保存してしまうと同じファイル名がアップロードされる可能性があり、
      // DBにパスを保存した場合、どっちの画像のパスなのか判断つかなくなってしまう
      // image_type_to_extension関数はファイルの拡張子を取得するもの
      $path = 'uploads/'.sha1_file($file['tmp_name']).image_type_to_extension($type);

      // ファイルを移動する
      if(!move_uploaded_file($file['tmp_name'], $path)){
        throw new RuntimeException('ファイル保存時にエラーが発生しました');
      }

      // 保存したファイルパスのパーミッション（権限）を変更する
      chmod($path,0644);

      debug('ファイルがアップロードされました');
      debug('ファイルパス：'.$path);
      return $path;

    } catch (RuntimeException $e){
      global $err_msg;
      $err_msg[$key] = $e->getMessage();
    }
  }
}

// ランダムキー作成
function makeRandKey($length = 8){
  static $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJLKMNOPQRSTUVWXYZ0123456789'; //61個
  $str = '';
  for($i =0; $i<$length; $i++){
    // ランダムに1文字を抽出し、繋げる
    $str .= $chars[mt_rand(0,61)];
  }
  return $str;
}

// 一回だけセッションを取得
function getSessionFlash($key){
  if(!empty($_SESSION[$key])){
    $data = $_SESSION[$key];
    $_SESSION[$key] = '';
    return $data;
  }
}

function pagination($currentPageNum, $totalPageNum, $totalData, $link='', $pageColNum = 5){
  // ページネーションの数は5つを限度とする

  // $currentPageNum：現在のページ番号
  // $totalPageNum：総ページ数
  // $totalPageNum：総データ数
  // $pageColNum：ページネーション表示数

  // 表示させるページの範囲
  // $minPageNum：最小値
  // $maxPageNum：最大値

  // 現在のページが総ページ数と同じかつ、総ページ数が表示数以上なら左にリンクを４個出す
  if($currentPageNum == $totalPageNum && $totalPageNum >= $pageColNum ){
    $minPageNum = $currentPageNum - 4;
    $maxPageNum = $currentPageNum;
  
  // 現在のページが総ページ数の1ページ前なら左にリンク3個、右に1個出す
  } elseif($currentPageNum == ($totalPageNum -1) && $totalPageNum > $pageColNum){
    $minPageNum = $currentPageNum -3;
    $maxPageNum = $currentPageNum +1;
  
  // 現在のページが2の場合は、左にリンク1個、右に3個出す
  } elseif($currentPageNum == 2 && $totalPageNum > $pageColNum){
    $minPageNum = $currentPageNum -1;
    $maxPageNum = $currentPageNum +3;
  
  // 現在のページが1の場合は、左に何も出さず、右に4個出す
  } elseif($currentPageNum == 1 && $totalPageNum > $pageColNum){
    $minPageNum = $currentPageNum;
    $maxPageNum = $currentPageNum +4;
  
  // 総ページ数が表示項目数より少ない場合は、総ページ数をループのMax、Minを1とする
  } elseif($totalPageNum < $pageColNum){
    $minPageNum = 1;
    $maxPageNum = $totalPageNum;
  // それ以外は、左右に2個ずつだす  
  } else{
    $minPageNum = $currentPageNum -2;
    $maxPageNum = $currentPageNum +2;
  }

  // 検証用
  debug('現在のページ番号：'.$currentPageNum);
  debug('総ページ数：'.$totalPageNum);
  debug('総データ数：'.$totalData);
  debug('ページネーション表示数：'.$pageColNum);

  // 以下、画面表示用ページネーション設定
  echo '<ul class="pagination justify-content-center">';
    echo '<li class="page-item ';
    if($currentPageNum == 1 || $totalData == 0){
      echo 'd-none';
    }
    echo '">';
        echo '<a class="page-link" href="?p=1'.$link.'" aria-label="前">';
            echo '<span aria-hidden="true">&laquo;</span>';
        echo '</a>';
    echo '</li>';
    
    for($i=1; $i<=$maxPageNum; $i++){
      echo '<li class="page-item ';
      if($currentPageNum == $i){echo 'active';}
      echo '"><a class="page-link" href="?p='.$i.$link.'">'.$i.'</a></li>';
    }
  
    echo '<li class="page-item ';
    if($currentPageNum == $totalPageNum || $totalData == 0){
      echo 'd-none';
    }
    echo '">';
        echo '<a class="page-link" href="?p='.$totalPageNum.$link.'" aria-label="次">';
            echo '<span aria-hidden="true">&raquo;</span>';
        echo '</a>';
    echo '</li>';
  echo '</ul>';
}
?>