<!doctype html>

<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stuck Overflow - Edit</title>
    <base href="<?= $web_root ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Stack overflow for stuck people">
    <meta name="author" content="Gautier Kiss | Guillaume Rigaux">
    <link rel="icon" href="upload/favicon.ico" />
    <link href="css/styles.css" rel="stylesheet" type="text/css"/>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">    
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
            $('#edit').validate({
                rules: {
                    subject: {
                        required: true,
                        regex: /\S/,
                    },
                    markdown_body: {
                        required: true,
                        regex: /\S/,
                    },
                },
                messages: {
                    subject: {
                        required: "Title is required",
                        regex: "This title is not allowed",
                    },
                    markdown_body: {
                        required: "Body is required",
                        regex: "This body is not allowed",
                    }
                }
            });
        });
</script>


</head>
<body>
    <header>
        <?php include('menu.php'); ?>
    </header>
    <main>
        <form id="edit" method="post" action="post/edit/<?= $post->PostId ?>">
            <?php if (!isset($post->ParentId)): ?>
                <h3>Title</h3>
                <p>Be specific and imagine you're asking a question to another person</p>
                <input type="text" cols=100 id="subject" name="subject" value =<?=$post->Title ?> required>
            <?php endif; ?>
            <h3>Body</h3>
            <p>Include all the information someone would need to answer your question</p>
            <textarea name="markdown_body" rows=20 cols=100 required><?=$post->Body ?></textarea><br>
            <button type="submit">Edit this post</button>
        </form>
        <?php if (isset($errors)):?>
            <ul>
                <?php foreach ($errors as $e):?>
                    <li><?=$e?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif;?>
    </main>
</body>
</html>