<?php

// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　ログインページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// ログイン認証
require('auth.php');

// POST送信されていた場合
if(!empty($_POST)){
    debug('POST送信があります。');
    debug('POST情報：'.print_r($_POST,true));

    // 変数にユーザ情報を代入
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS);
    $pass = filter_input(INPUT_POST, 'pass', FILTER_SANITIZE_SPECIAL_CHARS);
    $pass_save = (!empty($_POST['pass_save'])) ? true : false;

    // 未入力チェック
    validRequired($email,'email');
    validRequired($pass,'pass');

    // Emailの形式チェック
    validEmail($email, 'email');
    // Emailの最大文字数チェック
    validMaxLen($email, 'email', 255);

    // パスワードの半角英数字チェック
    validHalf($pass,'pass');
    // パスワードの最小文字数チェック
    validMinLen($pass, 'pass', 8);
    // パスワードの最大文字数チェック
    validMaxLen($pass, 'pass', 255);

    if(empty($err_msg)){

        debug('バリデーションOKです。');

        // 例外処理
        try {
            // DBへ接続
            $dbh = dbConnect();
            // SQL文作成
            $sql = 'SELECT password,id FROM users WHERE email = :email AND is_deleted = 0';
            $data = array(':email' => $email);
            // クエリ実行
            $stmt = queryPost($dbh, $sql, $data);

            // クエリ結果の値を取得
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            debug('クエリ結果の中身：'.print_r($result,true));

            // パスワード照合
            if(!empty($result) && password_verify($pass, array_shift($result))){
                debug('パスワードがマッチしました。');
                // ログイン有効期限（デフォルトを1時間とする）
                $sessLimit = 60*60;
                // 最終ログイン日時を現在日時に
                $_SESSION['login_date'] = time();

                // ログイン保持にチェックがある場合
                if($pass_save){
                    debug('ログイン保持にチェックがあります。');
                    // ログイン有効期限を30日にセット
                    $_SESSION['login_limit'] = $sessLimit * 24 * 30;
                }else {
                    debug('ログイン保持にチェックはありませんでした。');
                    // 次回からログイン保持しないので、ログイン有効期限を1時間にセット
                    $_SESSION['login_limit'] = $sessLimit;
                }
                // ユーザーIDを格納
                $_SESSION['user_id'] = $result['id'];

                debug('セッション変数の中身：'.print_r($_SESSION,true));
                debug('マイページへ遷移します。');
                // マイページへ遷移する
                header("Location:mypage.php");
                exit;
            }else {
                debug('パスワードがアンマッチです');
                $err_msg['common'] = MSG_UNMATCH_EOB;
            }
        }catch (Exception $e){
            error_log('エラー発生：'. $e->getMessage());
            $err_msg['common'] = MSG_CMN;
        }
    }
}
debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<?php
$siteTitle = 'ログイン';
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
        <section id="login" class="m-auto user-info-controll bg-light p-5">
            <h2 class="font-weight-bold text-center mb-5">ログイン</h2>
            <div class="<?php if(!empty($err_msg['common'])) echo 'alert alert-danger'; ?>" role="alert">
                <?php 
                if(!empty($err_msg['common'])) echo $err_msg['common'];
                ?>
            </div>
            <form action="" method="post">
                <div class="form-group mb-4">
                    <label for="input-email">メールアドレス</label>
                    <input type="email" class="form-control <?php if(!empty($err_msg['email'])) echo 'border-danger'; ?>" name="email" id="input-email" value="<?php if(!empty($_POST['email'])) echo $_POST['email']; ?>">
                    <small id="emailHelp" class="form-text">
                        <?php
                        if(!empty($err_msg['email'])) echo $err_msg['email'];
                        ?>
                    </small>
                </div>
                <div class="form-group mb-4">
                    <label for="input-password">パスワード</label>
                    <input type="password" class="form-control <?php if(!empty($err_msg['pass'])) echo 'border-danger'; ?>" name="pass" id="input-password" value="<?php if(!empty($_POST['pass'])) echo $_POST['pass']; ?>">
                    <small id="emailHelp" class="form-text">
                        <?php
                        if(!empty($err_msg['pass'])) echo $err_msg['pass'];
                        ?>   
                    </small>
                </div>
                <div class="form-group form-check mb-4">
                    <input type="checkbox" class="form-check-input" id="loginCheck" name="pass_save">
                    <label class="form-check-label mb-3" for="loginCheck">次回ログインを省略する</label><br>
                    <a href="passReminderSend.php" class="ml-0">パスワードを忘れた場合</a>
                </div>
                <button type="submit" class="btn btn-success rounded-0 px-4 py-2 ml-auto float-right">ログイン</button>
                
            </form>
        </section>
    </div>
    </div>

    <!-- フッター -->
    <?php
    require('footer.php');
    ?>