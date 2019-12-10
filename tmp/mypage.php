<?php
//共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　マイページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// ログイン認証
require('auth.php');

//================================
// 画面処理
//================================

// 画面表示用データ取得
//================================
$u_id = $_SESSION['user_id'];
// DBから投稿情報を取得
$postData = getMyPost($u_id);
// DBからメッセージデータを取得
$messageData = getMyMessage($u_id);
// DBから沖に位置データを取得
$likeData = getMyLike($u_id);

// 変数の中身を確認
//================================
debug('ユーザーID：'.$u_id);
debug('自分の投稿情報：'.print_r($postData,true));
debug('メッセージデータ：'.print_r($messageData,true));
debug('お気に入りの投稿情報：'.print_r($likeData,true));


?>

<?php
$siteTitle= 'マイページ';
require('head.php');
?>

<body>
    <!-- ヘッダー -->
    <?php
    require('header.php');
    ?>
    <div class="container">
        <div class="row">    
            <section id="mypage" class="col-9 bg-light">
                <h2 class="font-weight-bold text-center mt-5 mb-5">マイページ</h2>
                <h4 class="mb-3">登録書籍情報</h4>
                <div class="row mb-5">
                    <div class="col-3 ml-1">
                        <img src="
                        <?php
                        echo (!empty($postData)) ? $postData['picture'] : './img/noimg.svg';
                        ?>
                        " alt="" class="align-middle" width="150">
                    </div>                    
                    <div class="col-8">
                        <label for="" class="font-weight-bold">タイトル</label>
                        <h5 class="ml-1 mb-3"><?php echo sanitize($postData['title']); ?></h5>
                        <label for="" class="font-weight-bold">著者</label>
                        <h5 class="ml-1 mb-3"><?php echo sanitize($postData['author']); ?></h5>
                        <label for="" class="font-weight-bold">コメント</label>
                        <h5 class="ml-1 mb-3"><?php echo sanitize($postData['comment']); ?></h5>
                    </div>
                </div>
                <h4 class="mb-3">メッセージ履歴</h4>
                <div class="msg-group mb-5">
                    <div class="row msg-header">
                        <p class="col-2 mb-1 text-center">日付</p>
                        <p class="col-3 mb-1 text-center">相手</p>
                        <p class="col-7 mb-1">コメント内容</p>
                    </div>
                    <div class="row msg-detail">
                    <?php
                    foreach($messageData as $key => $data):
                    ?>
                        <p class="col-2 text-center"><?php echo substr($data['created_on'],0,10); ?></p>
                        <p class="col-3 text-center"><?php echo $data['name']; ?></p>
                        <p class="col-7"><a href="bookDetail.php?p_id=<?php echo $data['post_id']; ?>"><?php echo $data['message']; ?></a></p>
                    <?php
                    endforeach;
                    ?>
                    </div>

                </div>
                <h4 class="mb-3">お気に入り一覧</h4>
                <div class="row favorite-group mb-5">
                    <?php
                    foreach($likeData as $key => $data):
                    ?>
                    <div class="col-3 text-center mb-3">
                        <a href="bookDetail.php?p_id=<?php echo $data['post_id']; ?>"><img src="
                        <?php
                        echo (!empty($data)) ? $data['picture'] : './img/noimg.svg';
                        ?>
                        " alt="" class="align-middle d-block ml-auto mr-auto mb-1" width="150"></a>
                        <span class="small text-muted"><?php echo $data['name']; ?></span>
                    </div>
                    <?php
                    endforeach;
                    ?>
                </div>
            </section>

            <!-- サイドバー右 -->
            <?php
            require('sidebarRight.php');
            ?>
        </div>
    </div>

    <!-- フッター -->
    <?php
    require('footer.php');
    ?>