<header>
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <a href="index.php" class="navbar-brand">Liferary</a>
            <div class="navbar-nav ml-auto">
                <?php
                    if(empty($_SESSION['user_id'])){
                ?>
                    <a href="./login.php" class="nav-item nav-link">ログイン</a>
                    <a href="./signup.php" class="nav-item nav-link">ユーザ登録</a>
                <?php
                    }else{
                ?>
                    <a href="./mypage.php" class="nav-item nav-link">マイページ</a>
                    <a href="./logout.php" class="nav-item nav-link">ログアウト</a>
                <?php
                    }
                ?>
            </div>
        </nav>
    </div>
</header>