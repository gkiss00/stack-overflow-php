<?php
    
    require_once 'framework/Controller.php';
    require_once 'model/Member.php';

    Class Vote extends Model{
        public $UserId;
        public $PostId;
        public $UpDown;

        public function __construct($UserId, $PostId, $UpDown){
            $this->UserId = $UserId;
            $this->PostId = $PostId;
            $this->UpDown = $UpDown;
        }

        public function getSumOfVotes(){
            $query = self::execute("SELECT sum(UpDown) from vote where PostId = :PostId group by PostId", array("PostId" => $this->PostId));
            return ($query->fetch());
        }

        public function getNumberOfVotes(){
            $query = self::execute("SELECT count(*) from vote where PostId = :PostId", array("PostId"=> $this->PostId));
            return ($query->fetch());
        }

        public function getVotesToShow(){
            $query = self::execute("SELECT IF( EXISTS(SELECT AcceptedAnswerId FROM Post WHERE PostId = :PostId),
                    (SELECT sum(UpDown) FROM Vote WHERE PostId = :PostId group by PostId),
                    (SELECT sum(UpDown) FROM vote v LEFT JOIN post p ON p.PostId = v.PostId AND p.postId = p.AcceptedAnswerId AND p.postId = :PostId))", array("PostId"=>$this->PostId));
            $nbr = $query->fetch();
            if (!$nbr[0])
                $nbr[0] = 0;
            return ($nbr);
        }

        public function deleteAllVotes(){
            self::execute("DELETE FROM Vote where PostId = :PostId", array("PostId"=>$this->PostId));
        }

        public function thumbs(){
            $query = self::execute("SELECT UpDown FROM Vote where PostId = :PostId AND UserId = :UserId", array("PostId"=>$this->PostId, "UserId"=>$this->UserId));
            $data = $query->fetch();
            if ($data == NULL){
                if (self::execute("INSERT INTO Vote VALUES (:UserId, :PostId, :UpDown)", array("PostId"=>$this->PostId, "UserId"=>$this->UserId, "UpDown"=> $this->UpDown)))
                    return true;
                else 
                    return false;
            }
            else {
                self::execute("DELETE FROM Vote where PostId = :PostId AND UserId = :UserId", array("PostId"=>$this->PostId, "UserId"=>$this->UserId));
                if ($data[0] != $this->UpDown) {
                    if (self::execute("INSERT INTO Vote VALUES (:UserId, :PostId, :UpDown)", array("PostId"=>$this->PostId, "UserId"=>$this->UserId, "UpDown"=> $this->UpDown)))
                        return true;
                    else
                        return false;
                }
                return false;
            }
        }

        public static function vote($user, $postId){
            if (isset($user)) {
                $query = self::execute("SELECT UpDown from Vote where UserId = :UserId and PostId = :PostId", array("PostId"=> $postId, "UserId"=>$user->Id));
                $data = $query->fetch();
                return (new Vote($user->Id, $postId, $data['UpDown'])); 
            }
            else  
                return null;
        }

        public static function get_votes_as_json($question, $answers, $userId) {
            $str = "";
            if (isset($question->vote))
            {
                $nbVote = $question->NbVotes[0];
                if ($nbVote == null)
                    $nbVote = 0;
                $hasVoted = $question->vote->UpDown;
                if (!isset($hasVoted))
                    $hasVoted = 0;
                $postId = json_encode($question->PostId);
                $nbVote = json_encode($nbVote);
                $hasVoted = json_encode($hasVoted);
                $str .= "{\"PostId\":$postId, \"Votes\":[{\"Sum\":$nbVote,\"hasVoted\":$hasVoted}]},";
            }

            foreach($answers as $a)
            {
                if (isset($a->vote)) {
                    $nbVote = $a->NbVotes[0];
                    if ($nbVote == null)
                        $nbVote = 0;
                    $hasVoted = $a->vote->UpDown;
                    if (!isset($hasVoted))
                        $hasVoted = 0;

                    $postId = json_encode($a->PostId);
                    $nbVote = json_encode($nbVote);
                    $hasVoted = json_encode($hasVoted);
                    $str .= "{\"PostId\":$postId, \"Votes\":[{\"Sum\":$nbVote,\"hasVoted\":$hasVoted}]},";
                }
            }
            if($str !== "")
                $str = substr($str,0,strlen($str)-1);
            return "[$str]";
        }
    }

?>