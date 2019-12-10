<?php
debug('セッション情報：'.print_r($_SESSION,true));

$p_id = '';
$p_id = getPostId($_SESSION['user_id']);
if($p_id){
    $p_id = array_shift($p_id);
    debug('投稿ID：'.$p_id);
}

?>
<section id="sidebar-right" class="col-3 pt-5">
    <ul>
        <li class="mb-3"><a href="resistBook.php<?php if($p_id) echo '?p_id='.$p_id ?>">登録書籍情報の編集</a></li>
        <li class="mb-3"><a href="profEdit.php">プロフィールの編集</a></li>
        <li class="mb-3"><a href="passEdit.php">パスワードの変更</a></li>
        <li class="mb-3"><a href="withdraw.php">退会</a></li>
    </ul>
</section>