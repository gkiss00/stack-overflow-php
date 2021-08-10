<!DOCTYPE html>

<?php
    require_once 'model/Tag.php';
?>

<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stuck Overflow - Tags</title>
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

            $("form[id^='edit_']").on("click", function(event) {
                const tagId = event.target.id.replace('tagName_edit_', '');
                const frm = $("#edit_" + tagId);
                frm.validate({
                    rules: {
                            tagName: {
                                regex: /\S/,
                                remote: {
                                    url: 'tag/is_valid_service',
                                    type: 'post',
                                    data: {
                                        name: function (){
                                            return $("#tagName_edit_" + tagId).val();
                                        }
                                    }
                                },
                                required: true,
                            },
                        },
                        messages: {
                            tagName: {
                                regex: "This tag name is not valid",
                                remote : "This tag name is already taken",
                                required : "The tag name is required",
                            },
                        }
                    });
            })
            
            $('#add_tag').validate({
                rules: {
                    tagName: {
                        required: true,
                        remote: {
                            url: 'tag/is_valid_service',
                            type: 'post',
                            data: {
                                name: function(){
                                    return $("#new_tag").val();
                                }
                            }
                        },
                    },
                },
                messages: {
                    tagName: {
                        required: "Field is required",
                        remote: "The tag name is already taken/ is not valid",
                    },
                }
            });
        });
</script>

</head>
<body>
    <header>
        <?php include('menu.php'); ?>
    </header>
    <main class="taglist">
        <h2 id ='tagName'>List of all tags</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($tags as $t) :?>
                    <tr>
                        <td> 
                            <p class="names"><?=$t->Name?> (<a href="post/posts/tag/1/<?=$t->Id?>"><?=$t->nbTag[0]?> posts</a>) </p> 
                        </td>
                        <td>
                            <?php if (isset($user) && strcmp($user->Role, "admin") == 0) : ?>
                                <div id ="tag_flex">
                                    <form id="edit_<?= $t->Id?>" action="tag/edit/<?= $t->Id?>" method="post">
                                        <input type="text" name="tagName" id="tagName_edit_<?= $t->Id?>" value="<?= $t->Name?>">
                                        <button type="submit"><i class="fa fa-edit"></i></button>
                                    </form>
                                    <button><a href="tag/confirm_delete/<?=$t->Id?>"><i class="fa fa-trash-o"></i></a></button>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2 id ='tagName'>Add a tag</h2>
        <?php if (isset($user) && strcmp($user->Role, "admin") == 0) : ?>
            <div id = "newTag">
                <form action="tag/add" id="add_tag" method="post">
                    <input type="text" name="tagName" id="new_tag" placeholder="New tag name">
                    <button type="submit"><i class="fa fa-plus-square"></i></button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>