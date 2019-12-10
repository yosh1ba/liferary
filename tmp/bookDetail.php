<?php

//共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　書籍詳細ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// ログイン認証
require('auth.php');

//================================
// 画面処理
//================================
// 画面表示用データ取得
//================================
// GETデータを格納
$p_id = (!empty($_GET['p_id'])) ? $_GET['p_id'] : '';
// DBから投稿情報を取得
$viewPostData = getPostOne($p_id);
debug('投稿情報：'.print_r($viewPostData,true));
// DBから登録者情報を取得
$viewUserData = getUser($viewPostData['user_id']);
debug('投稿者情報：'.print_r($viewUserData,true));
// DBから年代情報を取得
$viewPeriodData = getPeriodCategoryOne($p_id);
debug('年代情報：'.print_r($viewPeriodData,true));
// DBからメッセージ情報を取得
$viewMessageData = getMessage($p_id);
debug('メッセージ情報：'.print_r($viewMessageData,true));

// パラメータに不正な値が入っているかチェック
if(empty($viewPostData)){
    error_log('エラー発生:指定ページに不正な値が入りました');
    header("Location:index.php"); //トップページへ
}
debug('取得したDBデータ：'.print_r($viewPostData,true));

// POST送信されていた場合
if($_POST){
    debug('POST送信があります：'.print_r($_POST,true));
    
    // POSTされた値を変数に代入
    $mes = $_POST['mes'];

    // バリデーションチェック
    validRequired($mes, 'mes');
    // 最大文字数チェック
    validMaxLen($mes, 'mes', 30);

    if(empty($err_msg)){
        debug('バリデーションOKです。');
    
        // 例外処理
        try {
            $dbh = dbConnect();
            $sql = 'INSERT INTO messages (post_id, user_id, `message`, created_on) VALUES (:p_id, :u_id, :mes, :date)';
            $data = array(':p_id' => $p_id, ':u_id' => $_SESSION['user_id'], 'mes' => $mes, ':date' => date('Y-m-d H:i:s'));

            $stmt = queryPost($dbh, $sql, $data);
            debug('クエリの中身：'.print_r($stmt,true));

            if($stmt){
                $_SESSION['msg_success'] = SUC_MES;
                debug('ページを再読み込みします。');
                header("Location:".$_SERVER['PHP_SELFT'].'?p_id='.$p_id);
                unset($_SESSION['msg_success']);
                exit;
            }
            
        } catch (Exception $e) {
            error_log('エラー発生:' . $e->getMessage());
            $err_msg['common'] = MSG07;
        }
    }
}
debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<?php
$siteTitle = '書籍詳細';
require('head.php');
?>

<body>
    <!-- ヘッダー -->
    <?php
    require('header.php');
    ?>

    <!-- メインコンテンツ -->
    <div class="container mt-3">
        <section id="book-detail" class="bg-light pt-5 px-5 mb-3">
            <h2 class="font-weight-bold text-center mb-5">書籍情報</h2>
            <div class="row ml-auto mr-auto mb-5">
                <div class="col-4">
                    <img src="<?php echo sanitize($viewPostData['picture']); ?>" alt="" class="align-middle" width="225">
                </div>                    
                <div class="col-5 pl-0">
                    <label for="" class="font-weight-bold">タイトル</label>
                    <h5 class="ml-1 mb-3"><?php echo sanitize($viewPostData['title']); ?></h5>
                    <label for="" class="font-weight-bold">著者</label>
                    <h5 class="ml-1 mb-3"><?php echo sanitize($viewPostData['author']); ?></h5>
                    <label for="" class="font-weight-bold">出版日</label>
                    <h5 class="ml-1 mb-3"><?php echo sanitize($viewPostData['published_on']); ?></h5>
                </div>
            </div>
            <h3 class="font-weight-bold text-center mb-4">だれの一冊？</h3>
            <div class="row ml-auto mr-auto mb-5">
                <div class="col-3 text-center">
                    <img src="<?php echo $viewUserData['icon']; ?>" alt="" class="align-middle d-block ml-auto mr-auto mb-3 rounded-circle" width="150">
                    <span><?php echo sanitize($viewUserData['name']); ?></span>
                    <i class="far fa-heart icn-like js-click-like <?php if(isLike($_SESSION['user_id'], $viewPostData['post_id'])){ echo 'active'; } ?>" area-hidden=”true” data-postid="<?php echo sanitize($viewPostData['post_id']); ?>"></i>
                </div>
                <div class="col-9">
                    <p class="font-weight-bold">読んだ時期：<br>
                        <span class="font-weight-normal pl-2 mb-5">
                            <?php echo sanitize($viewPeriodData['name']); ?>
                        </span>
                    </p>
                    <p class="font-weight-bold">この本について：<br>
                        <span class="font-weight-normal pl-2">
                            <?php echo sanitize($viewPostData['detail']); ?>
                        </span>
                    </p>
                </div>
            </div>
            <h3 class="font-weight-bold text-center mb-3">コメント</h3>
            <?php
            foreach($viewMessageData as $key => $data):
            ?>
            <div class="row mx-auto mb-4">
                <div class="col-10 text-right">
                    <div class="balloon-comment">
                        <p><?php echo $data['message']; ?></p>
                    </div>
                </div>
                <div class="col-2 pl-0">
                    <div class="balloon-icon h-100 text-center">
                        <img src="
                        <?php 
                        if(!empty($data['icon'])){
                            echo $data['icon'];
                        } else{
                            echo './img/noicon.svg';
                        }; ?>" alt="" class="align-middle d-block m-auto rounded-circle" width="80">
                        <span class="small text-muted text-center"><?php echo $data['name']; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <form action="" class="p-3" method="POST">
                <div class="form-group">
                    <textarea class="form-control mb-2 js-input <?php if(!empty($err_msg['mes'])) echo 'border-danger'; ?>" id="input-comment" rows="2" placeholder="コメント入力" name="mes"><?php
                    if($_POST){
                        echo $_POST['mes'];
                    }
                    ?></textarea>
                    <div class="d-flex mb-5
                    <?php
                    if(!empty($err_msg['mes'])){
                        echo 'justify-content-between';
                    } else{
                        echo 'justify-content-end';
                    }
                    ?>">
                        <small id="isbnHelp" class="form-text pl-3">
                        <?php
                            if(!empty($err_msg['mes'])) echo $err_msg['mes'];
                        ?>
                        </small>
                        <span class="text-right"><span id="js-count"></span>/30文字</span>
                    </div>
                    <button type="submit" class="btn btn-success rounded-0 px-4 py-2 mb-5 ml-auto float-right">送信</button>
                </div>
            </form>
        </section>
    </div>
    <!-- フッター -->
    <?php
    require('footer.php');
    ?>