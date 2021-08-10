<?php

require_once 'framework/View.php';
require_once 'framework/Controller.php';
require_once 'model/Post.php';
require_once 'model/Vote.php';
require_once 'model/Tag.php';
require_once 'util/Utils.php';

class ControllerComment extends Controller{

    public function index()
    {
        $this->redirect("post", "index");
    }

    public function add()
    {
        if (!isset($_GET['param1']))
            (new View("error"))->show(array("error"=>"Your comment must be added to a post."));
        else {
            $user = NULL;
            if (!$this->user_logged())
                (new View("error"))->show(array("error"=>"You must be connected to add a comment."));
            else {
                $user = $this->get_user_or_redirect();
                if (!($post = Post::getQuestionById($_GET['param1'], $user)))
                    (new View("error"))->show(array("error"=>"Wrong post id."));
                else {
                    if (!isset($_POST["markdown_body"]) || $this->post_comment($user, $post))
                        (new View("comment_add"))->show(array("post"=>$post, "user"=>$user));
                    else {
                        if ($post->ParentId != NULL)
                            $post = Post::getQuestionById($post->ParentId, $user);
                        $this->redirect("post", "show", "$post->PostId");
                    }
                }
            }
        }
    }

    public function add_available_service()
    {
        $res = "false";
        if (isset($_POST["markdown_body"]) && Post::check_ask($_POST["markdown_body"]))
            $res = "true";
        echo $res;
    }

    public function add_service()
    {
        $user = $this->get_user_or_redirect();
        $postId = $_POST["postId"];
        $body = $_POST["markdown_body"];
        $comment = new Comment(-1, null, $postId, $body, null);
        return ($comment->add($user->Id));
    }

    public function post_comment($user, $post)
    {
        $errors = [];
        $comment = new Comment(-1, null, -1, $_POST["markdown_body"], null);
        if (!$comment->validate())
            $errors[] = "You can't comment that!";
        $body = $_POST["markdown_body"];
        $comment = new Comment(-1, null, $post->PostId, $body, null);
        $comment->add($user->Id);
        return ($errors);
    }

    public function edit()
    {
        if (!isset($_GET['param1']))
            (new View("error"))->show(array("error"=>"You can't edit an invisible comment."));
        else {
            $user = NULL;
            if (!$this->user_logged())
                (new View("error"))->show(array("error"=>"You must be connected to edit a comment."));
            else {
                $user = $this->get_user_or_redirect();   
                $comment = Comment::getComment($_GET['param1']);
                if (!isset($comment))
                    (new View("error"))->show(array("error"=>"It seems that this comment doesn't exist."));
                else {
                    if (!($post = Post::getQuestionById($comment->PostId, $user)))
                        (new View("error"))->show(array("error"=>"Wrong post id."));
                    else {
                        if (!isset($_POST["markdown_body"]))
                            (new View("comment_edit"))->show(array("user"=>$user, "post"=>$post, "comment"=>$comment));
                        else {
                            if ($user->Id != $comment->Author['UserId'] && strcmp("admin", $user->Role) != 0)
                                (new View("error"))->show(array("error"=>"This is not your comment, why would you edit it ?"));
                            else {
                                $comment = new Comment($comment->Id, $comment->Author, -1, $_POST["markdown_body"], $comment->Timestamp);
                                if (!$comment->validate())
                                    (new View("comment_edit"))->show(array("user"=>$user, "post"=>$post, "comment"=>$comment, "errors"=>"You can't comment that."));
                                else
                                {
                                    $body = $_POST["markdown_body"];
                                    $comment = Comment::getComment($comment->Id);
                                    $comment->update($body);
                                    if ($post->ParentId != NULL)
                                        $post = Post::getQuestionById($post->ParentId, $user);
                                    $this->redirect("post", "show", "$post->PostId");
                                }
                            }
                        }
                    }
                }
            }
        } 
    }

    public function delete()
    {
        if (!isset($_GET['param1']))
            (new View("error"))->show(array("error"=>"You can't delete an invisible comment."));
        else {
            $user = NULL;
            if (!$this->user_logged())
                (new View("error"))->show(array("error"=>"You must be connected to edit a comment."));
            else {
                $user = $this->get_user_or_redirect();   
                $comment = Comment::getComment($_GET['param1']);
                if (!isset($comment))
                    (new View("error"))->show(array("error"=>"It seems that this comment doesn't exist."));
                else {
                    $post = Post::getQuestionById($comment->PostId, $user);
                    if ($user->Id != $comment->Author['UserId'] && strcmp("admin", $user->Role) != 0)
                        (new View("error"))->show(array("error"=>"This is not your comment, why would you delete it ?"));
                    else {
                        $comment = Comment::getComment($comment->Id);
                        $comment->delete();
                        if ($post->ParentId != NULL)
                            $post = Post::getQuestionById($post->ParentId, $user);
                        $this->redirect("post", "show", "$post->PostId");
                    }
                }
            }
        }            
    }
}
