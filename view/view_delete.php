<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Stuck Overflow - Confirm Delete</title>
  <base href="<?= $web_root ?>"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Stack overflow for stuck people">
  <meta name="author" content="Gautier Kiss | Guillaume Rigaux">
  <link rel="icon" href="upload/favicon.ico" />
  <link href="css/styles.css" rel="stylesheet" type="text/css"/>
  <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

</head>
<body>
    <header>
        <?php include('menu.php'); ?>
    </header>
    <main id= "delete">
        <div id="supprimer">
            <h1 id ="image">&#128465;</h1>
            <h1>Are you sure?</h1>
            <p>This process cannot be undone</p>
            <div id="button_del">
                <?php if(isset($post)): ?>
                    <?php if ($post->ParentId == NULL): ?>
                        <button><a href="post/show/<?= $post->PostId ?>">Cancel</a></button>
                    <?php else: ?>
                        <button><a href="post/show/<?= $post->ParentId ?>">Cancel</a></button>
                    <?php endif; ?>
                        <button><a href="post/delete/<?= $post->PostId ?>">Delete</a></button>
                <?php elseif(isset($tagId)): ?>
                    <button><a href="tag/index">Cancel</a></button>
                    <form action="tag/delete/<?= $tagId?>" method="post">
                        <button type="submit">Delete</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>