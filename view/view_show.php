<!DOCTYPE html>

<?php
    require_once "lib/parsedown-1.7.3/Parsedown.php";
    require_once "util/Utils.php";
?>

<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stuck Overflow - Show</title>
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
        var comments_json = <?= $comments_as_json?>;
        var userId = <?= $user->Id?>;
        var userRole = "<?= $user->Role ?>";
        var votes_json = <?= $votes_as_json?>;

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

        $(function(){    
            // suppression d'un charactere invisible dans l'ide mais visible dans le navigateur (???)
            var abc = document.body.innerHTML;
            var a = String(abc).replace(/\u200B/g,'');
            document.body.innerHTML = a;
            
            // on initialise pour les users qui n'auraient pas js
            $('.comment_add').hide();
            $('.comment_form').attr('hidden', false);
            $('.comment_form').hide();
            $(".comments").hide();
            displayComments();
            showCommentsAdd();

            //on gere les commentaires
            $("input[id^='jQuery_comment_add_']").on("click", function(event) {
                event.preventDefault();
                const postid = event.target.id.replace('jQuery_comment_add_', '');
                const frm = $("#form_" + postid);
                showForm(postid);
                frm.validate({
                    rules: {
                        markdown_body: {
                            required: true,
                            regex: /\S/,
                        },
                    },
                    messages: {
                        markdown_body: {
                            required: "The comment is required",
                            regex: "It looks that there is a problem with your comment",
                        }
                    }
                });
            });

            //validation des reponses
            $("#reply_form").validate({
                rules: {
                    markdown_body: {
                        required: true,
                        remote: {
                            url: 'post/body_is_valid_service',
                            type: 'post',
                            data: {
                                markdown_body: function(){
                                    return $("#reply_body").val();
                                }
                            }
                        },
                    },
                },
                messages: {
                    markdown_body: {
                        required: "Field is required to post an answer",
                        remote: "The answer is not valid",
                    }
                }
            });

            $(".votes_to_hide").hide();

            showVotes();
            displayVotes();            
        });

        function showCommentsAdd() {
            var inputs = document.getElementsByTagName("input");
            for (var i = 0; i < inputs.length; i++) {
                if (inputs[i].id.indexOf('jQuery_comment_add_') == 0) {
                    id = inputs[i].getAttribute("id");
                    $("#" + id).show();
                }
            }
        }

        function showForm(PostId) {
            $('#jQuery_comment_add_' + PostId).toggle("fast");
            $("#form_" + PostId).toggle("fast");
            $("#comment_body_form_" + PostId).val("");
        }

        function displayComments() {
            for (let c of comments_json) {
                let html = "";
                var div = $("#comments_" + c.PostId);
                div.html("");
                for (let com of c.Comments) {
                    html += "<div class=\"comments\">";
                    html += "<p>" + com.Body + " - <a href=\"\">" + com.Author[0].pseudo + " </a> " + com.Timestamp +" </p>";
                    if (com.Author[0].Id == userId || userRole.localeCompare("admin") == 0) {
                        console.log(com.Id);
                        html += "<form action=\"comment/edit/" + com.Id + "\" name=\"edit_comment\" method=\"post\">";
                        html += "<input type=\"submit\" value=\"edit\"></input>";
                        html += "</form>";
                        html += "<form action=\"comment/delete/" + com.Id + "\" name=\"delete_comment\" method=\"post\">";
                        html += "<input type=\"submit\" value=\"delete\"></input>";
                        html += "</form>";
                    }
                    html += "</div>";
                }
                div.html(html);
            }
        }

        function getComments(postId) {
            $.get("post/get_comments_service/" + postId, function(data){
                comments_json = data;
                displayComments();
            }, "json").fail(function(){
                    quest_div.html("<tr><td>Error encountered while retrieving the comments!</td></tr>");
            });
        }

        function postComment(e) {
            e.preventDefault();
            const frm = e.target;
            if (!$(frm).valid())
                console.log("errors dans la form: " + frm.id);
            else {
                var markdown_body = $("#comment_body_" + frm.id).val();
                var postId =  frm.id.replace('form_', '');
                var data = {markdown_body: markdown_body,
                    postId : postId};
                $.post("comment/add_service/",
                    data,
                    function (data) {
                        $("#comment_body_form_" + postId).val("");
                        $("#form_" + postId).hide();
                        $("#jQuery_comment_add_" + postId).show();
                        getComments(postId);
                    }
                ).fail(function (){
                    alert("Error encountered while posting comment!");
                    $("#comment_body_form_" + postId).val("");
                    getComments(postId);
                });
            }
            return false;
        }
        
        function showVotes() {
            var divs = document.getElementsByTagName("div");
            for (var i = 0; i < divs.length; i++) {
                if (divs[i].id.indexOf("votes_") == 0) {
                    id = divs[i].getAttribute("id");
                    $("#" + id).show();
                }
            }
        }

        function displayVotes() {
            for (let v of votes_json) {
                let html = "";
                var div = $("#votes_" + v.PostId);
                div.html("");
                var sum = v.Votes[0].Sum;
                var hasVoted = v.Votes[0].hasVoted;
                html += "<form id=\"" + v.PostId + "\" method=\"post\">";
                html += "<button class=\"voteUpvoteDown\" type=\"button\" onclick=\"voteUp(this.form.id);\">";
                if (hasVoted == 1) {
                    html += "<i class=\"fa fa-thumbs-up\"></i>";
                } 
                else {
                    html += "<i class=\"fa fa-thumbs-o-up\"></i>";
                }
                html += "</button>";
                html += "</form>";
                html += "<p>" + sum + " votes(s)</p>";
                html += "<form id=\"" + v.PostId + "\" method=\"post\">";
                html += "<button class=\"voteUpvoteDown\" type=\"button\" onclick=\"voteDown(this.form.id);\">";
                if (hasVoted == "-1") {
                    html += "<i class=\"fa fa-thumbs-down\"></i>";
                } 
                else {
                    html += "<i class=\"fa fa-thumbs-o-down\"></i>";
                }
                html += "</button>";
                html += "</form>";
                div.html(html);
            }
        }

        function voteUp(postId) {
            var data = {postId : postId};
            $.post("vote/voteUp_service/",
                data,
                function (data) {
                    getVotes(postId);
                }
            ).fail(function (){
                alert("Error encountered while voting up!");
                getVotes(postId);
            });
        }

        function voteDown(postId) {
            var data = {postId : postId};
            $.post("vote/voteDown_service/",
                data,
                function (data) {
                    getVotes(postId);
                }
            ).fail(function (){
                alert("Error encountered while voting down!");
                getVotes(postId);
            });
        }

        function getVotes(postId) {
            $.get("post/get_votes_service/" + postId, function(data){
                votes_json = data;
                displayVotes();
            }, "json").fail(function(){
                    $("#entete").html("<tr><td>Error encountered while retrieving the votes!</td></tr>");
            });
        }

    </script>
</head>
<body id = "show_body">
    <header>
        <?php include('menu.php'); ?>
    </header>
    <main id ="voir">
        <div class="show">
            <!-- DEBUT AFFICHAGE QUESTION -->
            <div class ="question_cadre">
                <div id = "entete">
                    <h2><?= $post->Title ?></h2>
                    <p class="author">Asked <?=$post->Timestamp ?> by <a href=""><?= $post->Author['FullName']?></a>
                        <?php if (isset($user) && ($user->Id == $post->Author['UserId'] || strcmp("admin", $user->Role) == 0)):?>
                        <a href = "post/edit/<?= $post->PostId ?>">
                            <i class="fa fa-edit"></i>
                        </a>
                            <?php if (($post->NbAnswer[0] == 0 && !isset($post->comments[0])) || strcmp("admin", $user->Role) == 0):?>
                                <a href = "post/confirm_delete/<?= $post->PostId ?>">
                                    <i class="fa fa-trash-o"></i>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class = "tags">
                            <?php if (isset($post->tags)): ?>
                                <?php foreach($post->tags as $tag): ?>
                                    <a href="post/posts/tag/1/<?= $tag->Id ?>" class="tagname"><?=$tag->Name?></a>
                                    <?php if (isset($user) && ($user->Id == $post->Author['UserId'] || strcmp("admin", $user->Role) == 0)):?>
                                        <a href = "tag/remove_tag/<?= $post->PostId?>/<?= $tag->Id?>" class="removetag">
                                            <i class="fa fa-remove"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?></p>
                            <?php endif; ?>
                            <?php if ((isset($user) && ($user->Id == $post->Author['UserId'] || strcmp("admin", $user->Role) == 0)) && count($post->tags) < Configuration::get("max_tags")) : ?>
                                <form action="tag/add_to_post/<?= $post->PostId?>" method="post" class="handle_tags">
                                    <select name="tag">
                                        <?php foreach($other_tags as $t) :?>
                                            <option value="<?=$t->Id?>"><?= $t->Name?></option>
                                        <?php endforeach;?>
                                    </select>
                                    <button type="submit"><i class="fa fa-plus-square"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </p>
                </div>
                <div id="corp_and_vote">
                    <div class = "votes">
                        <div class="votes_to_hide">
                            <?php if (isset($user)): ?>
                                <form action ="vote/thumbsUp/<?= $post->PostId ?>" method= "post">
                                    <button type="submit">
                                        <?php if (isset($user) && ($post->PostId == $post->vote->PostId) && ($post->vote->UpDown == 1)):?>
                                            <i class="fa fa-thumbs-up"></i>
                                        <?php else: ?>
                                            <i class="fa fa-thumbs-o-up"></i>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            <?php endif;?>
                            <p>
                                <?php if ($post->NbVotes[0] == NULL):?>
                                    0
                                <?php else: ?>
                                    <?= $post->NbVotes[0] ?>
                                <?php endif; ?>    
                            vote(s)</p>
                            <?php if (isset($user)): ?>
                                <form action ="vote/thumbsDown/<?= $post->PostId ?>" method = "post">
                                    <button type="submit">
                                        <?php if (isset($user) && ($post->PostId == $post->vote->PostId) && ($post->vote->UpDown == -1)):?>
                                            <i class="fa fa-thumbs-down"></i>
                                        <?php else: ?>
                                            <i class="fa fa-thumbs-o-down"></i>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            <?php endif;?>
                        </div>
                    </div>
                    <div class="votes_js">
                        <div id="votes_<?=$post->PostId?>" hidden>
                        </div>
                    </div>

                    <div id="mainQuestion">
                    <p> <?= Utils::parsedown($post->Body) ?> </p>
                    </div>
                </div>
                <div id="comments_<?= $post->PostId?>">
                    <?php foreach($post->comments as $c) :?>
                        <div class="comments">
                            <p><?=$c->Body?> - <a href=""><?= $c->Author['FullName']?></a> <?= $c->Timestamp ?></p>
                            <?php if (isset($user) && ($user->Id == $c->Author['UserId'] || strcmp("admin", $user->Role) == 0)):?>
                                <form action="comment/edit/<?= $c->Id ?>" name="edit_comment" method="post">
                                    <input type="submit" value="edit"></input>
                                </form>
                                <form action="comment/delete/<?=$c->Id?>" name="delete_comment" method="post">
                                    <input type="submit" value="delete"></input>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form action="comment/add/<?=$post->PostId?>" class="comment_add">
                    <input type="submit" value="add a comment">
                </form>

                <input class="comment_add" type="button" id="jQuery_comment_add_<?= $post->PostId ?>" value="add a comment" hidden>

                <form id="form_<?=$post->PostId?>" name="comment_add" class="comment_form" method ="post" onsubmit="postComment(event)"hidden>
                    <textarea id="comment_body_form_<?= $post->PostId?>" name="markdown_body" rows=5 cols=100></textarea> <br>
                    <input type="submit" class="comment_btn" value="Comment" id="<?= $post->PostId ?>">
                    <input type="button" value="Cancel" class="cancel_btn" onclick="showForm(<?= $post->PostId ?>);">
                </form>
            </div>
            
            <!-- FIN AFFICHAGE QUESTION -->

            <div id ="nb_answers">
                <h2><?= $post->NbAnswer[0] ?> answer(s)</h2>
            </div>
            <div class = "all_answers">

                <!-- DEBUT AFFICHAGE REPONSES -->
                <?php foreach($answers as $a) :?>
                    <div class = "answers">
                        <div class="ans_and_vote">
                            <div class="answers_body">
                                <p> <?= Utils::parsedown($a->Body) ?> </p>
                                <div class = "author">
                                    <p>Answered <?=$a->Timestamp ?> by <a href=""><?= $a->Author['FullName'] ?></a>
                                        <?php if (isset($user) && ($user->Id == $post->Author['UserId'] || strcmp("admin", $user->Role) == 0)):?>
                                            <?php if (!isset($post->AcceptedAnswerId) || (isset($post->AcceptedAnswerId) && $post->AcceptedAnswerId != $a->PostId)):?>
                                                <form action = "post/AcceptedAnswer/<?= $a->PostId ?>" method = "post">
                                                    <button><i class="fa fa-check-circle-o"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (isset($user) && ($user->Id == $post->Author['UserId'] || strcmp("admin", $user->Role) == 0)):?>
                                            <a href = "post/edit/<?= $a->PostId ?>">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <?php if (!isset($a->comments[0]) || (isset($user) && strcmp("admin", $user->Role) != 0)): ?>
                                                <a href = "post/confirm_delete/<?= $a->PostId ?>">
                                                    <i class="fa fa-trash-o"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class ="votes">
                                <div class="votes_to_hide">
                                    <?php if (isset($user)): ?>
                                        <form action ="vote/thumbsUp/<?= $a->PostId ?>" method = "post">
                                            <button type="submit">
                                                <?php if (isset($user) && ($a->PostId == $a->vote->PostId) && ($a->vote->UpDown == 1)):?>
                                                <i class="fa fa-thumbs-up"></i>
                                                <?php else: ?>
                                                    <i class="fa fa-thumbs-o-up"></i>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <p>
                                        <?php if ($a->NbVotes[0] == NULL):?>
                                            0
                                        <?php else: ?>
                                            <?= $a->NbVotes[0] ?>
                                        <?php endif; ?>    
                                    vote(s)
                                    </p>
                                    <?php if (isset($user)): ?>
                                        <form action ="vote/thumbsDown/<?= $a->PostId ?>" method="post">
                                            <button type="submit">
                                                <?php if (isset($user) && ($a->PostId == $a->vote->PostId) && ($a->vote->UpDown == -1)):?>
                                                    <i class="fa fa-thumbs-down"></i>
                                                <?php else: ?>
                                                    <i class="fa fa-thumbs-o-down"></i>
                                                <?php endif; ?>
                                            </button>
                                        </form> <br>
                                    <?php endif; ?>
                                </div>
                                <div class="votes_js">
                                    <div id="votes_<?=$a->PostId?>" hidden>
                                    </div>
                                </div>
                                <div class = "acceptedAnswer">
                                    <?php if (isset($post->AcceptedAnswerId) && $post->AcceptedAnswerId == $a->PostId):?>
                                        <i class="fa fa-check" style="color:green"></i>
                                        <?php if (isset($user) && ($user->Id == $post->Author['UserId'] || strcmp("admin", $user->Role) == 0)):?>
                                            <form action = "post/DeleteAcceptedAnswer/<?= $a->PostId ?>" method = "post">
                                                <button><i class="fa fa-times" style="color:red"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div id="comments_<?= $a->PostId?>">
                            <?php foreach($a->comments as $c) :?>
                                <div class="comments">
                                    <p><?=$c->Body?> - <a href=""><?= $c->Author['FullName']?></a> <?= $c->Timestamp ?></p>
                                    <?php if (isset($user) && ($user->Id == $c->Author['UserId'] || strcmp("admin", $user->Role) == 0)):?>
                                        <form action="comment/edit/<?= $c->Id?>" name="edit_comment" method="get">
                                            <input type="submit" value="edit"></input>
                                        </form>
                                        <form action="comment/delete/<?=$c->Id?>" name="edit_comment" method="post">
                                            <input type="submit" value="delete"></input>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form action="comment/add/<?=$a->PostId?>" class="comment_add">
                            <input type="submit" value="add a comment">
                        </form>

                        <input class="comment_add" type="button" id="jQuery_comment_add_<?= $a->PostId ?>" value="add a comment" hidden>

                <form id="form_<?=$a->PostId?>" name="comment_add" class="comment_form" method ="post" onsubmit="postComment(event)"hidden>
                    <textarea id="comment_body_form_<?= $a->PostId?>" name="markdown_body" rows=5 cols=100></textarea> <br>
                    <input type="submit" class="comment_btn" value="Comment" id="<?= $a->PostId ?>">
                    <input type="button" value="Cancel" class="cancel_btn" onclick="showForm(<?= $a->PostId ?>);">
                </form>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- FIN AFFICHAGE REPONSES -->
            
            <div class = "reply_form">
                <?php if (isset($user)): ?>
                    <h3>Your Answer</h3>
                    <form id="reply_form" action= "post/reply/<?= $post->PostId ?>" method="post">
                    <textarea form="reply_form" name="markdown_body" id="reply_body" rows=10 cols=100></textarea> <br>
                    <button type="submit">Post your answer</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php if (isset($errors) && count($errors) != 0):?>
                <ul>
                    <?php foreach ($errors as $e):?>
                        <li><?=$e?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif;?>
        </div>
    </main>
</html>
