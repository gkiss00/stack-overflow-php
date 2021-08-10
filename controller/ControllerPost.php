<?php

require_once 'framework/View.php';
require_once 'framework/Controller.php';
require_once 'model/Post.php';
require_once 'model/Vote.php';
require_once 'model/Tag.php';
require_once 'util/Utils.php';

class ControllerPost extends Controller{

    public function index(){
        $user = NULL;
        if ($this->user_logged())
            $user = $this->get_user_or_redirect();
        if (isset($_GET['param1']) && strcmp($_GET['param1'], "active") == 0){
            $questions = Post::getAllQuestionsSortedByActive(1);
            $nb_pages = Post::getNbOfPagesByActive();
            $sort = "active";
            $active = 1;

            (new View("index"))->show(array("user"=>$user, "questions"=>$questions, "sort"=>$sort, "nb_pages"=>$nb_pages, "active"=>$active));
        }
        else if (isset($_GET['param1']) && strcmp($_GET['param1'], "unanswered") == 0){
            $questions = Post::getAllQuestionsSortedByUnanswered(1);
            $nb_pages = Post::getNbOfPagesByUnanswered();
            $sort = "unanswered";
            $active = 1;

            (new View("index"))->show(array("user"=>$user, "questions"=>$questions, "sort"=>$sort, "nb_pages"=>$nb_pages, "active"=>$active));
        }
        else if (isset($_GET['param1']) && strcmp($_GET['param1'], "votes") == 0){
            $questions = Post::getAllQuestionsSortedByVotes(1);
            $nb_pages = Post::getNbOfPagesByVotes();
            $sort = "votes";
            $active = 1;

            (new View("index"))->show(array("user"=>$user, "questions"=>$questions, "sort"=>$sort, "nb_pages"=>$nb_pages, "active"=>$active));
        }
        else if (isset($_POST['search']) && $_POST['search'] != NULL) {
            $filter = $_POST['search'];
            $search_value = $_POST['search'];
            $this->redirect("Post", "index", "search", Utils::url_safe_encode($filter));
        }
        else if (isset($_GET["param2"])) {
            $filter = Utils::url_safe_decode($_GET["param2"]);
            $url = $_GET["param2"];
            if (!$filter)
                Tools::abort("Bad url parameter");
            $questions = Post::getSearchedQuestion($filter, $user, 1);
            $nb_pages = Post::getNbOfPagesBySearch($filter);
            $active = 1;
    
            (new view("index"))->show(array("user"=>$user, "questions"=>$questions, "user"=>$user, "search_value"=>$filter, "nb_pages"=>$nb_pages, "active"=>$active, "sort"=>"search", "url"=>$url));
        }
        else {
            $questions = Post::getAllQuestionsSortedByNewest(1);
            $nb_pages = Post::getNbOfPagesByNewest();
            $sort = "newest";
            $active = 1;
            $quest_json = Post::get_questions_as_json($questions);

            (new View("index"))->show(array("user"=>$user, "questions"=>$questions, "sort"=>$sort, "user"=>$user, "nb_pages"=>$nb_pages, "active"=>$active, "quest_json"=>$quest_json));
        }
    }

    public function show(){
        if (isset($_GET['param1'])){
            $vote = NULL;
            $user = NULL;
            if ($this->user_logged()){
                $user = $this->get_user_or_redirect();
                $post = Post::getQuestionById($_GET["param1"], $user);
                $vote = Vote::vote($user, $post->PostId);
            }
            $post = Post::getQuestionById($_GET["param1"], $user);
            if ($post->PostId == NULL)
                (new View("error"))->show(array("error"=>"The provided ID of the question doesn't exist."));
            else {
                $answers = Post::getAnswersById($_GET["param1"], $user);
                $other_tags = Tag::getOtherTags($post->PostId);
                $comments = Comment::getAllComments($post, $answers);
                $comments_as_json = Comment::get_comments_as_json($comments);
                if (isset($user))
                    $votes_as_json = Vote::get_votes_as_json($post, $answers, $user->Id);
                else
                    $votes_as_json = Vote::get_votes_as_json($post, $answers, null);
                    $max_tags = Configuration::get("max_tag");
                (new View("show"))->show(array("post"=>$post, "answers"=>$answers, "user"=>$user, "vote"=>$vote, "other_tags"=>$other_tags, "comments_as_json"=>$comments_as_json, "votes_as_json"=>$votes_as_json, "max_tags"=>$max_tags));
            }
        }
        else
            (new View("error"))->show(array("error"=>"You havent provided any ID for the question."));
    }

    public function reply(){
        $errors = [];
        if (isset($_GET['param1']) && isset($_POST["markdown_body"])){
            $user = $this->get_user_or_redirect();
            $body = $_POST["markdown_body"];
            $post = new Post(-1, null, null, $body, null, -1, -1, -1, -1, -1, null, null, null);
            $errors = $post->validate_reply();
            if (count($errors) == 0){
                $post = new Post($_GET["param1"], null, null, $body, null, -1, -1, -1, -1, -1, null, null, null);
                $post->reply($user);
                $this->redirect("post", "show", $_GET["param1"]);
            }
        }
        if (!(isset($_GET['param1'])))
            (new View("error"))->show(array("error"=>"You havent provided any ID for the question you want to reply to."));
        else {
            $vote = NULL;
            $user = NULL;
            if ($this->user_logged()){
                $user = $this->get_user_or_redirect();
                $post = Post::getQuestionById($_GET["param1"], $user);
                $vote = new Vote($user->Id, $post->PostId, 0);
                $vote = $vote->vote($user, $post->PostId);
            }
            $post = Post::getQuestionById($_GET["param1"], $user);
            if ($post->PostId == NULL)
                (new View("error"))->show(array("error"=>"The ID provided doesn't exist."));
            else if ($post->ParentId != NULL)
                (new View("error"))->show(array("error"=>"You can't reply to an answer."));
            else {
                $answers = Post::getAnswersById($_GET["param1"], $user);
                (new View("show"))->show(array("post"=>$post, "answers"=>$answers, "user"=>$user, "vote"=>$vote, "errors"=>$errors));
            }
        }
    }

    public function ask(){
        $user = $this->get_user_or_redirect();
        $errors = [];
        $max_tags = Configuration::get("max_tags");
        if (isset($_POST['subject']) && isset($_POST["markdown_body"]) && $_POST['subject'] != NULL && $_POST["markdown_body"] != NULL){
            $body = $_POST["markdown_body"];
            if (isset($_POST['selectedtags']))
                $selectedtags = $_POST['selectedtags'];
            else
                $selectedtags = NULL;
            $post = new Post(-1, null, $_POST['subject'], $body, null, -1, -1, -1, -1, -1, null, null, null);
            $errors = $post->validate_ask($selectedtags);
            if (count($errors) == 0) {
                $post = new Post(-1, null, $_POST['subject'], $body, null, -1, -1, -1, -1, -1, null, null, null);
                $postid = $post->ask($user, $selectedtags);
                $this->redirect("post", "show", $postid);
            }
            else
                (new View("ask"))->show(array("user"=>$user, "errors"=>$errors, "tags"=>Tag::getAllTags(), "subject"=>$_POST["subject"], "body"=>$_POST["markdown_body"], "alreadyselected"=>$selectedtags, "max_tags"=>$max_tags));
        } 
        else {
            (new View("ask"))->show(array("user"=>$user, "tags"=>Tag::getAllTags(), "max_tags"=>$max_tags));
        }
    }

    public function confirm_delete(){
        if (isset($_GET['param1'])){
            $user = $this->get_user_or_redirect();
            $post = Post::getQuestionById($_GET['param1'], $user);
            if ($post->Author['UserId'] == $user->Id || strcmp("admin", $user->Role) == 0)
            {
                (new View("delete"))->show(array("post"=>$post, "user"=>$user));
            }
            else {
                (new View("error"))->show(array("error"=>"You can't delete something you haven't posted."));

            }
        }
        else
            (new View("error"))->show(array("error"=>"You haven't provided any ID."));
    }

    public function delete(){
        $user = $this->get_user_or_redirect();
        if (isset($_GET['param1'])){
            $post = Post::getQuestionById($_GET['param1'], $user);
            if ($post->Author['UserId'] == $user->Id || strcmp("admin", $user->Role) == 0){
                if (isset($post->comments[0]) && $post->ParentId == NULL && strcmp("admin", $user->Role) != 0)
                    (new View("error"))->show(array("error"=>"You can't delete a question that has comments.", "postId"=>$post->PostId));
                else if ($post->NbAnswer[0] != 0 && $post->ParentId == NULL && strcmp("admin", $user->Role) != 0)
                    (new View("error"))->show(array("error"=>"You can't delete a question that has answers.", "postId"=>$post->PostId));
                else if (isset($post->comments[0]) && $post->ParentId != NULL && strcmp("admin", $user->Role) != 0)
                    (new View("error"))->show(array("error"=>"You can't delete an answers that has comments.", "postId"=>$post->PostId));
                else {
                    Tag::deletePosttags($post);
                    $post->delete($user);
                    if ($post->ParentId == NULL)
                    {
                        $this->redirect("post", "index");
                    }
                    else
                    {
                        $this->redirect("post", "show", $post->ParentId);
                    }
                }
            }
            else
            {
                (new View("error"))->show(array("error"=>"You can't delete something you haven't posted."));
            }
        }
        else
            (new View("error"))->show(array("error"=>"You haven't provided any ID."));
    }

    public function edit(){
        if (isset($_GET['param1'])){
            $user = $this->get_user_or_redirect();
            $post = Post::getQuestionById($_GET['param1'], $user);
            if ((!isset($_POST["markdown_body"])) || (!isset($post->ParentId) && !isset($_POST['subject']))){
                if ($post->Author['UserId'] == $user->Id || strcmp($user->Role, "admin") == 0) {
                    (new View("edit"))->show(array("user"=>$user, "post"=>$post));
                }
                else {
                    $this->redirect("post", "show", $_GET["param1"]);
                }
            }
            else {
                $body = $_POST["markdown_body"];
                $title = NULL;
                $errors = [];
                if (isset($_POST['subject'])) {
                    $title = $_POST['subject'];
                    $errors = $post->validate_ask(null);
                }
                else {
                    $errors = $post->validate_body();
                }
                if (count($errors) == 0) {
                    $post = $post->update($body, $user, $title);
                    $this->redirect("post", "show", $post->PostId);
                }
                else
                    (new View("edit"))->show(array("user"=>$user, "post"=>$post, "errors"=>$errors));
            }
        }
        else
            (new View("error"))->show(array("error"=>"You haven't provided any ID."));
    }

    public function AcceptedAnswer(){
        $user = $this->get_user_or_redirect();
        if (isset($_GET['param1'])){
            $post = Post::getQuestionById($_GET['param1'], $user);
            $parent = Post::getQuestionById($post->ParentId, $user);
            if ($user->Id == $parent->Author['UserId'] || strcmp($user->Role, "admin") == 0){
                $post->acceptAnswer($_GET['param1']);
            }
            $parent = Post::getQuestionById($post->ParentId, $user);
            $this->redirect("post", "show", $parent->PostId);
        }
        else
            (new View("error"))->show(array("error"=>"You haven't provided any ID."));
    }

    public function DeleteAcceptedAnswer(){
        $user = $this->get_user_or_redirect();
        if (isset($_GET['param1'])) {
            $post = Post::getQuestionById($_GET['param1'], $user);
            $parent = Post::getQuestionById($post->ParentId, $user);
            if ($user->Id == $parent->Author['UserId'] || strcmp($user->Role, "admin") == 0){
                $post->deleteAcceptedAnswer();
            }
            $parent = Post::getQuestionById($post->ParentId, $user);
            $this->redirect("post", "show", $parent->PostId);
        }
        else
            (new View("error"))->show(array("error"=>"You haven't provided any ID."));
    }

    public function posts()
    {
        if (!(isset($_GET["param1"]) && isset($_GET["param2"])))
            (new View("error"))->show(array("error"=>"You must have at least 2 parameters"));
        else if (isset($_GET['param3']) && (!(is_numeric($_GET["param3"]) && strcmp($_GET['param1'], "tag") == 0) && !(strcmp($_GET['param1'], "search") == 0)))
            (new View("error"))->show(array("error"=>"ID must be an int"));
        else if (!(is_numeric($_GET["param2"])))
            (new View("error"))->show(array("error"=>"Page number must be an int"));
        else 
        {
            $questions;
            $nb_pages;
            $to_show = $_GET["param1"];
            $page = $_GET["param2"];
            $user = NULL;
            if ($this->user_logged())
                $user = $this->get_user_or_redirect();
            if (strcmp($to_show, "tag") == 0 && isset($_GET['param3'])) {
                $id = $_GET["param3"];
                $questions = Post::getPostByTagId($page, $id, $user);
                $nb_pages = Post::getNbOfPagesByTagId($id);
                $tag = Tag::getTagById($id); 
                
                if (!isset($questions) || !isset($tag))    
                    (new View("error"))->show(array("error"=>"No results found for these parameters. There's certainly a mistake !"));
                else
                    (new View("index"))->show(array("user"=>$user, "questions"=>$questions, "sort"=>"posts", "selected_tag"=>$tag, "nb_pages"=>$nb_pages, "active"=>$page));
            }
            else {
                if (strcmp($to_show, "newest") == 0){
                    $questions = Post::getAllQuestionsSortedByNewest($page);
                    $nb_pages = Post::getNbOfPagesByNewest();
                }
                else if (strcmp($to_show, "active") == 0) {
                    $questions = Post::getAllQuestionsSortedByActive($page);
                    $nb_pages = Post::getNbOfPagesByActive();
                }
                else if (strcmp($to_show, "unanswered") == 0) {
                    $questions = Post::getAllQuestionsSortedByUnanswered($page);
                    $nb_pages = Post::getNbOfPagesByUnanswered();
                }
                else if (strcmp($to_show, "votes") == 0) {
                    $questions = Post::getAllQuestionsSortedByVotes($page);
                    $nb_pages = Post::getNbOfPagesByVotes();
                }
                else if (strcmp($to_show, "search") == 0) {
                    $filter = Utils::url_safe_decode($_GET["param3"]);
                    if (!$filter)
                        Tools::abort("Bad url parameter");
                    $questions = Post::getSearchedQuestion($filter, $user, $page);
                    $nb_pages = Post::getNbOfPagesBySearch($filter);
                }
                if (!isset($questions))
                    (new View("error"))->show(array("error"=>"No results found for these parameters. There's certainly a mistake !"));
                else if (isset($filter))
                    (new View("index"))->show(array("user"=>$user, "questions"=>$questions, "sort"=>$to_show, "nb_pages"=>$nb_pages, "active"=>$page, "search_value"=>$filter, "url"=>$_GET['param3']));
                else
                    (new View("index"))->show(array("user"=>$user, "questions"=>$questions, "sort"=>$to_show, "nb_pages"=>$nb_pages, "active"=>$page));
            }
        }
    }

    public function get_details_service() {
        $user = null;
        if ($this->user_logged())
            $user = $this->get_user_or_redirect();
        if (!isset($_GET['param1']) || (isset($_GET['param1']) && 
        (strcmp($_GET['param1'], "day") == 0) && (strcmp($_GET['param1'], "week") == 0) && (strcmp($_GET['param1'], "month") == 0) && (strcmp($_GET['param1'], "year") == 0)))
            (new View("error"))->show(array("user"=>$user, "error"=>"You must give a valid period as first parameter. It can be day, week, month or year."));
        else if (!isset($_GET['param2']) || (isset($_GET['param2']) && !is_int((int)$_GET['param2'])))
            (new View("error"))->show(array("user"=>$user, "error"=>"You must give a valid number as second parameter"));
        else if (!(isset($_GET['param3']) || (isset($_GET['param3']) && !Member::get_member_by_pseudo($_GET['param3']))))
            (new View("error"))->show(array("user"=>$user, "error"=>"You must give a valid pseudo as third parameter"));
        else {
            $period = $_GET['param1'];
            $number = (int)$_GET['param2'];
            $pseudo = $_GET['param3'];
            $user = Member::get_member_by_pseudo($pseudo);
            $details_json = Post::get_details_as_json($period, $number, $user->Id, $user);
            echo $details_json;
        }
    }

    public function get_newest_service() {
        $page = (int)$_GET["param1"];
        $questions = Post::getAllQuestionsSortedByNewest($page);
        $quest_json = Post::get_questions_as_json($questions);
        echo $quest_json;
    }

    public function get_active_service() {
        $page = (int)$_GET["param1"];
        $questions = Post::getAllQuestionsSortedByActive($page);
        $quest_json = Post::get_questions_as_json($questions);
        echo $quest_json;
    }

    public function get_unanswered_service() {
        $page = (int)$_GET["param1"];
        $questions = Post::getAllQuestionsSortedByUnanswered($page);        
        $quest_json = Post::get_questions_as_json($questions);
        echo $quest_json;
    }

    public function get_vote_service() {
        $page = (int)$_GET["param1"];
        $questions = Post::getAllQuestionsSortedByVotes($page);
        $quest_json = Post::get_questions_as_json($questions);
        echo $quest_json;
    }

    public function get_search_service() {
        $page = (int)$_GET["param2"];
        $search = $_GET["param1"];
        $user = null;
        if ($this->user_logged())
            $user = $this->get_user_or_redirect();
        $questions = Post::getSearchedQuestion($search, $user, $page);
        $quest_json = Post::get_questions_as_json($questions);
        echo $quest_json;
    }
    
    public function get_tagged_service() {
        $page = (int)$_GET["param2"];
        $tag = $_GET["param1"];
        $id = Tag::getTagIdByName($tag);
        $user = null;
        if ($this->user_logged())
            $user = $this->get_user_or_redirect();
        $questions = Post::getPostByTagId($page, $id[0], $user);
        $quest_json = Post::get_questions_as_json($questions);
        echo $quest_json;
    }

    public function get_newest_page_service() {
        $nb_pages = Post::getNbOfPagesByNewest();
        $nb_pages = Post::get_nb_page_as_json($nb_pages);
        echo $nb_pages;
    }
    
    public function get_active_page_service() {
        $nb_pages = Post::getNbOfPagesByActive();
        $nb_pages = Post::get_nb_page_as_json($nb_pages);
        echo $nb_pages;
    }
    
    public function get_unanswered_page_service() {
        $nb_pages = Post::getNbOfPagesByUnanswered();
        $nb_pages = Post::get_nb_page_as_json($nb_pages);
        echo $nb_pages;
    }
    
    public function get_vote_page_service() {
        $nb_pages = Post::getNbOfPagesByVotes();
        $nb_pages = Post::get_nb_page_as_json($nb_pages);
        echo $nb_pages;
    }

    public function get_search_page_service() {
        $search = $_GET["param1"];
        $nb_pages = Post::getNbOfPagesBySearch($search);
        $nb_pages = Post::get_nb_page_as_json($nb_pages);
        echo $nb_pages;
    }

    public function get_tagged_page_service() {
        $tag = $_GET["param1"];
        $id = Tag::getTagIdByName($tag);
        $nb_pages = Post::getNbOfPagesByTagId($id[0]);
        $nb_pages = Post::get_nb_page_as_json($nb_pages);
        echo $nb_pages;
    }

    public function get_comments_service() {
        $postId = $_GET["param1"];
        $user = NULL;
        if ($this->user_logged()){
            $user = $this->get_user_or_redirect();
            $post = Post::getQuestionById($_GET["param1"], $user);
            $vote = new Vote($user->Id, $post->PostId, 0);
            $vote = $vote->vote($user, $post->PostId);
        }
        $post = Post::getQuestionById($_GET["param1"], $user);
        $answers = Post::getAnswersById($_GET["param1"], $user);
        $comments = Comment::getAllComments($post, $answers);
        $comments_as_json = Comment::get_comments_as_json($comments);

        echo $comments_as_json;
    }

    public function get_votes_service() {
        $postId = $_GET["param1"];
        $user = $this->user_logged();
        $user = $this->get_user_or_redirect();
        $post = Post::getQuestionById($postId, $user);
        if ($post->ParentId != null)
            $post = Post::getQuestionById($post->ParentId, $user);
        $answers = Post::getAnswersById($post->PostId, $user);
        $votes_as_json = Vote::get_votes_as_json($post, $answers, $user->Id);

        echo $votes_as_json;
    }
}
?>