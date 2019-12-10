<?php
//================================
// ログイン認証・自動ログアウト
//================================
// ログインしている場合
if(!empty($_SESSION['login_date'])){
    debug('ログイン済みユーザーです');

    // 現在日時が最終ログイン日時＋有効期限を超過していた場合
    if( ($_SESSION['login_date'] + $_SESSION['login_limit']) < time()){
        debug('ログイン有効期限オーバーです');

        // セッションを削除
        session_destroy();
        //ログインページへ
        header("Location:login.php");
        exit;

    }else {
        debug('ログイン有効期限内です');
        // 最終ログイン日時を現在日時に更新
        $_SESSION["login_date"] = time();

        // 無限ループ防止
        if(basename($_SERVER['PHP_SELF']) === 'login.php'){
            // マイページへ遷移
            debug('マイページへ遷移します');
            header("Location:mypage.php");
            exit;
        }

    }
} else{
    debug('未ログインユーザーです');
    
    // 無限ループ防止
    if(basename($_SERVER['PHP_SELF']) !== 'login.php'){
        // ログインページへ遷移
        debug('ログインページへ遷移します');
        header("Location:login.php");
        exit;
    }
}
?>