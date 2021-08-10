<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stuck Overflow - Ask</title>
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
        var max_tags = <?= $max_tags?>;

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
            $('#askquestion').validate({
                ignore: [],
                rules: {
                    subject: {
                        required: true,
                        regex: /\S/,
                    },
                    "selectedtags[]": {
                        maxlength: max_tags,
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
                    "selectedtags[]": {
                        maxlength: "You can only select " + max_tags + " tags",
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
        <form action="post/ask" method="post" id="askquestion">
            <h3>Title</h3>
            <p>Be specific and imagine you're asking a question to another person</p>
            <?php if (isset($subject)): ?>
                <input type="text" id="subject" name="subject" value="<?=$subject?>" required>
            <?php else: ?>
                <input type="text" id="subject" name="subject" required>
            <?php endif; ?>
            <h3>Tag</h3>
            <p>Add up to <?=$max_tags?> tags to describe what your question is about</p>
            <div class = "check_tags">
                <?php foreach($tags as $tag): ?>
                    <?php if (isset($alreadyselected) && in_array($tag->Name, $alreadyselected)):?>
                        <input type="checkbox" name="selectedtags[]" value="<?= $tag->Name?>" checked> <p><?= $tag->Name?></p>
                    <?php else:?>
                        <input type="checkbox" name="selectedtags[]" value="<?= $tag->Name?>"> <p><?= $tag->Name?></p>
                    <?php endif;?>
                <?php endforeach; ?>
            </div>
            <h3>Body</h3>
            <p>Include all the information someone would need to answer your question</p>
            <?php if (isset($body)): ?>
                <textarea id="body" name="markdown_body" form="askquestion" rows=20 cols=100 required><?= $body?></textarea> <br>
            <?php else: ?> 
                <textarea id="body" name="markdown_body" form="askquestion" rows=20 cols=100 required></textarea> <br>
            <?php endif; ?>
            <input type="submit" value="Publish your question">
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