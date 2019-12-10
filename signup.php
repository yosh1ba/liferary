<?php

// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　ユーザー登録ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// POST送信されていた場合
if(!empty($_POST)){
    debug('POST送信があります。');
    debug('POST情報：'.print_r($_POST,true));

    // 変数にユーザ情報を代入
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS);
    $pass = filter_input(INPUT_POST, 'pass', FILTER_SANITIZE_SPECIAL_CHARS);
    $pass_re = filter_input(INPUT_POST, 'pass_re', FILTER_SANITIZE_SPECIAL_CHARS);

    // 未入力チェック
    validRequired($name,'name');
    validRequired($email,'email');
    validRequired($pass,'pass');
    validRequired($pass_re,'pass_re');

    if(empty($err_msg)){

        // ユーザー名の最大文字数チェック
        validMaxLen($name, 'name', 10);

        // Emailの形式チェック
        validEmail($email, 'email');
        // Emailの最大文字数チェック
        validMaxLen($email, 'email', 255);
        // Emailの重複チェック
        validEmailDup($email, 'email');

        // パスワードの半角英数字チェック
        validHalf($pass,'pass');
        // パスワードの最小文字数チェック
        validMinLen($pass, 'pass', 8);
        // パスワードの最大文字数チェック
        validMaxLen($pass, 'pass', 255);

        // パスワード（再入力）の最小文字数チェック
        validMinLen($pass_re, 'pass_re', 8);
        // パスワード（再入力）の最大文字数チェック
        validMaxLen($pass_re, 'pass_re', 255);

        if(empty($err_msg)){

            // パスワードの一致判定
            validMatch($pass, $pass_re, 'pass_re');

            if(empty($err_msg)){

                // 例外処理
                try {
                    // DBへ接続
                    $dbh = dbConnect();
                    // SQL文作成
                    $sql = 'INSERT INTO users (name,email,password,logined_on,created_on) VALUES(:name,:email,:pass,:login_time,:create_date)';
                    $data = array(
                        ':name' => $name,
                        ':email' => $email,
                        ':pass' => password_hash($pass,PASSWORD_DEFAULT),
                        'login_time' => date('Y-m-d H:i:s'),
                        'create_date' => date('Y-m-d H:i:s')
                    );
                    // クエリ実行
                    $stmt = queryPost($dbh, $sql, $data);

                    // クエリ成功の場合
                    if($stmt){
                        // ログイン有効期限（1時間とする）
                        $sesLimit = 60*60;
                        // 最終ログイン日時を現在日時に書き換える
                        $_SESSION['login_date'] = time();
                        $_SESSION['login_limit'] = $sesLimit;
                        // ユーザーIDを格納
                        $_SESSION['user_id'] = $dbh->lastInsertId();

                        debug('セッションの中身：'.print_r($_SESSION,true));
                        
                        // マイページへ遷移
                        header('Location:mypage.php');
                    }
                } catch (Exception $e){
                    error_log('エラー発生：'. $e->getMessage());
                    $err_msg['common'] = MSG_CMN;
                }
            }
        }
    }
}
debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<?php
$siteTitle = 'ユーザ登録';
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
        <section id="signup" class="m-auto user-info-controll bg-light p-5">
            <h2 class="font-weight-bold text-center mb-5">ユーザー登録</h2>
            <div class="<?php if(!empty($err_msg['common'])) echo 'alert alert-danger'; ?>" role="alert">
                <?php 
                if(!empty($err_msg['common'])) echo $err_msg['common'];
                ?>
            </div>
            <form action="" method="post">
                <div class="form-group mb-4">
                    <label for="input-name">ニックネーム</label>
                    <input type="text" class="form-control <?php if(!empty($err_msg['name'])) echo 'border-danger'; ?>" name="name" id="input-name" value="<?php if(!empty($_POST['name'])) echo $_POST['name']; ?>">
                    <small id="emailHelp" class="form-text">
                        <?php
                        if(!empty($err_msg['name'])) echo $err_msg['name'];
                        ?>
                    </small>
                </div>
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
                <div class="form-group mb-5">
                    <label for="input-password-retype">パスワード（再入力）</label>
                    <input type="password" class="form-control <?php if(!empty($err_msg['pass_re'])) echo 'border-danger'; ?>" name="pass_re" id="input-password-retype" value="<?php if(!empty($_POST['pass_re'])) echo $_POST['pass_re']; ?>">
                    <small id="emailHelp" class="form-text">
                        <?php
                        if(!empty($err_msg['pass_re'])) echo $err_msg['pass_re'];
                        ?>
                    </small>
                </div>
                <button type="submit" class="btn btn-success rounded-0 px-4 py-2 ml-auto float-right">登録する</button>
                
            </form>
        </section>
    </div>
    </div>

    <!-- フッター -->
    <?php
    require('footer.php');
    ?>