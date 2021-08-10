<?php
    
    require_once 'framework/Controller.php';
    require_once 'framework/Model.php';
    require_once 'model/Post.php';

    class Comment extends Model {
        public $Id;
        public $Author;
        public $PostId;
        public $Body;
        public $Timestamp;

        public function __construct($Id, $Author, $PostId, $Body, $Timestamp)
        {
            $this->Id = $Id;
            $this->Author = $Author;
            $this->PostId = $PostId;
            $this->Body = $Body;
            $this->Timestamp = $Timestamp;
        }

        public static function getComments($postId)
        {
            $comments = [];
            $query = self::execute("SELECT * FROM comment WHERE PostId = :PostId order by Timestamp ASC", array("PostId"=>$postId));
            $data = $query->fetchAll();
            foreach($data as $a) {
                $comments[] = new Comment($a['CommentId'], Post::getAuthorById($a['UserId']), $a['PostId'], $a['Body'], Post::getTimestamp($a['Timestamp']));
            }
            return ($comments);
        }

        public function validate()
        {
            if (!$this->Body || ctype_space($this->Body))
                return (false);
            else
                return (true);
        }

        public function add($userId)
        {
            $now = new DateTime;
            $timestamp = $now->format('Y-m-d H:i:s');
            if (self::execute("INSERT INTO comment(UserId, PostId, Body, Timestamp) VALUES (:userId, :postId, :body, :timestamp)", 
                array("userId"=>$userId, "postId"=>$this->PostId, "body"=>$this->Body, "timestamp"=>$timestamp)))
                return true;
            else
                return false;
        }

        public static function getComment($id)
        {
            $query = self::execute("SELECT * FROM comment where CommentId = :id", array("id"=>$id));
            $data = $query->fetch();
            if ($query->RowCount() == 0)
                return NULL;
            else
                return (new Comment($data['CommentId'], Post::getAuthorById($data['UserId']), $data['PostId'], $data['Body'], Post::getTimestamp($data['Timestamp'])));
        }

        public function update($body)
        {
            $now = new DateTime;
            $timestamp = $now->format('Y-m-d H:i:s');
            self::execute("UPDATE comment SET Body = :body, Timestamp = :timestamp WHERE CommentId = :id", array("body"=>$body, "timestamp"=>$timestamp, "id"=>$this->Id));   
        }

        public function delete()
        {
            self::execute("DELETE FROM comment where CommentId = :id", array("id"=>$this->Id));
        }

        public static function deleteAllComments($id)
        {
            self::execute("DELETE FROM comment where Postid = :id", array("id"=>$id));
        }

        public static function getAllComments($question, $answers) {
            $comments = [];
            if (isset($question->comments[0]))
                $comments[$question->PostId] = $question->comments;
            foreach($answers as $a) {
                if (isset($a->comments[0]))
                    $comments[$a->PostId] = $a->comments;
            }
            return $comments;
        }

        public static function get_comments_as_json($comments) {
            $str = "";
            foreach($comments as $key => $value)
            {
                $PostId = json_encode($key);
                $comments = Comment::get_comment_as_json($value);
                $str .= "{\"PostId\":$PostId, \"Comments\":[$comments]},";
            }
            if($str !== "")
                $str = substr($str,0,strlen($str)-1);
            return "[$str]";
        }

        public static function get_comment_as_json($comments) {
            $str = "";
            foreach($comments as $c) {
                $Id = $c->Id;
                $Author = $c->Author;
                $PostId = $c->PostId;
                $Body = $c->Body;
                $Timestamp = $c->Timestamp;

                $Id = json_encode($Id);
                $Author = Member::get_author_as_json($Author);
                $PostId = json_encode($PostId);
                $Body = json_encode($Body);
                $Timestamp = json_encode($Timestamp);
                $str .= "{\"Id\":$Id,\"Author\":[$Author],\"PostId\":$PostId,\"Body\":$Body,\"Timestamp\":$Timestamp},";
            }
            if($str !== "")
                $str = substr($str,0,strlen($str)-1);
            return $str;
        }
    }
