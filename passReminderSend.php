<?php

///共通変数・関数ファイル読み込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　パスワード再発行メール送信ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

//ログイン認証はなし（ログインできない人が使う画面なので）

//================================
// 画面処理
//======================
// POST送信されていた場合
if(!empty($_POST)){
    debug('POST送信があります。');
    debug('POST情報：'.print_r($_POST,true));

    // 変数にPOST情報を代入
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS);

    // 未入力チェック
    validRequired($email, 'email');

    if(empty($err_msg)){
        debug('未入力チェックOK');

        // emailの形式チェック
        validEmail($email, 'email');
        // emailの最大文字数チェック
        validMaxLen($email, 'email', 255);

        if(empty($err_msg)){
            debug('バリデーションチェックOK');

            // 例外処理
            try {
                //DBへ接続
                $dbh = dbConnect();
                // SQL文作成
                $sql = 'SELECT count(*) FROM users WHERE email = :email AND is_deleted = 0';
                $data = array(':email' => $email);
                // クエリ実行
                $stmt = queryPost($dbh, $sql, $data);
                // クエリ結果の値を取得
                $result = $stmt -> fetch(PDO::FETCH_ASSOC);

                // emailがDBに登録されている場合
                if($stmt && array_shift($result)){
                    debug('クエリ成功。DB登録あり。');
                    $_SESSION['msg_success'] = SUC_MAIL;

                    // 認証キー生成
                    $auth_key = makeRandKey();

                    // メールを送信
                    $from = 'info@liferary.com';
                    $to = $email;
                    $subject = '【パスワード再発行認証】｜ Liferary';
                    $comment = <<<EOT
本メールアドレス宛にパスワード再発行のご依頼がありました。
下記のURLにて認証キーをご入力頂くとパスワードが再発行されます。

パスワード再発行認証キー入力ページ：http://localhost:8888/webservice_output/passRemindRecieve.php
認証キー：{$auth_key}
※認証キーの有効期限は30分となります

認証キーを再発行されたい場合は下記ページより再度再発行をお願い致します。
http://localhost:8888/webservice_output/passRemindSend.php

////////////////////////////////////////
Liferary
URL  http://liferary.com/
E-mail info@liferary.com
////////////////////////////////////////
EOT;
                    sendMail($from, $to, $subject, $comment);

                    // 認証に必要な情報をセッションへ保存
                    $_SESSION['auth_key'] = $auth_key;
                    $_SESSION['auth_email'] = $email;
                    $_SESSION['auth_key_limit'] = time()+(60*30);
                    debug('セッション変数の中身：'.print_r($_SESSION,true));

                    // 認証キー入力ページへ
                    header("Location:passReminderRecieve.php");
                    exit;
                } else{
                    debug('クエリに失敗したかDBに登録のない情報が入力されました。');
                    $err_msg['common'] = MSG_CMN;
                }
            } catch (Exception $e){
                error_log('エラー発生：'.$e->getMessage());
                $err_msg['common'] = MSG_CMN;
            }
        }
    }
}
?>

<?php
$siteTitle = 'パスワード再発行';
require('head.php');
?>

<body>

    <!-- ヘッダー -->
    <?php
    require('header.php');
    ?>

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
            <p>ご指定のメールアドレス宛にパスワード再発行用のURLと認証キーをお送り致します。</p>
                <div class="form-group mb-4">
                    <label for="input-email">メールアドレス</label>
                    <input type="email" class="form-control  <?php if(!empty($err_msg['email'])) echo 'border-danger'; ?>" name="email" id="input-email" value="<?php echo getFormData('email'); ?>">
                    <small id="emailHelp" class="form-text">
                        <?php
                        if(!empty($err_msg['email'])) echo $err_msg['email'];
                        ?>
                    </small>
                </div>
                <button type="submit" class="btn btn-success rounded-0 px-4 py-2 ml-auto float-right">送信</button>
                
            </form>
        </section>
    </div>
    </div>
    <!-- フッター -->
    <?php
    require('footer.php');
    ?>