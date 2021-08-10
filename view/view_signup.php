<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stuck Overflow - Signup</title>
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
            $('.signup').validate({
                rules: {
                    pseudo: {
                        remote: {
                            url: 'user/pseudo_already_exists_service',
                            type: 'post',
                            data:  {
                                pseudo: function() { 
                                    return $("#pseudo").val();
                                }
                            }
                        },
                        required: true,
                        minlength: 3,
                    },
                    password: {
                        remote: {
                            url: 'user/password_is_valid_service',
                            type: 'post',
                            data:  {
                                password: function() {
                                    return $("#password").val();
                                }
                            }
                        },
                        required: true,
                        minlength: 8,
                    },
                    password_confirm: {
                        remote: {
                            url: 'user/passwords_correspond_service',
                            type: 'post',
                            data: {
                                password: function() {
                                    return $("#password").val();
                                },
                                password_confirm: function() {
                                    return $("#password_confirm").val();
                                }
                            }
                        },
                        required: true,
                    },
                    email: {
                        remote: {
                            url: 'user/mail_correct_service',
                            type: 'post',
                            data: {
                                email: function() {
                                    return $("#email").val();
                                }
                            }
                        },
                        required: true,
                    },
                    name: {
                        required: true,
                        minlength: 3,
                    },
                },
                messages: {
                    pseudo: {
                        remote: "This pseudo already exist",
                        required: "Pseudo is required to sign up",
                        minlength: "The minimum length for the pseudo is 3",
                    },
                    password: {
                        remote: "The password must contain at least a number, an uppercase and a non alphanumeric character",
                        required: "You must enter the password",
                        minlength: "The minimum length for the password is 8",
                    },
                    password_confirm: {
                        remote: "Passwords are not the same",
                        required: "You must confirm password",
                    },
                    email: {
                        remote: "The mail address is not valid/ already exists",
                        required: "You must enter a mail address",
                    },
                    name: {
                        required: "Please fill your name",
                        minlength: "The minimum length of the name is 3",
                    },
                }
            });
        });
    </script>
</head>
<body id = "signup_body">
    <header>
        <?php include('menu.php'); ?>
    </header>
    <main id = "signup">
        <div id = "s_inscrire">
            <h1>Signup</h1>
            <form class ="signup" action="user/signup" method="post">
                <table>
                    <tr>
                        <td>Pseudo</td>
                        <td><input id="pseudo" name="pseudo" type="text" value="<?= $pseudo ?>"></td>
                    </tr>
                    <tr>
                        <td>Password</td>
                        <td><input id="password" name="password" type="password" value="<?= $password ?>"></td>
                    </tr>
                    <tr>
                        <td>Confirm password</td>
                        <td><input id="password_confirm" name="password_confirm" type="password" value="<?= $password_confirm ?>"></td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td><input id="email" name="email" type="text" value="<?= $email ?>"></td>
                    </tr>
                    <tr>
                        <td>Name</td>
                        <td><input id="name" name="name" type="text" value="<?= $name ?>"></td>
                    </tr>
                </table>
                <input type="submit" value="Signup">
                <?php if(count($errors) != 0): ?>
                    <ul>
                        <?php foreach($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif;?>
            </form>
        </div>
    </main>
    <footer>
    </footer>
</body>
</html>