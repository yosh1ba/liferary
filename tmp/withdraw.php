<?php

// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　退会ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// ログイン認証
require('auth.php');

//================================
// 画面処理
//================================
// post送信されていた場合
if(!empty($_POST)){
    debug('POST送信があります。');
    debug('POST情報：'.print_r($_POST,true));

    // 例外処理
    try {
        // DBへ接続
        $dbh = dbConnect();

        // SQL文作成
        $sql1 = 'UPDATE users SET is_deleted = 1 WHERE id = :us_id';
        $sql2 = 'UPDATE posts SET is_deleted = 1 WHERE `user_id` = :us_id';
        $sql3 = 'UPDATE messages SET is_deleted = 1 WHERE `user_id` = :us_id';
        $sql4 = 'UPDATE likes SET is_deleted = 1 WHERE `user_id` = :us_id';
        // データ流し込み
        $data = array(':us_id' => $_SESSION['user_id']);
        // クエリ実行
        $stmt1 = queryPost($dbh, $sql1, $data);
        $stmt2 = queryPost($dbh, $sql2, $data);
        $stmt3 = queryPost($dbh, $sql3, $data);

        // クエリ成功の場合
        if($stmt1){
            session_destroy();
            debug('セッション変数の中身：'.print_r($_SESSION,true));
            debug('メインページへ遷移します');
            header('Location:index.php');
            exit;
        } else{
            debug('クエリが失敗しました');
            $err_msg['common'] = MSG_CMN;
        }
    } catch (Exception $e){
        error_log('エラー発生：'.$e->getMessage());
        $err_msg['common'] = MSG_CMN;
    }
}
debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<'); 
?>

<?php
$siteTitle = '退会';
require('head.php');
?>

<body>
    <!-- ヘッダー -->
    <?php
    require('header.php');
    ?>
    
    <!-- メインコンテンツ -->
    <div class="container mt-3">
        <section id="widthdraw" class="m-auto user-info-controll bg-light p-5">
            <h2 class="font-weight-bold text-center mb-5">退会</h2>
            <div class="<?php if(!empty($err_msg['common'])) echo 'alert alert-danger'; ?>" role="alert">
                <?php 
                if(!empty($err_msg['common'])) echo $err_msg['common'];
                ?>
            </div>
            <form action="" class="col text-center" method="post">
                <button type="submit" class="btn btn-success rounded-0 px-5 py-3 ml-auto mx-auto" name="submit">退会する</button>
            </form>
        </section>
    </div>

    <!-- フッター -->
    <?php
    require('footer.php');
    ?>