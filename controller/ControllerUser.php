<?php

require_once 'framework/View.php';
require_once 'framework/Controller.php';
require_once 'model/Member.php';
require_once 'model/Post.php';
require_once 'util/Utils.php';

class ControllerUser extends Controller 
{
    public function index()
    {
        $this->redirect("Post", "index");
    }

    public function signin()
    {
        if (!$this->user_logged())
        {
            $pseudo = '';
            $password = '';
            $errors = [];
            if (isset($_POST['pseudo']) && isset($_POST['password']))
            {
                $pseudo = $_POST['pseudo'];
                $password = $_POST['password'];

                $errors = Member::validate_signin($pseudo, $password);
                if (empty($errors))
                {
                    $this->log_user(Member::get_member_by_pseudo($pseudo));
                }
            }
            (new View("signin"))->show(array("pseudo" => $pseudo, "password" => $password, "errors" => $errors));
        }
        else
        {
            (new View("error"))->show(array("error"=>"You must logout before trying to login!"));
        }
    }

    public function signup()
    {
        if (!$this->user_logged())
        {
            $pseudo = '';
            $password = '';
            $password_confirm = '';
            $email = '';
            $name = '';
            $errors = [];
            if (isset($_POST['pseudo']) && isset($_POST['password']) && isset($_POST['password_confirm']) 
            && isset($_POST['email']) && isset($_POST['name']))
            {
                $pseudo = trim($_POST['pseudo']);
                $password = $_POST['password'];
                $password_confirm = $_POST['password_confirm'];
                $email = $_POST['email'];
                $name = $_POST['name'];

                $member = new Member($pseudo, Tools::my_hash($password), $email, $name, '', "user");
                if (empty($errors = $member->validate($password_confirm))){
                    $member->update(); // enregistre le user
                    $this->log_user(Member::get_member_by_pseudo($member->pseudo));
                }
            }
            (new View("signup"))->show(array("pseudo" => $pseudo, "password" => $password, "password_confirm" => $password_confirm,
                                            "email" => $email, "name" => $name, "errors" => $errors));
        }
        else
            (new View("error"))->show(array("error"=>"You canno't signup if you're already logged in!"));
    }

    public function stats()
    {
        $period = "month";
        $nbr = 3;
        $user = null;
        if ($this->user_logged())
            $user = $this->get_user_or_redirect();
        $nbr_stats = Configuration::get("nbr_user_stats");
        if ($nbr_stats > 0)
            $stats_json = Post::get_stats_as_json($nbr_stats, $nbr, $period);
        (new View("stats"))->show(array("user" => $user, "nbr_stats"=>$nbr_stats, "stats_json"=>$stats_json));
    }

    public function get_stats_service()
    {
        $user = null;
        if ($this->user_logged())
            $user = $this->get_user_or_redirect();
        $nbr_stats = Configuration::get("nbr_user_stats");
        if (!(isset($_GET['param1']) && isset($_GET['param2'])) || $nbr_stats <= 0)
            (new View("error"))->show(array("error"=>"We found a problem with the parameters !", "user"=>$user));
        else
        {
            $period = $_GET['param1'];
            $nbr = (int)$_GET['param2'];
            $stats_json = Post::get_stats_as_json($nbr_stats, $nbr, $period);
            echo $stats_json;
        }
    }

    public function pseudo_exists_service() 
    {
        $res = "false";
        $pseudo = $_POST["pseudo"];
        if (Member::get_member_by_pseudo($pseudo))
            $res = "true";
        echo $res;
    }

    public function password_is_correct_service()
    {
        $res = "false";
        $pseudo = $_POST["pseudo"];
        $password = $_POST["password"];
        $errors = Member::validate_signin($pseudo, $password);
        if (empty($errors))
            $res = "true";
        echo $res;
    }

    public function pseudo_already_exists_service() 
    {
        $res = "true";
        $pseudo = $_POST["pseudo"];
        if (Member::get_member_by_pseudo($pseudo))
            $res = "false";
        echo $res;
    }

    public function password_is_valid_service()
    {
        $res = "true";
        $password = $_POST["password"];
        if (!Member::password_valid($password))
            $res = "false";
        echo $res;
    }

    public function passwords_correspond_service()
    {
        $res = "true";
        $password = $_POST["password"];
        $password_confirm = $_POST["password_confirm"];
        if (strcmp($password, $password_confirm) != 0)
            $res = "false";
        echo $res;
    }

    public function mail_correct_service()
    {
        $res = "true";
        $mail = $_POST["email"];
        if (!Member::email_valid($mail))
            $res = "false";
        if (Member::email_exists($mail))
            $res = "false";
        echo $res;
    }
}
?>