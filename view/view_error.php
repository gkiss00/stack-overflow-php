<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Stuck Overflow - Error</title>
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
        <?php 
            $user = NULL;
            if (isset($_SESSION['user'])) {
                $user = $_SESSION['user'];
            }
        ?>
        <?php include('menu.php'); ?>
    </header>
<body>
    <div id="main_error">
        <img src="upload/error_gif.gif" alt='Oops!'>
        <h3>Oops, error!</h3>
        <p><?= $error ?></p> <br>
        <?php if (isset($postId)): ?>
            <a href="post/show/<?=$postId?>">Back</a>
        <?php endif; ?>
    </div>
</body>

</html>