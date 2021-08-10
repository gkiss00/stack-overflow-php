<div class = "nav_bar">
            <a id = "title" href ="post/index">Stuck overflow</a>
            <?php if (isset($user)): ?>
                <div class = "nav_main">
                    <a href ="post/ask">Ask a question</a>
                    <a href ="post/index">Questions</a>
                    <a href ="tag">Tags</a>
                    <a href = "user/stats">Stats</a>
                    <a href =""><?= $user->pseudo?></a>
                    <a href ="user/logout">Logout</a>
                </div>
            <?php else: ?>
                <div class = "nav_main">
                    <a href ="post/index">Questions</a>
                    <a href ="tag">Tags</a>
                    <a href ="user/stats">Stats</a>
                    <a href ="user/signin">SignIn</a>
                    <a href ="user/signup">SignUp</a>
                </div>
            <?php endif; ?>
        </div>