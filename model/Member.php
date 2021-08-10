<?php

require_once 'framework/Model.php';

class Member extends Model
{
    public $pseudo;
    public $hashed_password;
    public $email;
    public $name;
    public $Id;
    public $Role;

    public function __construct($pseudo, $hashed_password, $email, $name, $Id, $Role)
    {
        $this->pseudo = $pseudo;
        $this->hashed_password = $hashed_password;
        $this->email = $email;
        $this->name = $name;
        $this->Id = $Id;
        $this->Role = $Role;
    }

    public static function validate_signin($pseudo, $password)
    {
        $errors = [];
        $member = Member::get_member_by_pseudo($pseudo);
        if ($member)
        {
            if(!$member->check_password($password)){
                $errors[] = "Bad password for this pseudo";
            }
        }
        else
        {
            $errors[] = "User doesn't exist";
        }
        return ($errors);
    }

    public function validate($password_confirm)
    {
        $errors = [];
        $pattern = '/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>,\.\?\\\]/'; // tous les charactères spéciaux pr preg_match
        if(Member::get_member_by_pseudo($this->pseudo)){
            $errors[] = "Username is already taken";
        }
        if(strlen($this->pseudo) < 3){
            $errors[] = "Username must have at least 3 characters";
        }
        if(strlen($this->name) < 3){
            $errors[] = "Name must have at least 3 characters";
        }
        if(!($this->check_password($password_confirm))){
            $errors[] = "Passwords are not the same";
        }
        if(strlen($password_confirm) <= 8 || strtolower($password_confirm) == $password_confirm ||
            !preg_match('~[0-9]+~', $password_confirm) || !preg_match($pattern, $password_confirm)){
            $errors[] = "Password must have at least 8 characters, 1 uppercase letter, 1 number and 1 non alphanumeric character";
        }
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)){
            $errors[] = "Not a valid email";
        }
        if (Member::email_exists($this->email))
            $errors[] = "Email is already taken";
        return ($errors);
    }

    public static function password_valid($password) {
        $pattern = '/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>,\.\?\\\]/'; // tous les charactères spéciaux pr preg_match
        if(strlen($password) <= 8 || strtolower($password) == $password ||
            !preg_match('~[0-9]+~', $password) || !preg_match($pattern, $password))
            return false;
        return true;
    }

    public static function email_valid($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return false;
        return true;
    }

    public static function get_member_by_pseudo($pseudo)
    {
        $query = self::execute("SELECT * FROM user where UserName = :pseudo", array("pseudo"=>$pseudo));
        $data = $query->fetch();
        if ($query->rowCount() == 0)
            return (false);
        else
            return new Member($data["UserName"], $data["Password"], '', '', $data["UserId"], $data["Role"]);
    }

    public static function email_exists($email)
    {
        $query = self::execute("SELECT * FROM user where Email = :Email", array("Email"=>$email));
        $data = $query->fetch();
        if ($query->rowCount() == 0)
            return (false);
        else
            return (true);
    }

    public function check_password($password)
    {
        if($this->hashed_password == Tools::my_hash($password))
            return (true);
        return (false);
    }

    public function update()
    {
        self::execute("INSERT INTO user (UserName, Password, Email, FullName, Role) VALUES (:pseudo, :hashed_password, :email, :name, 'user')",
                        array("pseudo"=>$this->pseudo, "hashed_password"=>$this->hashed_password, "email"=>$this->email, "name"=>$this->name));
        return (true);
    }

    public static function get_author_as_json($Author) {
        $str = "";
        $pseudo = $Author["UserName"];
        $name = $Author["FullName"];
        $Id = $Author["UserId"];
        $Role = $Author["Role"];

        $pseudo = json_encode($pseudo);
        $name = json_encode($name);
        $Id = json_encode($Id);
        $Role = json_encode($Role);

        $str = "{\"pseudo\":$pseudo,\"name\":$name,\"Id\":$Id,\"Role\":$Role}";
        return $str;
    }
}
?>