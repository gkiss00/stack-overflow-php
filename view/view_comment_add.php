<!DOCTYPE html>
<html lang="en">
<?php
require_once "lib/parsedown-1.7.3/Parsedown.php";
require_once "util/Utils.php";
?>
<head>
    <meta charset="utf-8">
    <title>Stuck Overflow - Add comment</title>
    <base href="<?= $web_root ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Stack overflow for stuck people">
    <meta name="author" content="Gautier Kiss | Guillaume Rigaux">
    <link rel="icon" href="upload/favicon.ico" />
    <link href="css/styles.css" rel="stylesheet" type="text/css"/>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="lib/jquery-3.4.1.min.js" type="text/javascript"></script>
    <script src="lib/jquery-validation-1.19.1/jquery.validate.min.js" type="text/javascript"></script>
    <script>
        $.validator.addMethod("regex", function (value, element, pattern) {
            if (pattern instanceof Array) {
                for(p of pattern) {
                    if (!p.test(value))
                        return false;
                }
                return true;
            } else {
                return pattern.test(value);
            }
        }, "Please enter a valid input.");
        
        $(function () {
            $('#add').validate({
                rules: {
                    markdown_body: {
                        required: true,
                        regex: /\S/,
                    },
                },
                messages: {
                    markdown_body: {
                        required: "Body is required",
                        regex: "This body is not allowed",
                    }
                }
            });
        });
</script>


</head>
<body id = "show_body">
    <header>
        <?php include('menu.php'); ?>
    </header>
    <main id ="voir">
        <div class="show">
            <div class ="question_cadre">
                <div id = "entete">
                    <h2><?= $post->Title ?></h2>
                    <p>Asked <?=$post->Timestamp ?> by <a href=""><?= $post->Author['FullName']?></a>
                </div>
                <div id="corp_and_vote">
                    <div id="mainQuestion">
                    <?php 
                        Utils::parsedown($post->Body);
                    ?>
                    </div>
                </div>
                <div class="add_edit_comments">
                    <h2>Add a comment</h2>
                    <form action = "comment/add/<?=$post->PostId ?>" id="add" name="comment_add" method = "post">
                        <textarea id="comment" name="markdown_body" rows=5 cols=100 required></textarea> <br>
                        <input type="submit" value="Comment">
                    </form>
                    <?php if(isset($errors)) :?>
                            <?= $errors?>
                    <?php endif;?>
                </div>
            </div>
        </div>
    </main>
</html>