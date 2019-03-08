<?php
/*
 |  Snicker     A small Comment System 4 Bludit
 |  @file       ./system/comments.class.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.0
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2018 - 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    class Comments extends dbJSON{
        /*
         |  DATABASE FIELDS
         |      `key`       /page_key/comment_uniqueID
         */
        protected $dbFields = array(
            "title"         => "",              // Comment Title
            "type"          => "pending",       // Comment Status: pending, approved, rejected, spam
            "like"          => 0,               // Comment Like Counter
            "dislike"       => 0,               // Comment Dislike Counter
            "username"      => "",              // Author Username
            "email"         => "",              // Author eMail Address
            "website"       => "",              // Author Website
            "uid"           => "",              // Unique Comment ID
            "page_key"      => "",              // Page Key (Page Path)
            "parent_id"     => "",              // Parent Comment ID
            "subscribe"     => false,           // Subscribed Comment
            "date"          => "",              // Date Creation
            "dateModified"  => "",              // Date Modified
            "custom"        => array()          // Meta Data
        );

        /*
         |  CONSTRUCTOR
         |  @since 0.1.0
         */
        public function __construct(){
            parent::__construct(DB_SNICKER_COMMENTS);
            if(!file_exists(DB_SNICKER_COMMENTS)){
                $this->db = array();
                $this->save();
            }
        }

        /*
         |  HELPER :: GENERATE UNIQUE ID
         |  @since  0.1.0
         */
        private function uniqueID(){
            if(function_exists("random_bytes")){
                return bin2hex(random_bytes(16));
            } else if(function_exists("openssl_random_pseudo_bytes")){
                return bin2hex(openssl_random_pseudo_bytes(16));
            }
            return md5(uniqid() . time());
        }

        /*
         |  HELPER :: GENERATE UNIQUE USER ID
         |  @since  0.1.0
         */
        private function uniqueUserID($userdata){
            return password_hash();
        }


        /*
         |  HELPER :: GET DEFAULT FIELDS
         |  @since  0.1.0
         */
        public function getDefaultFields(){
            return $this->dbFields;
        }


        /*
         |  GET DATABASE
         |  @since  0.1.0
         */
        public function getDB($keys = true){
            if($keys){
                return array_keys($this->db);
            }
            return $this->db;
        }

        /*
         |  GET PENDING DATABASE
         |  @since  0.1.0
         */
        public function getPendingDB($keys = true){
            $temp = $this->db;
            foreach($temp AS $key => $fields){
                if($fields["type"] !== "pending"){
                    unset($temp[$key]);
                }
            }
            if($keys){
                return array_keys($temp);
            }
            return $temp;
        }

        /*
         |  GET PUBLIC DATABASE
         |  @since  0.1.0
         */
        public function getPublicDB($keys = true){
            $temp = $this->db;
            foreach($temp AS $key => $fields){
                if($fields["type"] !== "approved"){
                    unset($temp[$key]);
                }
            }
            if($keys){
                return array_keys($temp);
            }
            return $temp;
        }

        /*
         |  GET REJECTED DATABASE
         |  @since  0.1.0
         */
        public function getRejectedDB($keys = true){
            $temp = $this->db;
            foreach($temp AS $key => $fields){
                if($fields["type"] !== "rejected"){
                    unset($temp[$key]);
                }
            }
            if($keys){
                return array_keys($temp);
            }
            return $temp;
        }

        /*
         |  GET SPAM DATABASE
         |  @since  0.1.0
         */
        public function getSpamDB($keys = true){
            $temp = $this->db;
            foreach($temp AS $key => $fields){
                if($fields["type"] !== "spam"){
                    unset($temp[$key]);
                }
            }
            if($keys){
                return array_keys($temp);
            }
            return $temp;
        }

        /*
         |  GET COMMENTS BY PAGE
         |  @since  0.1.0
         */
        public function getPageCommentsDB($page_key, $keys = true){
            $temp = $this->db;
            foreach($temp AS $key => $fields){
                if(strpos($key, $page_key . "/comment_") !== 0){
                    unset($temp[$key]);
                }
                if($fields["type"] !== "approved"){
                    unset($temp[$key]);
                }
            }
            if($keys){
                return array_keys($temp);
            }
            return $temp;
        }

        /*
         |  GET COMMENT ITEM
         |  @since  0.1.0
         */
        public function getCommentDB($key){
            if($this->exists($key)){
                return $this->db[$key];
            }
            return false;
        }

        /*
         |  CHECK IF COMMENT ITEM EXISTS
         |  @since  0.1.0
         */
        public function exists($key){
            return isset($this->db[$key]);
        }

        /*
         |  ADD A NEW COMMENT
         |  @since  0.1.0
         */
        public function add($args){
            global $pages, $snicker;
            $login = new Login();
            $admin = ($login->isLogged() && $login->role() === "admin");

            // Loop Default Fields
            $row = array();
            foreach($this->dbFields AS $field => $value){
                if(isset($args[$field])){
                    $final = Sanitize::html($args[$field]);
                } else {
                    $final = $value;
                }
                settype($final, gettype($value));
                $row[$field] = $final;
            }

            // Set Data
            $row["uid"] = $this->uniqueID();
            $row["date"] = Date::current(DB_DATE_FORMAT);
            if($snicker->getValue("moderation") == "each"){
                $row["type"] = ($admin)? "approved": "pending";
            } else {
                $row["type"] = "approved";
            }

            // Generate Key
            if(!isset($row["page_key"]) || !$pages->exists($row["page_key"])){
                //@todo Error
                return false;
            }
            $key = $row["page_key"] . "/comment_" . $row["uid"];

            // Create Comment Directory
            $dir = PATH_PAGES . $row["page_key"] . DS . "comments";
            if(!file_exists($dir) && Filesystem::mkdir($dir, true) === false){
                Log::set(__METHOD__.LOG_SEP.'Error occurred when trying to create the directory ['.$dir.']', LOG_TYPE_ERROR);
                return false;
            }

            // Create Comment File
            $comment = (empty($args["comment"])? "": $args["comment"]);
            if(file_put_contents($dir . DS . "comment_{$row["uid"]}.php", $comment) === false){
                Log::set(__METHOD__.LOG_SEP.'Error occurred when trying to create the content in the file [comment_'.$row["uid"].'.php]',LOG_TYPE_ERROR);
                return false;
            }

            // Insert and Return
            $this->db[$key] = $row;
            $this->sortBy();
            $this->save();
            return $key;
        }

        /*
         |  EDIT EXISTING COMMENT
         |  @since  0.1.0
         */
        public function edit($args){
            global $pages, $Snicker;
            $login = new Login();
            $admin = ($login->isLogged() && $login->role() === "admin");

            // Check Key
            $key = $args["key"];
            if(!isset($this->db[$key])){
                //@todo Error
                return false;
            }

            // Loop Default Fields
            $row = array();
            foreach($this->dbFields AS $field => $value){
                if(isset($args[$field])){
                    $final = Sanitize::html($args[$field]);
                } else {
                    $final = $this->db[$key][$field];
                }
                settype($final, gettype($value));
                $row[$field] = $final;
            }

            // Set Data
            $row["dateModified"] = Date::current(DB_DATE_FORMAT);
            if(!$admin && $this->db[$key]["type"] !== $row["type"]){
                $row["type"] = $this->db[$key]["type"];
            }

            // Check Comment File
            $dir = PATH_PAGES . $row["page_key"] . DS . "comments";
            if(!file_exists($dir . DS . "comment_{$row["uid"]}.php")){
                //@todo Error
                return false;
            }

            // Update Comment File
            if(isset($args["comment"]) && !empty($args["comment"])){
                if(file_put_contents($dir . DS . "comment_{$row["uid"]}.php", $args["comment"]) === false){
                    //@todo Error
                    return false;
                }
            }

            // Insert and Return
    		unset($this->db[$key]);
            $this->db[$key] = $row;
            $this->sortBy();
            $this->save();
            return $key;
        }

        /*
         |  DELETE EXISTING COMMENT
         |  @since  0.1.0
         */
        public function delete($key){
            global $Snicker;

            // Check Key
            if(!isset($this->db[$key])){
                //@todo Error
                return false;
            }
            $row = $this->db[$key];

            // Remove Comment File
            $file = PATH_PAGES . $row["page_key"] . DS . "comments/comment_" . $row["uid"] . ".php";
            if(!file_exists($file) || (file_exists($file) && !Filesystem::rmfile($file))){
                return false;
            }

            // Remove Database Item
            unset($this->db[$key]);
            $this->save();
            return true;
        }

        /*
         |  SORT BY
         |  @since  0.1.0
         */
        public function sortBy(){
            global $snicker;

            if($snicker->getValue("frontend_order") === "date_asc"){
                uasort($this->db, function($a, $b){
                    return $a["date"] > $b["date"];
                });
            } else if($snicker->getValue("frontend_order") === "date_desc"){
                uasort($this->db, function($a, $b){
                    return $a["date"] < $b["date"];
                });
            }
            return true;
        }
    }
