<?php

require_once 'framework/Controller.php';
require_once 'model/Member.php';
require_once 'model/Tag.php';
require_once 'model/Post.php';
require_once 'util/Utils.php';

class ControllerTag extends Controller
{
    public function index()
    {
        $user = NULL;
        if ($this->user_logged())
            $user = $this->get_user_or_redirect();
        $tags = Tag::getAllTags();

        (new View("tag"))->show(array("tags"=>$tags, "user"=>$user));
    }

    public function access()
    {
        $user = NULL;
        if ($this->user_logged())
            $user = $this->get_user_or_redirect();
        if (!$this->user_logged()) {
            (new View("error"))->show(array("error"=>"You must log in to edit a tag."));
            return (false);
        }
        else if (strcmp($user->Role, "admin") != 0)
        {
            (new View("error"))->show(array("error"=>"You don't have the right for this."));
            return (false);
        }
        else
            return (true);
    }

    public function edit()
    {
        if (ControllerTag::access())
        {
            $tag = new Tag($_GET["param1"], $_POST["tagName"], -1);
            if (!(isset($_POST["tagName"]) && $tag->validate()))
                (new View("error"))->show(array("error"=>"This is not a valid tag name."));
            else if (!(isset($_GET["param1"])))
                (new View("error"))->show(array("error"=>"You must enter a tag ID to edit."));
            else if (!$tag->update())
                (new View("error"))->show(array("error"=>"Incorrect name/ID."));
            else
                $this->redirect("tag", "index");
        }
    }

    public function delete()
    {
        if (ControllerTag::access())
        {
            $tag = new Tag($_GET["param1"], null, -1);
            if (!(isset($_GET["param1"])))
                (new View("error"))->show(array("error"=>"You must enter a tag ID to edit."));
            else if (!$tag->delete())
                (new View("error"))->show(array("error"=>"Incorrect ID."));
            else
                $this->redirect("tag", "index");
        }
    }

    public function confirm_delete()
    {
        $user = $this->get_user_or_redirect();
        if (strcmp($user->Role, "admin") != 0)
            (new View("error"))->show(array("error"=>"You don't have access to this fonctionnality."));
        else
            (new View("delete"))->show(array("user"=>$user, "tagId"=>$_GET['param1']));
    }

    public function add()
    {
        if (ControllerTag::access())
        {
            $tag = new Tag(-1, $_POST["tagName"], -1);
            if (!(isset($_POST["tagName"]) && $tag->validate()))
                (new View("error"))->show(array("error"=>"This is not a valid tag name."));
            else {
                if (!$tag->add())
                    (new View("error"))->show(array("error"=>"This tag already exists."));
                else
                    $this->redirect("tag", "index");
            }
        }   
    }

    public function remove_tag()
    {
        $user = NULL;
        if (!$this->user_logged())
            (new View("error"))->show(array("error"=>"You must be connected to remove a tag from a post"));
        else {
            $user = $this->get_user_or_redirect();
            if (!(isset($_GET['param1']) && isset($_GET['param2'])))
                (new View("error"))->show(array("error"=>"You must enter 2 parameters."));
            else {
                $post = Post::getQuestionById($_GET['param1'], $user);
                if (!($post->Author['UserId'] == $user->Id || strcmp("admin", $user->Role) == 0))
                    (new View("error"))->show(array("error"=>"You don't have the rights for this."));
                else { 
                    $tag = Tag::getTagById($_GET['param2']);
                    if (!isset($tag))
                        (new View("error"))->show(array("error"=>"This tag doesn't exist."));
                    else {
                        if (!Tag::remove_posttag($tag->Id, $post->PostId))
                            (new View("error"))->show(array("error"=>"There is no such tag for this post"));
                        else
                            $this->redirect("post", "show", "$post->PostId");
                    }
                }
            } 
        }
    }

    public function add_to_post()
    {
        $user = NULL;
        if (!$this->user_logged())
            (new View("error"))->show(array("error"=>"You must be connected to remove a tag from a post"));
        else {
            $user = $this->get_user_or_redirect();
            if (!(isset($_GET['param1']) && isset($_POST['tag'])))
                (new View("error"))->show(array("error"=>"You must enter 2 parameters."));
            else {
                $post = Post::getQuestionById($_GET['param1'], $user);
                if (!($post->Author['UserId'] == $user->Id || strcmp("admin", $user->Role) == 0))
                    (new View("error"))->show(array("error"=>"You don't have the rights for this."));
                else { 
                    $tag = Tag::getTagById($_POST['tag']);
                    if (!isset($tag))
                        (new View("error"))->show(array("error"=>"This tag doesn't exist."));
                    else {
                        if (!$tag->add_posttag($post->PostId))
                        {
                            $max_tag = Configuration::get("max_tags");
                            (new View("error"))->show(array("error"=>"Sorry, the maximum number of tags is $max_tag and there already is $max_tag tags or more for this question."));
                        }
                        else
                            $this->redirect("post", "show", "$post->PostId");
                    }
                }
            }
        }
    }

    public function is_valid_service()
    {
        $res = "true";
        $name = $_POST["name"];
        $data = Tag::getTagIdByName($name);
        if (isset($data[0]))
            $res = "false";
        if (!Post::check_ask($name))
            $res = "false";
        echo $res;
    }
}
?>