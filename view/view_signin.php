<!doctype html>

<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stuck Overflow - Login</title>
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
        $(function () {
            $('#signin').validate({
                rules: {
                    pseudo: {
                        remote: {
                            url: 'user/pseudo_exists_service',
                            type: 'post',
                            data:  {
                                pseudo: function() { 
                                    return $("#pseudo").val();
                                }
                            }
                        },
                        required: true,
                    },
                    password: {
                        remote: {
                            url: 'user/password_is_correct_service',
                            type: 'post',
                            data:  {
                                pseudo: function() { 
                                    return $("#pseudo").val();
                                },
                                password: function() {
                                    return $("#password").val();
                                }
                            }
                        },
                        required: true,
                    },
                },
                messages: {
                    pseudo: {
                        remote: "This pseudo doesn't exist",
                        required: "Pseudo is required to sign in",
                    },
                    password: {
                        remote: "The password is not correct",
                        required: "You must enter the password",
                    },
                }
            });
        });
</script>
</head>
<body id = "signin_body">
    <header>
        <?php include('menu.php'); ?>
    </header>
    <main id = "connect">
        <div id = "se_connecter">
            <h1>Sign In</h1>
            <form id ="signin" action="user/signin" method="post">
                <table>
                <tr>
                    <td>Pseudo</td>
                    <td> <input id="pseudo" name="pseudo" type="text" value="<?= $pseudo ?>"></td>
                </tr>
                <tr>
                    <td>Password</td>
                    <td> <input id="password" name="password" type="password" value="<?= $password ?>"></td>
                </tr>
                </table>
                <input type="submit" value="Sign in">
                <?php if (count($errors) != 0): ?>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif;?>
            </form>
        </div>
    </main>
</body>
</html>