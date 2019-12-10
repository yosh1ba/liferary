<?php

//共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　書籍登録ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// ログイン認証
require('auth.php');

//================================
// 画面処理
//================================

// 画面表示用データ取得
//================================
//GETデータを格納
$p_id = (!empty($_GET['p_id'])) ? $_GET['p_id'] : '';
// DBから投稿データを取得
$dbFormData = (!empty($p_id)) ? getPost($_SESSION['user_id'], $p_id) : '';
debug('セッション情報：'.print_r($_SESSION,true));
// DBから書籍データを取得
// 既に投稿情報が存在する前提（GETデータがある）
// ISBNがDBと同じ場合、DBのISBNをキーに書籍情報を取得する
// ISBNがDBと異なる場合、入力されたISBNをキーに書籍情報を取得する
$dbFormData2 = (!empty($p_id)) ? getBookData(getFormData('isbn',true)) : '';
// DBから年代カテゴリーデータを取得
$dbPeriodCategoryData = getPeriodCategory();
// 新規登録画面か編集画面か判別用フラグ
$edit_flg = (empty($dbFormData)) ? false : true;
// 書籍情報がDBに存在するかの判別様フラグ
$isbn_flg = false;
// クリックしたボタンの種類を判別

// 情報表示
debug('投稿ID：'.$p_id);
debug('フォーム用DBデータ：'.print_r($dbFormData,true));
debug('フォーム用書籍DBデータ'.print_r($dbFormData2,true));
debug('年代カテゴリデータ：'.print_r($dbPeriodCategoryData,true));
debug('GETの中身：'.print_r($_GET,true));

// パラメータ改ざんチェック
//================================
// GETパラメータはあるが、改ざんされている（URL弄った）場合、正しい投稿データが取れないのでマイページへ遷移させる
if(!empty($p_id) && empty($dbFormData)){
    debug('GETパラメータの商品IDが違います。マイページへ遷移します。');
    header("Location:mypage.php");  //マイページへ
    exit;
}

//POST送信処理
//================================
if(!empty($_POST)){
    debug('POST送信があります。');
    debug('POST情報：'.print_r($_POST,true));

    //変数にユーザ情報を代入
    // ISBNコード
    $isbn = $_POST['isbn'];
    // 年代ID
    $period = $_POST['period_id'];
    // コメント
    $comment = $_POST['comment'];
    // 詳細
    $detail = $_POST['detail'];
    // ボタンの種類
    $btn = $_POST['btn'];

    // 削除ボタンがクリックされた場合
    if($btn === 'delete'){
        debug('削除ボタンがクリックされました。');
        
        // 例外処理
        try {
            $dbh = dbConnect();
            $sql = 'UPDATE posts SET is_deleted = 1 WHERE user_id = :u_id AND id = :p_id';
            $data = array(':u_id' => $_SESSION['user_id'], ':p_id' => $p_id);
            $stmt = queryPost($dbh, $sql, $data);
            if($stmt){
                $_SESSION['msg_success'] = SUC_DELETE;
                debug('投稿情報が削除されました');
                debug('マイページへ遷移します。');
                header("Location:mypage.php");
                exit;
            }
            
        } catch (Exception $e){
            error_log('エラー発生：'.$e->getMessage());
            $err_msg['common'] = MSG_CMN;
        }
    }

    // 書籍情報処理
    if(!empty($isbn)){
        debug('ISBN情報のPOST送信があります');
        debug('ISBNコード：'.$isbn);

        // ISBNコードから書籍情報を取得
        $bookData = getBookData($_POST['isbn']);

        // ISBNコードがヒットした場合
        if(!empty($bookData)){
            $dbFormData2 = $bookData;

        // 書籍情報が見つからない場合
        } else{
            $err_msg['isbn'] = MSG_BOOK;
        }
    // ISBNコード未入力
    } else{
        $err_msg['isbn'] = MSG_ISBN;
    }

    // DBに情報がない場合（新規登録の場合）
    if(empty($dbFormData)){
        // 未入力チェック
        validRequired($isbn,'isbn');
        $isbn_flg = searchBook($isbn);
        // セレクトボックスチェック
        validSelect($period, 'period_id');
        // 最大文字数チェック
        validMaxLen($comment, 'comment', 30);
        validMaxLen($detail, 'detail', 500);

    // DBとPOSTが異なる場合
    }else {
        if($dbFormData['isbn'] !== $isbn){
            // 未入力チェック
            validRequired($isbn,'isbn');
            $isbn_flg = searchBook($isbn);
        }
        if($dbFormData['period_id'] !== $period){
            // セレクトボックスチェック
            validSelect($category, 'category_id');
        }
        if($dbFormData['comment'] !== $comment){
            // 最大文字数チェック
            validMaxLen($comment, 'comment', 30);
        }
        if($dbFormData['detail'] !== $detail){
            // 最大文字数チェック
            validMaxLen($detail, 'detail', 500);
        }
    }
    if(empty($err_msg) && $btn === 'submit'){
        debug('バリデーションOKです。');

        // 例外処理
        try {
            // DB接続
            $dbh = dbConnect();
            // SQL文作成
            // DB更新の場合
            if($edit_flg){
                $sql1 = 'UPDATE posts SET 
                    user_id = :u_id ,isbn = :isbn, period_id = :period, comment = :comment, detail = :detail 
                    WHERE user_id = :u_id AND id = :p_id';
                $data1 = array(
                    ':u_id' => $_SESSION['user_id'],
                    ':isbn' => $isbn,
                    ':period' => $period,
                    ':comment' => $comment,
                    ':detail' => $detail,
                    ':p_id' => $p_id
                );
            // 新規登録の場合
            } else{
                $sql1 = 'INSERT INTO posts (`user_id`, isbn, period_id, comment, detail, created_on) VALUES (:u_id, :isbn, :period, :commnet, :detail, :date)';
                $data1 = array(
                    ':u_id' => $_SESSION['user_id'],
                    ':isbn' => $isbn,
                    ':period' => $period,
                    ':commnet' => $comment,
                    ':detail' => $detail,
                    ':date' => date('Y-m-d H:i:s')
                );
            }
            // DBに書籍が登録されていない場合、書籍も合わせて登録する
            if($isbn_flg){
                debug('SQL1：'.$sql1);
                debug('流し込みデータ1：'.print_r($data1,true));
                
                // クエリ実行
                $stmt1 = queryPost($dbh, $sql1, $data1);

                // クエリ成功の場合
                if($stmt1 && ($btn === 'submit')){
                    $_SESSION['msg_success'] = SUC_SUBMIT;
                    debug('マイページへ遷移します。');
                    header("Location:mypage.php");
                    exit;
                }
            } else{
                //SQL文作成
                $sql2 = 'INSERT INTO books (isbn, title, author, published_on, picture, created_on) VALUES (:isbn, :title, :author, :publish_date, :picture, :date)';
                $data2 = array(':isbn' => $isbn, ':title' => $title, ':author' => $author, ':publish_date' => $publish_date, ':picture' => $picture, ':date' => date('Y-m-d H:i:s'));
                debug('SQL1：'.$sql1);
                debug('流し込みデータ1：'.print_r($data1,true));
                debug('SQL2：'.$sql2);
                debug('流し込みデータ2：'.print_r($data2,true));

                // クエリ実行
                $stmt1 = queryPost($dbh, $sql1, $data1);
                $stmt2 = queryPost($dbh, $sql2, $data2);

                // クエリ成功の場合
                if($stmt1 && $stmt2 && ($btn === 'submit')){
                    $_SESSION['msg_success'] = SUC_SUBMIT;
                    debug('マイページへ遷移します。');
                    header("Location:mypage.php");
                    exit;
                }
            }
        } catch (Exception $e){
            error_log('エラー発生：'.$e->getMessage());
            $err_msg['common'] = MSG_CMN;
        }
    }
}
debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>
<?php
$siteTitle = '書籍登録';
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
            <section id="regist-book" class="col-9 mx-auto pl-4 bg-light">
                <h2 class="font-weight-bold text-center my-5">書籍登録</h2>
                <div class="<?php if(!empty($err_msg['common'])) echo 'alert alert-danger'; ?>" role="alert">
                <?php 
                if(!empty($err_msg['common'])) echo $err_msg['common'];
                ?>
                </div>
                <form action="" method="post">
                    <div class="form-group mb-4">
                        <label for="input-isbn">ISBNコード</label>
                        <div class="row w-100 m-0">
                            <input type="text" class="form-control w-50 <?php if(!empty($err_msg['name'])) echo 'border-danger'; ?>" name="isbn" id="input-isbn" value="<?php echo getFormData('isbn');?>">
                            <button type="submit" class="btn btn-success rounded-0 px-4 py-2 ml-4" name="btn" value="search">書籍検索</button>
                        </div>
                        <small id="isbnHelp" class="form-text d-block">
                                <?php
                                if(!empty($err_msg['isbn'])) echo $err_msg['isbn'];
                                ?>
                            </small>
                    </div>
                    <div class="row mb-5">
                        <div class="col-3">
                            <img src="
                            <?php
                            if(!empty($dbFormData2)){
                                echo $dbFormData2['imageLinks']['thumbnail'];
                            } else{
                                echo './img/noimg.svg';
                            };
                            ?>" 
                            class="align-middle" width="150">
                        </div>                    
                        <div class="col-9 <?php if(!$dbFormData2) echo 'd-none'; ?>">
                            <label for="" class="font-weight-bold">タイトル</label>
                            <h5 class="ml-1 mb-3"><?php if($dbFormData2) echo $dbFormData2['title']; ?></h5>
                            <label for="" class="font-weight-bold">著者</label>
                            <h5 class="ml-1 mb-3"><?php if($dbFormData2) echo array_shift($dbFormData2['authors']); ?></h5>
                            <label for="" class="font-weight-bold">出版日</label>
                            <h5 class="ml-1"><?php if($dbFormData2) echo $dbFormData2['publishedDate'] ?></h5>
                        </div>
                        <div class="col-9 <?php if($dbFormData2) echo 'd-none'; ?>">
                            <h5>書籍データがありません</h5>
                        </div>
                    </div>
                    <div class="form-group mb-4">
                    <label for="select-age">この本を読んだ時期</label>
                        <select class="form-control" id="select-period" name="period_id">
                            <option value="0" <?php if(getFormData('period_id') == 0){echo 'selected';} ?>>選択してください</option>
                            <?php
                            foreach($dbPeriodCategoryData as $key => $val){
                            ?>
                            <option value="<?php echo $val['id'] ?>" <?php if(getFormData('period_id') == $val['id']){ echo 'selected';} ?> >
                                <?php echo $val['name']; ?>
                            </option>
                            <?php
                            }
                            ?>
                        </select>
                        <small id="periodHelp" class="form-text">
                            <?php
                            if(!empty($err_msg['period_id'])) echo $err_msg['period_id'];
                            ?>
                        </small>
                    </div>
                    <div class="form-group mb-4">
                        <label for="input-comment">この本はあなたにとってどんな本ですか？</label>
                        <input type="text" class="form-control js-input <?php if(!empty($err_msg['comment'])) echo 'border-danger'; ?>" name="comment" id="input-comment" placeholder="例）無人島に持っていきたい一冊です" value="<?php echo getFormData('comment');?>" >
                        <span class="d-block text-right"><span id="js-count"></span>/30文字</span>
                    </div>
                    <div class="form-group mb-4">
                        <label for="input-detail">詳細</label>
                        <textarea class="form-control js-input <?php if(!empty($err_msg['detail'])) echo 'border-danger'; ?>" name="detail" id="input-detailt" rows="20"><?php echo getFormData('detail');?></textarea>
                        <span class="d-block text-right"><span id="js-count"></span>/500文字</span>
                    </div>
                    <div class="row justify-content-between px-3 mb-5">
                        <button type="submit" class="btn btn-dark rounded-0 px-4 py-2" name="btn" value="delete" <?php if(!$dbFormData2) echo 'disabled'; ?>>削除する</button>
                        <button type="submit" class="btn btn-success rounded-0 px-4 py-2" name="btn" value="submit"><?php echo (!$edit_flg) ? '登録する' : '編集する'; ?></button>
                    </div>
                </form>
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