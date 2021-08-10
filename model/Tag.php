<?php
    
    require_once 'framework/Controller.php';
    require_once 'framework/Model.php';

    class Tag extends Model {
        public $Id = -1;
        public $Name;
        public $nbTag = -1;

        public function __construct($Id, $Name, $nbTag)
        {
            $this->Id = $Id;
            $this->Name = $Name;
            $this->nbTag = $nbTag;
        }

        public static function getTagByQuestionId($id)
        {
            $tags = [];
            $query = self::execute("SELECT * FROM posttag WHERE PostId = :id", array("id"=>$id));
            $data = $query->fetchAll();
            foreach ($data as $a)
                $tags[] = Tag::getTagById($a['TagId']);
            return ($tags);
        }

        public static function getTagById($id)
        {
            $query = self::execute("SELECT * FROM tag where TagId = :id", array("id"=>$id));
            $data = $query->fetch();
            return (new Tag($data["TagId"], $data["TagName"], 0));
        }

        public static function getTagIdByName($name)
        {
            $query = self::execute("SELECT TagId FROM Tag where TagName = :name", array("name"=>$name));
            $data = $query->fetch();
            return ($data);
        }

        public static function getAllTags()
        {
            $tags = [];
            $query = self::execute("SELECT * FROM Tag", array());
            $data = $query->fetchAll();
            if ($data)
                foreach($data as $tag)
                    $tags[] = new Tag($tag['TagId'], $tag['TagName'], Tag::getNbTag($tag['TagId']));
            return ($tags);
        }

        private static function getNbTag($tagId)
        {
            $query = self::execute("SELECT count(*) FROM posttag where TagId = :TagId", array("TagId"=>$tagId));
            return ($query->fetch());
        }

        public function validate()
        {
            if (!$this->Name || ctype_space($this->Name))
                return (false);
            else
                return (true);
        }

        public function update()
        {
            $query = self::execute("SELECT * FROM Tag where TagId = :tagId", array("tagId"=>$this->Id));
            $data = $query->fetch();
            if ($data[0] == NULL)
                return (false);
            else {
                $query = self::execute("SELECT * FROM Tag where TagName = :tagName", array("tagName"=>$this->Name));
                if ($query->rowCount() == 0)
                    self::execute("UPDATE Tag set TagName = :tagName where TagId = :tagId", array("tagName"=>$this->Name, "tagId"=>$this->Id));
                return (true);
            }
        }

        public function delete()
        {
            $query = self::execute("SELECT * FROM Tag where TagId = :tagId", array("tagId"=>$this->Id));
            $data = $query->fetch();
            if ($data[0] == NULL)
                return (false);
            else {
                self::execute("DELETE From posttag where TagId = :TagId", array("TagId"=>$this->Id));
                self::execute("DELETE From tag where TagId = :TagId", array("TagId"=>$this->Id));
                return (true);
            }
        }

        public function add() 
        {
            $query = self::execute("SELECT * FROM tag WHERE TagName = :tagName", array("tagName"=>$this->Name));
            $data = $query->fetch();
            if ($data[0] != NULL)
                return (false);
            else {
                self::execute("INSERT INTO tag(TagName) values (:TagName)", array("TagName"=>$this->Name));
                return (true);
            }
        }

        public static function check_ask($selectedtags, $max_tags)
        {
            if (count($selectedtags) > $max_tags)
                return (false);
            else
                return (true);
        }

        public static function deletePosttags($post)
        {
            $tags = Tag::getTagByQuestionId($post->PostId);
            foreach ($tags as $t)
                $query = self::execute("DELETE FROM posttag where PostId = :PostId and TagId = :TagId", array("PostId"=>$post->PostId, "TagId"=>$t->Id));
        }

        public static function remove_posttag($tagId, $PostId)
        {
            return (self::execute("DELETE FROM posttag where PostId = :PostId and TagId = :TagId", array("PostId"=>$PostId, "TagId"=>$tagId)));
        }

        public static function getOtherTags($id)
        {
            $results = [];
            $query = self::execute("SELECT * FROM Tag WHERE NOT EXISTS (SELECT * FROM posttag where tag.TagId = posttag.TagId and PostId = :PostId)", array("PostId"=>$id));
            $data = $query->fetchAll();
            foreach($data as $a)
            {
                $results[] = new Tag($a['TagId'], $a['TagName'], NULL);
            }
            return ($results);
        }

        public function add_posttag($PostId)
        {
            $max_tag = Configuration::get("max_tags");
            $query = self::execute("SELECT * FROM posttag where PostId = :PostId", array("PostId"=>$PostId));
            if ($query->rowCount() >= $max_tag)
                return (false);
            else {
                self::execute("INSERT INTO posttag(TagId, PostId) values (:TagId, :PostId)", array("TagId"=>$this->Id, "PostId"=>$PostId));
                return (true);
            }
        }

        public static function get_tag_as_json($tags)
        {
            $str = "";
            foreach($tags as $t) {
                $Id = $t->Id;
                $Name = $t->Name;
                $nbTag = $t->nbTag;

                $Id = json_encode($Id);
                $Name = json_encode($Name);
                $nbTag = json_encode($nbTag);
                
                $str .="{\"Id\":$Id, \"Name\":$Name, \"nbTag\":$nbTag},";
            }
            if($str !== "")
                $str = substr($str,0,strlen($str)-1);
            return $str;
        }
    }
?>