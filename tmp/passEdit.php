<?php
//共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　パスワード変更ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

//ログイン認証
require('auth.php');

//================================
// 画面処理
//================================
// DBからユーザーデータを取得
$userData = getUser($_SESSION['user_id']);
debug('取得したユーザー情報：'.print_r($userData,true));

// POSTされていた場合
if(!empty($_POST)){
    debug('POST送信があります。');
    debug('POST情報：'.print_r($_POST,true));

    // 変数にユーザー情報を代入
    $pass_old = filter_input(INPUT_POST, 'pass_old', FILTER_SANITIZE_SPECIAL_CHARS);
    $pass_new = filter_input(INPUT_POST, 'pass_new', FILTER_SANITIZE_SPECIAL_CHARS);
    $pass_new_re = filter_input(INPUT_POST, 'pass_new_re', FILTER_SANITIZE_SPECIAL_CHARS);

    // 未入力チェック
    validRequired($pass_old, 'pass_old');
    validRequired($pass_new, 'pass_new');
    validRequired($pass_new_re, 'pass_new_re');

    if(empty($err_msg)){
        debug('未入力チェックOK。');

        // 古いパスワードのチェック
        validPass($pass_old,'pass_old');
        // 新しいパスワードのチェック
        validPass($pass_new, 'pass_new');

        // 古いパスワードとDBのパスワードを照合
        if(!password_verify($pass_old, $userData['password'])){
            $err_msg['pass_old'] = MSG_UNMATCH;
        }
        // 古いパスワードと新しいパスワーを照合（同じ場合はエラーとする）
        if($pass_old === $pass_new){
            $err_msg['pass_new'] = MSG_NOCHANGE;
        }
        // 再入力のパスワードを照合
        validMatch($pass_new, $pass_new_re, 'pass_new_re');

        if(empty($err_msg)){
            debug('バリデーションチェックOK。');

            // 例外処理
            try {
                // DBへ接続
                $dbh = dbConnect();
                // SQL文作成
                $sql = 'UPDATE users SET password = :pass WHERE id = :id';
                $data = array(':id' => $_SESSION['user_id'], ':pass' => password_hash($pass_new, PASSWORD_DEFAULT));
                // クエリ実行
                $stmt = queryPost($dbh, $sql, $data);
                // クエリ成功の場合
                if($stmt){
                    debug('クエリ成功。');
                    $_SESSION['msg_success'] = SUC_PASS;
                    
                    // メール送信
                    $name = ($userData['name']) ? $userData['name'] : '名無し';
                    $from = 'info@liferary.com';
                    $to = $userData['email'];
                    $subject = 'パスワード変更通知｜Liferary';
                    $comment = <<<EOT
{$name}さん
パスワードが変更されました。

////////////////////////////////////////
Liferary
URL  http://xxx.com/
E-mail info@ligerary.com
////////////////////////////////////////
EOT;
                    sendMail($from, $to, $subject, $comment);
                    
                    // マイページへ
                    header("Location:mypage.php");
                    exit;
                } else{
                    debug('クエリに失敗しました。');
                    $err_msg['common'] = ERR_CMN;
                }
            }catch (Exception $e){
                error_log('エラー発生：'.$e->getMessage());
                $err_msg['common'] = MSG_CMN;
            }
        }
    }
}

?>

<?php
$siteTitle = 'パスワード変更';
require('head.php');
?>

<body>
    <!-- ヘッダー -->
    <?php
    require('header.php');
    ?>

    <!-- メインコンテンツ -->
    <div class="container">
        <div class="row">    
            <section id="signup" class="col-9 mx-auto bg-light p-5">
                <h2 class="font-weight-bold text-center mt-5 mb-5">パスワード変更</h2>
                <div class="<?php if(!empty($err_msg['common'])) echo 'alert alert-danger'; ?>" role="alert">
                    <?php 
                    if(!empty($err_msg['common'])) echo $err_msg['common'];
                    ?>
                </div>
                <form action="" class="user-info-controll mx-auto" method="post">
                    <div class="form-group mb-4">
                        <label for="input-password-old">古いパスワード</label>
                        <input type="password" class="form-control <?php if(!empty($err_msg['pass_old'])) echo 'border-danger'; ?>" name="pass_old" id="input-password-old" value="<?php echo getFormData('pass_old'); ?>">
                        <small class="form-text">
                            <?php
                            if(!empty($err_msg['pass_old'])) echo $err_msg['pass_old'];
                            ?>
                        </small>
                    </div>
                    <div class="form-group mb-4">
                        <label for="input-password-new">新しいパスワード</label>
                        <input type="password" class="form-control <?php if(!empty($err_msg['pass_new'])) echo 'border-danger'; ?>" name="pass_new" id="input-password-new" value="<?php echo getFormData('pass_new'); ?>">
                        <small class="form-text">
                            <?php
                            if(!empty($err_msg['pass_new'])) echo $err_msg['pass_new'];
                            ?>
                        </small>
                    </div>
                    <div class="form-group mb-5">
                        <label for="input-password-new-retype">新しいパスワード（再入力）</label>
                        <input type="password" class="form-control <?php if(!empty($err_msg['pass_new_re'])) echo 'border-danger'; ?>" name="pass_new_re" id="input-password-new-retype" value="<?php echo getFormData('pass_new_re'); ?>">
                        <small class="form-text">
                            <?php
                            if(!empty($err_msg['pass_new_re'])) echo $err_msg['pass_new_re'];
                            ?>
                        </small>
                    </div>
                    <button type="submit" class="btn btn-success rounded-0 px-4 py-2 mb-5 ml-auto float-right">変更する</button>
                </form>
            </section>
            <!-- サイドバー右 -->
            <?php
            require('sidebarRight.php');
            ?>
        </div>
    </div>
    <footer class="container-fluid footer py-1 bg-dark text-center">
        <span>&copy; 2019 yoshiba</span>
    </footer>
    <script type="text/javascript" src="./js/script.js"></script>
</body>
</html>