<?php
    
    require_once 'framework/Controller.php';
    require_once 'model/Member.php';
    require_once 'model/Vote.php';
    require_once 'model/Tag.php';
    require_once 'model/Comment.php';
    require_once "lib/parsedown-1.7.3/Parsedown.php";

    class Post extends Model{
        public $PostId;
        public $Author;
        public $Title;
        public $Body;
        public $Timestamp;
        public $AcceptedAnswerId;
        public $ParentId;
        public $NbAnswer;
        public $SumVote;
        public $NbVotes;
        public $vote;
        public $tags;
        public $comments;

        public function __construct($PostId, $Author, $Title, $Body, $Timestamp, $AcceptedAnswerId, 
                $ParentId, $NbAnswer, $SumVote, $NbVotes, $vote, $tags, $comments){
            $this->PostId = $PostId;
            $this->Author = $Author;
            $this->Title = $Title;
            $this->Body = $Body;
            $this->Timestamp = $Timestamp;
            $this->AcceptedAnswerId = $AcceptedAnswerId;
            $this->ParentId = $ParentId;
            $this->NbAnswer = $NbAnswer;
            $this->SumVote = $SumVote;
            $this->NbVotes = $NbVotes;
            $this->vote = $vote;
            $this->tags = $tags;
            $this->comments = $comments;
        }

        public function validate_ask($selectedtags) {
            $errors = [];
            if (!self::check_ask($this->Title))
                $errors[] = "Title not valid";
            if (!self::check_ask($this->Body))
                $errors[] = "Body not valid";
            $max_tags = Configuration::get("max_tag");
            if (isset($selectedtags) && !Tag::check_ask($selectedtags, $max_tags))
                $errors[] = "You can select maximum " + $max_tags + " tags.";
            return ($errors);
        }

        public function validate_body() {
            $errors = [];
            if (!self::check_ask($this->Body))
                $errors[] = "Body not valid";
            return ($errors);
        }

        public function validate_reply() {
            $errors = [];
            if (!self::check_ask($this->Body))
                $errors[] = "Answer must be filled";
            return ($errors);
        }

        public static function check_ask($string) {
            if (!$string || ctype_space($string))
                return false;
            else
                return true;
        }

        public static function getAnswersById($PostId, $user){
            $answers = [];
            $otherAnswers = [];
            $query = self::execute("SELECT AcceptedAnswerId FROM Post where PostId = :PostId", array("PostId"=> $PostId));
            $data = $query->fetch();
            if (isset($data[0]))
                $answers[] = Post::getQuestionById($data[0], $user);
            $query = self::execute("SELECT * FROM Post where ParentId = :PostId order by Timestamp DESC", array("PostId" => $PostId));
            $data = $query->fetchAll();
            foreach ($data as $a){
                if (isset($user))
                    $vote = new Vote($user->Id, $a['PostId'], 0);
                else
                    $vote = new Vote(-1, $a['PostId'], 0);
                $otherAnswers[] = new Post($a['PostId'], Post::getAuthorById($a['AuthorId']), NULL, $a['Body'], 
                    Post::getTimestamp($a['Timestamp']), $a['AcceptedAnswerId'], NULL, NULL, $vote->getVotesToShow(), 
                    $vote->getSumOfVotes(), Vote::vote($user, $a['PostId']), Tag::getTagByQuestionId($a['PostId']), Comment::getComments($a['PostId']));
            }
            $otherAnswers = Post::sortAnswers($otherAnswers);
            $answers = array_merge($answers, $otherAnswers);
            $answers = array_unique($answers);
            return ($answers);
        }

        private static function sortAnswers($answers){
            usort($answers, array("Post", "compareVotes"));
            return ($answers);
        }

        private static function compareVotes($a, $b){
            return (($b->NbVotes[0]) - ($a->NbVotes[0]));
        }
        
        public static function getQuestionById($PostId, $user){
            $query = self::execute("SELECT * FROM Post where PostId = :PostId", array("PostId" => $PostId));
            $data = $query->fetch();
            $vote = new Vote(-1, $data['PostId'], 0);
            return(new Post($data['PostId'], Post::getAuthorById($data['AuthorId']), $data['Title'],  
                $data['Body'], Post::getTimestamp($data['Timestamp']),  $data['AcceptedAnswerId'],  $data['ParentId'], 
                Post::getNumberOfAnswer($data['PostId']), $vote->getVotesToShow(), $vote->getSumOfVotes(), 
                Vote::vote($user, $data['PostId']), Tag::getTagByQuestionId($data['PostId']), Comment::getComments($data['PostId'])));
        }

        public static function getAuthorById($AuthorId){
            $query1 = self::execute("SELECT * FROM user where UserId = :AuthorId", array("AuthorId" => $AuthorId));
            return ($query1->fetch());
        }

        public static function getNumberOfAnswer($PostId){
            $countA = self::execute("SELECT count(*) FROM post where ParentId = :Postid", array("Postid" => $PostId));
            return ($countA->fetch());
        }

        public static function getAllQuestionsSortedByNewest($page){
            $pagination_nb = Configuration::get("pagination_nb");
            $limit_inf = ($page * $pagination_nb) - $pagination_nb;
            $query = self::execute("SELECT * FROM post where ParentId IS NULL ORDER BY Timestamp DESC LIMIT $limit_inf, $pagination_nb", array());
            $data = $query->fetchAll();
            $questions = [];
            foreach ($data as $q){
                $vote = new Vote(-1, $q['PostId'], 0);
                $questions[] = new Post($q['PostId'], Post::getAuthorById($q['AuthorId']), $q['Title'],$q['Body'], 
                    Post::getTimestamp($q['Timestamp']), $q['AcceptedAnswerId'], $q['ParentId'], Post::getNumberOfAnswer($q['PostId']), 
                    $vote->getVotesToShow(), $vote->getSumOfVotes(), NULL, Tag::getTagByQuestionId($q['PostId']), Comment::getComments($q['PostId']));
            }
            return ($questions);
        }

        public static function getNbOfPagesByNewest(){
            $pagination_nb = Configuration::get("pagination_nb");
            $query = self::execute("SELECT * FROM post where ParentId IS NULL ORDER BY Timestamp DESC", array());
            $res = $query->rowCount() / $pagination_nb;
            if ($query->rowCount() % $pagination_nb != 0)
                $res += 1;
            return ($res);
        }

        public static function getAllQuestionsSortedByActive($page){
            $pagination_nb = Configuration::get("pagination_nb");
            $limit_inf = ($page * $pagination_nb) - $pagination_nb;
            $query = self::execute("select question.PostId, question.AuthorId, question.Title, question.Body, question.ParentId, question.Timestamp, question.AcceptedAnswerId 
            from post as question, 
                 (select post_updates.postId, max(post_updates.timestamp) as timestamp from (
                    select q.postId as postId, q.timestamp from post q where q.parentId is null
                    UNION
                    select a.parentId as postId, a.timestamp from post a where a.parentId is not null
                    UNION
                    select c.postId as postId, c.timestamp from comment c 
                    UNION 
                    select a.parentId as postId, c.timestamp 
                    from post a, comment c 
                    WHERE c.postId = a.postId and a.parentId is not null
                    ) as post_updates
                  group by post_updates.postId) as last_post_update
            where question.postId = last_post_update.postId and question.parentId is null
            order by last_post_update.timestamp DESC LIMIT $limit_inf, $pagination_nb", array());
            $data = $query->fetchAll();
            $questions = [];
            foreach ($data as $q)
            {
                $vote = new Vote(-1, $q['PostId'], 0);
                $questions[] = new Post($q['PostId'], Post::getAuthorById($q['AuthorId']), $q['Title'], $q['Body'], 
                    Post::getTimestamp($q['Timestamp']), $q['AcceptedAnswerId'], $q['ParentId'], Post::getNumberOfAnswer($q['PostId']), 
                    $vote->getVotesToShow(), $vote->getSumOfVotes(), NULL, Tag::getTagByQuestionId($q['PostId']), Comment::getComments($q['PostId']));
            }
            return ($questions);
        }

        public static function getNbOfPagesByActive(){
            $pagination_nb = Configuration::get("pagination_nb");
            $query = self::execute("select question.PostId, question.AuthorId, question.Title, question.Body, question.ParentId, question.Timestamp, question.AcceptedAnswerId 
            from post as question, 
                 (select post_updates.postId, max(post_updates.timestamp) as timestamp from (
                    select q.postId as postId, q.timestamp from post q where q.parentId is null
                    UNION
                    select a.parentId as postId, a.timestamp from post a where a.parentId is not null
                    UNION
                    select c.postId as postId, c.timestamp from comment c 
                    UNION 
                    select a.parentId as postId, c.timestamp 
                    from post a, comment c 
                    WHERE c.postId = a.postId and a.parentId is not null
                    ) as post_updates
                  group by post_updates.postId) as last_post_update
            where question.postId = last_post_update.postId and question.parentId is null
            order by last_post_update.timestamp DESC", array());
            $res = $query->rowCount() / $pagination_nb;
            if ($query->rowCount() % $pagination_nb != 0)
                $res += 1;
            return ($res);
        }

        public static function getAllQuestionsSortedByUnanswered($page){
            $pagination_nb = Configuration::get("pagination_nb");
            $limit_inf = ($page * $pagination_nb) - $pagination_nb;
            $query = self::execute("SELECT * FROM post where ParentId IS NULL and AcceptedAnswerId IS NULL 
                ORDER BY Timestamp DESC LIMIT $limit_inf, $pagination_nb", array());
            $data = $query->fetchAll();
            $questions = [];
            foreach ($data as $q){
                $vote = new Vote(-1, $q['PostId'] ,0);
                $questions[] = new Post($q['PostId'], Post::getAuthorById($q['AuthorId']), $q['Title'],$q['Body'], 
                    Post::getTimestamp($q['Timestamp']), $q['AcceptedAnswerId'], $q['ParentId'], Post::getNumberOfAnswer($q['PostId']), 
                    $vote->getVotesToShow(), $vote->getSumOfVotes(), NULL, Tag::getTagByQuestionId($q['PostId']), Comment::getComments($q['PostId']));
            }
            return ($questions);
        }
        
        public static function getNbOfPagesByUnanswered(){
            $pagination_nb = Configuration::get("pagination_nb");
            $query = self::execute("SELECT * FROM post where ParentId IS NULL and AcceptedAnswerId IS NULL 
            ORDER BY Timestamp DESC", array());
            $res = $query->rowCount() / $pagination_nb;
            if ($query->rowCount() % $pagination_nb != 0)
                $res += 1;
            return ($res);
        }

        public static function getAllQuestionsSortedByVotes($page){
            $pagination_nb = Configuration::get("pagination_nb");
            $limit_inf = ($page * $pagination_nb) - $pagination_nb;
            $query = self::execute("SELECT post.*, max_score
                FROM post, (
                    SELECT parentid, max(score) max_score
                    FROM (
                        SELECT post.postid, ifnull(post.parentid, post.postid) parentid, ifnull(sum(vote.updown), 0) score
                        FROM post LEFT JOIN vote ON vote.postid = post.postid
                        GROUP BY post.postid
                    ) AS tbl1
                    GROUP by parentid
                ) AS q1
                WHERE post.postid = q1.parentid
                ORDER BY q1.max_score DESC, timestamp DESC LIMIT $limit_inf, $pagination_nb", array());
            $data = $query->fetchAll();
            $questions = [];
            foreach ($data as $q){
                $vote = new Vote(-1, $q['PostId'], 0);
                $questions[] = new Post($q['PostId'], Post::getAuthorById($q['AuthorId']), $q['Title'], $q['Body'], 
                    Post::getTimestamp($q['Timestamp']), $q['AcceptedAnswerId'], $q['ParentId'], Post::getNumberOfAnswer($q['PostId']), 
                    $vote->getVotesToShow(), $vote->getSumOfVotes(), NULL, Tag::getTagByQuestionId($q['PostId']), Comment::getComments($q['PostId']));
            }
            return ($questions);
        }
        
        public static function getNbOfPagesByVotes(){
            $pagination_nb = Configuration::get("pagination_nb");
            $query = self::execute("SELECT post.*, max_score
            FROM post, (
                SELECT parentid, max(score) max_score
                FROM (
                    SELECT post.postid, ifnull(post.parentid, post.postid) parentid, ifnull(sum(vote.updown), 0) score
                    FROM post LEFT JOIN vote ON vote.postid = post.postid
                    GROUP BY post.postid
                ) AS tbl1
                GROUP by parentid
            ) AS q1
            WHERE post.postid = q1.parentid
            ORDER BY q1.max_score DESC, timestamp DESC", array());
            $res = $query->rowCount() / $pagination_nb;
            if ($query->rowCount() % $pagination_nb != 0)
                $res += 1;
            return ($res);
        }

        public function reply($user){
            $now = new DateTime;
            $timestamp = $now->format('Y-m-d H:i:s');
            self::execute("INSERT INTO Post(AuthorId, Body, Timestamp, AcceptedAnswerId, ParentId) values (:AuthorId, :Body, :Timestamp, NULL, :ParentId)", 
                array("AuthorId"=>$user->Id, 
                "Body"=>$this->Body, "Timestamp"=>$timestamp,
                "ParentId"=>$this->PostId));
        }

        public function ask($user, $selectedtags){
            $now = new DateTime;
            $timestamp = $now->format('Y-m-d H:i:s');
            self::execute("INSERT INTO Post(AuthorId, Title, Body, Timestamp, AcceptedAnswerId, ParentId) VALUES (:AuthorId, :Title, :Body, :Timestamp, NULL, NULL)",
                array("AuthorId"=>$user->Id, "Title"=>$this->Title, "Body"=>$this->Body, "Timestamp"=>$timestamp));
            $postId = self::lastInsertId();

            if (isset($selectedtags))
            {
                foreach($selectedtags as $tag)
                {
                    $tagId = Tag::getTagIdByName($tag);
                    self::execute("INSERT INTO posttag VALUES (:PostId, :TagId)", array("PostId"=>$postId, "TagId"=>$tagId[0]));
                }
            }
            return ($postId);    
        }

        public function delete($user){
            if ($this->NbAnswer[0] != 0)
            {
                $answers = Post::getAnswersById($this->PostId, $user);
                foreach($answers as $a)
                    $a->delete($user);
            }
            $vote = new Vote(-1, $this->PostId, 0);
            if ($vote->getNumberOfVotes() != 0)
                $vote->deleteAllVotes();
            if (isset($this->comments)) {
                Comment::deleteAllComments($this->PostId);
            }
            if ($this->ParentId != NULL) {
                $parent = Post::getQuestionById($this->ParentId, NULL);
                if ($parent->AcceptedAnswerId == $this->PostId)
                    $this->deleteAcceptedAnswer();
            }
            self::execute("DELETE FROM Post where PostId = :PostId", array("PostId" => $this->PostId));
        }

        public function update($newBody, $user, $newtitle){
            $now = new DateTime;
            $timestamp = $now->format('Y-m-d H:i:s');
            if (!isset($newtitle))
                self::execute("UPDATE Post set Body = :newBody, Timestamp = :Timestamp where PostId = :PostId", 
                    array("newBody"=>$newBody, "PostId"=>$this->PostId, "Timestamp"=>$timestamp));
            else
                self::execute("UPDATE Post set Body = :newBody, Title = :newTitle, Timestamp = :Timestamp where PostId = 
                    :PostId", array("newBody"=>$newBody, "PostId"=>$this->PostId, "Timestamp"=>$timestamp, "newTitle"=>$newtitle));
            if ($this->ParentId != NULL)
                return (Post::getQuestionById($this->ParentId, $user));
            else
                return (Post::getQuestionById($this->PostId, $user));
        }

        public function acceptAnswer($PostId){
            self::execute("UPDATE Post SET AcceptedAnswerId = :PostId where PostId = :ParentId", array("PostId"=>$PostId, "ParentId"=>$this->ParentId));
        }

        public function deleteAcceptedAnswer(){
            self::execute("UPDATE Post SET AcceptedAnswerId = NULL where PostId = :ParentId", array("ParentId"=>$this->ParentId));
        }

        public static function getSearchedQuestion($search, $user, $page){
            $pagination_nb = Configuration::get("pagination_nb");
            $limit_inf = ($page * $pagination_nb) - $pagination_nb;
            $query = self::execute("SELECT DISTINCT question.*
            FROM post as question
            LEFT OUTER JOIN post AS answer ON answer.parentId = question.postId  
            LEFT OUTER JOIN user AS answerer ON answer.authorId = answerer.userId 
            LEFT OUTER JOIN user AS questioner ON question.authorId = questioner.UserId 
            WHERE question.parentId IS NULL  
            AND (
            question.title LIKE :Body OR
                question.body LIKE :Body OR
                answer.body LIKE :Body OR
                answerer.UserName LIKE :Body OR
                questioner.UserName LIKE :Body
                )
            ORDER BY question.timestamp DESC
            LIMIT $limit_inf, $pagination_nb", array("Body"=>"%$search%"));
            $data = $query->fetchAll();
            $questions = [];
            foreach ($data as $q){
                $vote = new Vote(-1, $q['PostId'], 0);
                $post = new Post($q['PostId'], Post::getAuthorById($q['AuthorId']), $q['Title'], $q['Body'], 
                    Post::getTimestamp($q['Timestamp']), $q['AcceptedAnswerId'], $q['ParentId'], Post::getNumberOfAnswer($q['PostId']), 
                    $vote->getVotesToShow(), $vote->getSumOfVotes(), NULL, Tag::getTagByQuestionId($q['PostId']), Comment::getComments($q['PostId']));
                if (isset($post->ParentId))
                    $post = Post::getQuestionById($post->ParentId, $user);
                $questions[] = $post;
            }
            return ($questions);
        }

        public static function getNbOfPagesBySearch($search)
        {
            $pagination_nb = Configuration::get("pagination_nb");
            $query = self::execute("SELECT DISTINCT question.*
            FROM post as question
            LEFT OUTER JOIN post AS answer ON answer.parentId = question.postId  
            LEFT OUTER JOIN user AS answerer ON answer.authorId = answerer.userId 
            LEFT OUTER JOIN user AS questioner ON question.authorId = questioner.UserId 
            WHERE question.parentId IS NULL  
            AND (
            question.title LIKE :Body OR
                question.body LIKE :Body OR
                answer.body LIKE :Body OR
                answerer.UserName LIKE :Body OR
                questioner.UserName LIKE :Body
                )
            ORDER BY question.timestamp DESC", array("Body"=>"%$search%"));
            $res = $query->rowCount() / $pagination_nb;
            if ($query->rowCount() % $pagination_nb != 0)
                $res += 1;
            return ($res);
        }

        public static function getPostByTagId($page, $id, $user)
        {
            $questions = [];
            $pagination_nb = Configuration::get("pagination_nb");
            $limit_inf = ($page * $pagination_nb) - $pagination_nb;
            $query = self::execute("SELECT * FROM posttag, post WHERE post.PostId = posttag.PostId AND TagId = :id ORDER BY Timestamp DESC LIMIT $limit_inf, $pagination_nb",
                array("id"=>$id));
            if ($query->rowCount() == 0)
                return (NULL);
            else {
                $data = $query->fetchAll();
                foreach($data as $a){
                    $questions[] = Post::getQuestionById($a['PostId'], $user);
                }
                return ($questions);
            }
        }

        public static function getNbOfPagesByTagId($id)
        {
            $pagination_nb = Configuration::get("pagination_nb");
            $query = self::execute("SELECT * FROM posttag WHERE TagId = :id", array("id"=>$id));
            $res = $query->rowCount() / $pagination_nb;
            if ($query->rowCount() % $pagination_nb != 0)
                $res += 1;
            return ($res);
        }

        public function __toString(){
            return ("$this->PostId");
        }

        public static function getTimestamp($postTime){
            $d = new DateTime;
            $now = strtotime($d->format('Y-m-d H:i:s'));
            $old = strtotime($postTime);

            $diff = $now - $old;
            if ((int)$diff == 0)
                return ("just now");
            else if ($diff < 60){
                if ((int)$diff == 1)
                    return ((int)$diff." second ago");
                else
                    return ((int)$diff. " seconds ago");
            }
            else if (($res = $diff / 60) < 60){
                if ((int)$res == 1)
                    return ((int)$res." minute ago");
                else
                    return ((int)$res." minutes ago");
            }
            else if (($res = $diff / 3600) < 24){
                if ((int)$res == 1)
                    return ((int)$res." hour ago");
                else
                    return ((int)$res." hours ago");
            }
            else if (($res = $diff / 86400) < 7){
                if ((int)$res == 1)
                    return ((int)$res." day ago");
                else
                    return ((int)$res." days ago");
            }
            else if (($res = $diff / 604800) < 4.3){
                if ((int)$res == 1)
                    return ((int)$res." week ago");
                else
                    return ((int)$res." weeks ago");
            }
            else if (($res = $diff / 2600640) < 12){
                if ((int)$res == 1)
                    return ((int)$res." month ago");
                else
                    return ((int)$res." months ago");
            }
            else if (($res = $diff / 31207680) < 1){
                if ((int)$res == 1)
                    return ((int)$res." year ago");
                else
                    return ((int)$res." years ago");
            }
        }

        private static function getDateDifference($nbr, $period)
        {
            $d = new Datetime;
            $res = strtotime($d->format("Y-m-d H:i:s")."-".strval($nbr)." ".$period);
            $res = date("Y-m-d H:i:s", $res);
            return ($res);
        }

        public static function get_stats_as_json($nbr_stats, $nbr, $period)
        {
            $time = Post::getDateDifference($nbr, $period);
            $query = self::execute("SELECT  *,
                postactions + commentactions AS totalactions
                FROM    (
                SELECT  user.*,
                    (SELECT count(*) FROM post WHERE post.AuthorId = user.UserId and post.Timestamp > :time) as postactions,
                    (SELECT count(*) FROM comment WHERE comment.UserId = user.UserId and comment.Timestamp > :time) as commentactions
                FROM user
                ) q 
                ORDER BY totalactions DESC
                LIMIT 0, $nbr_stats", array("time"=>$time));
            $data = $query->fetchAll();
            $str = "";
            foreach ($data as $d)
            {
                $userId = $d['UserId'];
                $username = $d['UserName'];
                $totalactions = $d['totalactions'];
                $userId = json_encode($userId);
                $username = json_encode($username);
                $totalactions = json_encode($totalactions);
                $str .= "{\"userid\":$userId,\"username\":$username,\"totalactions\":$totalactions},"; 
            }
            if($str !== "")
                $str = substr($str,0,strlen($str)-1);
            return "[$str]";
        }

        public static function get_details_as_json($period, $nbr, $userId, $user){
            $time = Post::getDateDifference($nbr, $period);
            $query = self::execute("SELECT postId, true, Timestamp FROM post WHERE post.AuthorId = :userId and post.Timestamp > :time
                UNION 
                SELECT commentId, false, Timestamp FROM comment WHERE comment.UserId = :userId and comment.Timestamp > :time
                ORDER BY Timestamp DESC", array("time"=>$time, "userId"=>$userId));
            $data = $query->fetchAll();
            $str = "";
            foreach($data as $d)
            {
                if ($d["TRUE"] == 1)
                {
                    $post = Post::getQuestionById($d["postId"], $user);
                    if ($post->ParentId != NULL)
                        $post = Post::getQuestionById($post->ParentId, $user);
                    $timestamp = $d["Timestamp"];
                    $timestamp = json_encode($timestamp);
                    $type = json_encode("post");
                    $title = json_encode($post->Title);
                    $postId = json_encode($d["postId"]);
                    $str .= "{\"timestamp\":$timestamp,\"type\":$type,\"title\":$title,\"postId\":$postId},";
                }
                else
                {
                    $comment = Comment::getComment($d["postId"]);
                    $post = Post::getQuestionById($comment->PostId, $user);
                    if ($post->ParentId != NULL)
                        $post = Post::getQuestionById($post->ParentId, $user);
                    $timestamp = $d["Timestamp"];
                    $title = $post->Title;
                    $timestamp = json_encode($timestamp);
                    $type = json_encode("comment");
                    $title = json_encode($title);
                    $postId = json_encode($post->PostId);
                    $str .= "{\"timestamp\":$timestamp,\"type\":$type,\"title\":$title, \"postId\":$postId},";
                }
            }
            if($str !== "")
                $str = substr($str,0,strlen($str)-1);
            return "[$str]";
        }

        public static function get_questions_as_json($questions) {
            $str = "";
            foreach($questions as $q)
            {
                $str .= Post::get_question_as_json($q);
            }
            if($str !== "")
                $str = substr($str,0,strlen($str)-1);
            return "[$str]";
        }

        public static function get_question_as_json($q) {
            $str = "";
            $PostId = $q->PostId;
            $Author = $q->Author["UserName"];
            $Title = $q->Title;
            $Body = $q->Body;
            $Timestamp = $q->Timestamp;
            $AcceptedAnswerId = $q->AcceptedAnswerId;
            $ParentId = $q->ParentId;
            $NbAnswer = $q->NbAnswer[0];
            $SumVote = $q->SumVote[0];
            $NbVotes = $q->NbVotes[0];
            $vote = $q->vote;
            $tags = $q->tags;
            $comments = $q->comments;

            $PostId = json_encode($PostId);
            $Author = json_encode($Author);
            $Title = json_encode($Title);
            $Body = json_encode($Body);
            $Timestamp = json_encode($Timestamp);
            $AcceptedAnswerId = json_encode($AcceptedAnswerId);
            $ParentId = json_encode($ParentId);
            $NbAnswer = json_encode($NbAnswer);
            $SumVote = json_encode($SumVote);
            $NbVotes = json_encode($NbVotes);
            $vote = json_encode($vote);
            $tags = Tag::get_tag_as_json($tags);
            $comments = json_encode($comments);

            return ("{\"PostId\":$PostId,\"Author\":$Author,\"Title\":$Title,\"Body\":$Body,\"Timestamp\":$Timestamp,\"AcceptedAnswerId\":$AcceptedAnswerId,
                \"ParentId\":$ParentId,\"NbAnswer\":$NbAnswer,\"SumVote\":$SumVote,\"NbVotes\":$NbVotes,\"vote\":$vote,\"tags\":[$tags],\"comments\":$comments},");
        }
        
        public static function get_nb_page_as_json($nb_pages) {
            $nb_pages = json_encode($nb_pages);
            $str = "[{Id:" + $nb_pages + "}]";
            return $str;
        }
    }
?>
