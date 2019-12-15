<?php

//共通変数・関数ファイルを読み込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　パスワード再発行認証キー入力ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// ログイン認証なし。

// SESSIONに認証キーがあるか確認、なければリダイレクト
if(empty($_SESSION['auth_key'])){
    header("Location:passReminaderSend.php");
}

//================================
// 画面処理
//================================
//post送信されていた場合
if(!empty($_POST)){
    debug('POST情報があります。');
    debug('POST情報：'.print_r($_POST,true));

    // 変数に認証キーを代入
    $auth_key = $_POST['token'];

    // 未入力チェック
    validRequired($auth_key, 'token');

    if(empty($err_msg)){
        debug('未入力チェックOK');

        // 固定長チェック
        validRequired($auth_key, 'token');
        // 半角チェック
        validHalf($auth_key, 'token');

        if(empty($err_msg)){
            debug('バリデーションチェックOK');

            if($auth_key !== $_SESSION['auth_key']){
                $err_msg['common'] = MSG_AUTH;
            }
            if(time() > $_SESSION['auth_key_limit']){
                $err_msg['common'] = MSG_LIMIT;
            }

            if(empty($err_msg)){
                debug('認証OK');

                // パスワード生成
                $pass = makeRandKey();

                // 例外処理
                try {
                    // DBへ接続
                    $dbh = dbConnect();
                    // SQL文作成
                    $sql = 'UPDATE users SET password = :pass WHERE email = :email AND is_deleted = 0';
                    $data = array(':pass' => password_hash($pass,PASSWORD_DEFAULT), ':email' => $_SESSION['auth_email']);
                    // クエリ実行
                    $stmt = queryPost($dbh, $sql, $data);

                    // クエリ成功の場合
                    if($stmt){
                        debug('クエリ成功');

                        // メール送信
                        $from = 'info@liferary.com';
                        $to = $_SESSION['auth_email'];
                        $subject = '【パスワード再発行完了】｜ Liferary';
                        $comment = <<<EOT
本メールアドレス宛にパスワードの再発行を致しました。
下記のURLにて再発行パスワードをご入力頂き、ログインください。

ログインページ：https://liferary.yosh1ba.com/passRemindRecieve.php
再発行パスワード：{$pass}
※ログイン後、パスワードのご変更をお願い致します

////////////////////////////////////////
Liferary
URL  https://liferary.yosh1ba.com/
E-mail info@liferary.com
////////////////////////////////////////
EOT;
                        sendMail($from, $to, $subject, $comment);

                        // セッション削除。セッションIDは残したままにする。
                        session_unset();
                        $_SESSION['msg_success'] = SUC_MAIL;
                        debug('セッションの中身：'.print_r($_SESSION,true));

                        // ログインページへ
                        header("Location:login.php");
                    } else{
                        debug('クエリに失敗しました。');
                        $err_msg['common'] = MSG_CMN;
                    }
                } catch (Exception $e){
                    error_log('エラー発生：'.$e-> getMessage());
                    $err_msg['common'] = MSG_CMN;
                }
            }
        }
    }
}

?>

<?php
$siteTitle = 'パスワード再発行認証';
require('head.php');
?>

<body>

    <!-- ヘッダー -->
    <?php
    require('header.php');
    ?>

    <p id="js-show-msg" style="display:none;" class="msg-slide">
    <?php echo getSessionFlash('msg_success'); ?>
    </p>

    <!-- メインコンテンツ -->
    <div class="bg-image">
    <div class="container py-5">
        <section id="pass-reminder-send" class="m-auto user-info-controll bg-light p-5">
            <h2 class="font-weight-bold text-center mb-5">パスワード再発行</h2>
            <div class="<?php if(!empty($err_msg['common'])) echo 'alert alert-danger'; ?>" role="alert">
                <?php 
                if(!empty($err_msg['common'])) echo $err_msg['common'];
                ?>
            </div>
            <form action="" method="post">
            <p>ご指定のメールアドレスお送りした【パスワード再発行認証】メール内にある「認証キー」をご入力ください。</p>
                <div class="form-group mb-4">
                    <label for="input-text">認証キー</label>
                    <input type="text" class="form-control   <?php if(!empty($err_msg['token'])) echo 'border-danger'; ?>" name="token" id="input-text" value="<?php echo getFormData('token'); ?>">
                    <small id="tokenHelp" class="form-text">
                    <?php
                    if(!empty($err_msg['email'])) echo $err_msg['email'];
                    ?>
                    </small>
                </div>
                <a href="passReminderSend.php" class="d-block">&lt; パスワード再発行メールを再度送信する</a>
                <button type="submit" class="btn btn-success rounded-0 px-4 py-2 ml-auto float-right">再発行</button>
            </form>
        </section>
    </div>
    </div>
    
    <!-- フッター -->
    <?php
    require('footer.php');
    ?>