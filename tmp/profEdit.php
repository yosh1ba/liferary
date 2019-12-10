<?php

//共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　プロフィール編集ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// ログイン認証
require('auth.php');

//================================
// 画面処理
//================================

// DBからユーザーデータを取得
$dbFormData = getUser($_SESSION['user_id']);
debug('取得したユーザー情報：'.print_r($dbFormData,true));

// DBから年齢カテゴリーデータを取得
$dbAgeCategoryData = getAgeCategory();
debug('取得した年齢カテゴリー情報：'.print_r($dbAgeCategoryData,true));

// POST送信されていた場合
if(!empty($_POST)){
    debug('POST送信情報があります。');
    debug('POST情報：'.print_r($_POST,true));

    debug('ファイル情報があります。');
    debug('FILE情報：'.print_r($_FILES,true));

    // 変数にユーザ情報を代入
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $ageId = filter_input(INPUT_POST, 'age_id', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS);
    // 画像をアップロードし、パスを格納
    $icon = ( !empty($_FILES['icon']['name']) ) ? uploadImg($_FILES['icon'],'icon') : '';
    // 画像をPOSTしていない（登録していない）が既にDBに登録されている場合、DBのパスを入れる
    $icon = ( empty($icon) && !empty($dbFormData['icon']) ) ? $dbFormData['icon'] : $icon;

    // DBの情報と入力情報が異なる場合にバリデーションを行う
    if($dbFormData['name'] !== $name){
        // 名前の最大文字数チェック
        validMaxLen($name,'name',10);
    }
    if($dbFormData['email'] !== $email){
        // 最大文字数チェック
        validMaxLen($email, 'email', 50);
        if(empty($err_msg['email'])){
            //emailの重複チェック
            validEmailDup($email, 'email');
            // emailの形式チェック
            validEmail($email, 'email');
            // emailの未入力チェック
            validRequired($email, 'email');
        }
    }
    if(empty($err_msg)){
        debug('バリデーションOKです。');

        // 例外処理
        try {
            // DBへ接続
            $dbh = dbConnect();
            // SQL文作成
            $sql = 'UPDATE users SET `name` = :name, age_id = :age_id, email = :email, icon = :icon, updated_at = :update_date WHERE id = :u_id';
            $data = array(
                ':name' => $name,
                ':age_id' => $ageId,
                ':email' => $email,
                ':icon' => $icon,
                ':update_date' => date('Y-m-d H:i:s'),
                ':u_id' => $dbFormData['id']
            );
            // クエリ実行
            $stmt = queryPost($dbh, $sql, $data);

            // クエリ成功の場合
            if($stmt){
                $_SESSION['msg_success'] = SUC_PROF;
                debug('マイページへ遷移します。');
                header("location:mypage.php");
                exit;
            }
        } catch(Exception $e){
            error_log('エラー発生：'.$e->getMessage());
            $err_msg['common'] = MSG_CMN;
        }
    }
}
debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>
<?php
$siteTitle = 'プロフィール編集';
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
            <section id="signup" class="col-9 bg-light">
                <h2 class="font-weight-bold text-center mt-5 mb-5">プロフィール編集</h2>
                <div class="<?php if(!empty($err_msg['common'])) echo 'alert alert-danger'; ?>" role="alert">
                    <?php 
                    if(!empty($err_msg['common'])) echo $err_msg['common'];
                    ?>
                </div>
                <form action="" class="user-info-controll mx-auto" method="post" enctype="multipart/form-data">
                    <div class="form-group mb-4">
                        <label for="input-name">ニックネーム</label>
                        <input type="text" class="form-control <?php if(!empty($err_msg['name'])) echo 'border-danger'; ?>" name="name" id="input-name" value="<?php echo getFormData('name');?>" >
                        <small id="emailHelp" class="form-text">
                            <?php
                            if(!empty($err_msg['name'])) echo $err_msg['name'];
                            ?>
                        </small>
                    </div>
                    <div class="form-group mb-4">
                        <label for="select-age">年代</label>
                        <select class="form-control" id="select-age" name="age_id">
                            <option value="0" <?php if(getFormData('age_id') == 0){echo 'selected';} ?>>選択してください</option>
                            <?php
                            foreach($dbAgeCategoryData as $key => $val){
                            ?>
                            <option value="<?php echo $val['id'] ?>" <?php if(getFormData('age_id') == $val['id']){ echo 'selected';} ?> >
                                <?php echo $val['name']; ?>
                            </option>
                            <?php
                            }
                            ?>
                        </select>
                        <small id="agelHelp" class="form-text">
                            <?php
                            if(!empty($err_msg['age_id'])) echo $err_msg['age_id'];
                            ?>
                        </small>
                    </div>
                    <div class="form-group mb-4">
                        <label for="input-email">メールアドレス</label>
                        <input type="email" class="form-control <?php if(!empty($err_msg['email'])) echo 'border-danger'; ?>" name="email" id="input-email" value="<?php echo getFormData('email'); ?>">
                        <small id="emailHelp" class="form-text">
                            <?php
                            if(!empty($err_msg['email'])) echo $err_msg['email'];
                            ?>
                        </small>
                    </div>
                    <div class="form-group mb-4" id="icon-area">
                        <label for="" class="d-block">プロフィール画像</label>
                        <label for="input-img" id="drop-area" class="position-relative">
                            <input type="file" class="form-control-file" id="input-img" name="icon">
                            <!-- <span class="small mx-auto w-100 d-block text-center">ドラッグ＆ドロップ</span> -->
                            <img src="<?php echo getFormData('icon'); ?>" class="prev-img w-100 h-100 <?php if(empty(getFormData('icon'))) echo 'd-none'; ?>" alt="">
                            ドラッグ＆ドロップ
                        </label>
                        <small id="iconHelp" class="form-text">
                            <?php
                            if(!empty($err_msg['icon'])) echo $err_msg['icon'];
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
    <!-- フッター -->
    <?php
    require('footer.php');
    ?>