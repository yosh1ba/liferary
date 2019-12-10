<?php

//共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　書籍登録ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

//================================
// 画面処理
//================================

// 画面表示用データ取得
//================================
// GETパラメータを取得
//----------------------------------
// カレントページ
// デフォルトは1ページ目
$currentPageNum = (!empty($_GET['p'])) ? $_GET['p'] : 1;
// 年代
$period = (!empty($_GET['period_id'])) ? $_GET['period_id'] : '';
// 年代
$sort = (!empty($_GET['sort'])) ? $_GET['sort'] : '';
// パラメータに不正な値が入っているかチェック
if(!is_int((int)$currentPageNum)){
    error_log('エラー発生:指定ページに不正な値が入りました');
    header("Location:index.php"); //トップページへ
}

//表示件数
$listSpan = 5;
// 現在の表示レコードの先頭を算出
$currentMinNum = (($currentPageNum-1)*$listSpan);
// 投稿情報の一覧を取得
$dbPostData = getPostList($currentMinNum, $period, $listSpan, $sort);
// DBから年代カテゴリーデータを取得
$dbPeriodCategoryData = getPeriodCategory();

// 取得データ確認
// 投稿情報
debug('投稿情報一覧：'.print_r($dbPostData,true));

debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<?php
$siteTitle = 'トップページ';
require('head.php');
?>

<body>
    <!-- ヘッダー -->
    <?php
    require('header.php');
    ?>

    <!-- メインコンテンツ -->
    <div class="bg-image">
    <div class="container">
        <div class="row">
            
            <!-- サイドバー左 -->
            <?php
            require('sidebarLeft.php');
            ?>

            <section id="main" class="col-9">
                <span class="text-right d-block text-right mt-1 mb-1">
                <?php echo (!empty($dbPostData['data'])) ? $currentMinNum+1 : 0 ?> - 
                <?php echo $currentMinNum + count($dbPostData['data']); ?>件表示/
                <?php echo sanitize($dbPostData['total']); ?>件中</span>
                <?php
                foreach($dbPostData['data'] as $key => $data ):
                ?>
                <div class="row contents-group border border-muted rounded p-2 w-100 ml-auto mr-a mb-3 bg-light">
                    <div class="col-2 h-100 pr-0 pl-0">
                        <img src="
                        <?php
                        echo (!empty($dbPostData['data'])) ? $data['picture'] : './img/noimg.svg';
                        ?>
                        " alt="" class="align-middle">
                    </div>
                    <div class="col-10 pl-4 pr-0">
                        <span class="badge badge-warning mb-2"><?php echo $data['period_name']; ?></span>
                        <p class="font-weight-bold mb-1"><?php echo '『'.$data['title'].'』'; ?></p>
                        <p class="mb-2 ml-3 small text-muted"><?php echo '著者 '.$data['author']; ?></p>
                        <div class="border-line mb-2"></div>
                        <p class="mb-0 ml-2"><?php echo $data['comment']; ?></p>
                        <div class="row">
                            <p class="col-8 small text-dark  ml-3 small text-muted"><?php echo '投稿者 '.$data['user_name'].'　　　投稿日 '.substr($data['post_date'],0,10); ?></p>
                            <a href="bookDetail.php?p_id=<?php echo $data['post_id']; ?>" class="col-3 btn btn-success rounded-0 px-4 py-2 mt-3" role="button">詳しくみる</a>
                        </div>
                    </div>
                </div>
                <?php
                endforeach;
                ?>
                <nav>
                    <?php pagination($currentPageNum, $dbPostData['total_page'], $dbPostData['total'],'&period_id='.$period.'&sort='.$sort); ?>
                </nav>
            </section>
        </div>
    </div>
    </div>

    <!-- フッター -->
    <?php
    require('footer.php');
    ?>