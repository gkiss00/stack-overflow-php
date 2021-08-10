<?php

require_once 'framework/View.php';
require_once 'framework/Controller.php';
require_once 'model/Post.php';

class ControllerVote extends Controller{

    public function index(){
        $this->redirect("post", "index");
    }

    public function thumbsUp(){
        if (isset($_GET['param1']))
        {
            $user = NULL;
            $post = Post::getQuestionById($_GET['param1'], $user);
            if ($post->ParentId != NULL){
                $post = Post::getQuestionById($post->ParentId, $user);
            }
            $vote = NULL;
            if ($this->user_logged()){
                $user = $this->get_user_or_redirect();
                $vote = new Vote($user->Id, $_GET['param1'], 1);
                $vote->thumbs();
                $post = Post::getQuestionById($post->PostId, $user);
            }
            $this->redirect("post", "show", $post->PostId);
        }
        else
            (new View("error"))->show(array("error"=>"You haven't provided any ID."));
    }

    public function thumbsDown(){
        if (isset($_GET['param1']))
        {
            $user = NULL;
            $post = Post::getQuestionById($_GET['param1'], $user);
            if ($post->ParentId != NULL){
                $post = Post::getQuestionById($post->ParentId, $user);
            }
            $answers = Post::getAnswersById($post->PostId, $user);
            $vote = NULL;
            if ($this->user_logged()){
                $user = $this->get_user_or_redirect();
                $vote = new Vote($user->Id, $_GET['param1'], -1);
                $vote->thumbs();
                $post = Post::getQuestionById($post->PostId, $user);
                $answers = Post::getAnswersById($post->PostId, $user);
            }
            $this->redirect("post", "show", $post->PostId);
        }
        else
            (new View("error"))->show(array("error"=>"You haven't provided any ID."));
    }

    public function voteUp_service() {
        $user = $this->get_user_or_redirect();
        $postId = $_POST["postId"];
        $vote = new Vote($user->Id, $postId, 1);
        return ($vote->thumbs());
    }

    public function voteDown_service() {
        $user = $this->get_user_or_redirect();
        $postId = $_POST["postId"];
        $vote = new Vote($user->Id, $postId, -1);
        return ($vote->thumbs());
    }
}

?>