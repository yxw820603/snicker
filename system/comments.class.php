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
         */
        protected $dbFields = array(
            "type"          => "comment",   // Comment Type ("comment", "reply", "pingback")
            "depth"         => 1,           // Comment Depth (starting with 1)
            "title"         => "",          // Comment Title
            "status"        => "pending",   // Comment Status ("pending", "approved", "rejected", "spam")
            "rating"        => [0, 0],      // Comment Rating
            "page_key"      => "",          // Comment Page Key
            "parent_uid"    => "",          // Comment Parent UID

            "uuid"          => "",          // Unique User ID or "bludit"
            "username"      => "",          // Username
            "email"         => "",          // eMail Address (or null if "bludit")
            "subscribe"     => false,       // eMail Subscription

            "date"          => "",          // Date Comment Written
            "dateModified"  => "",          // Date Comment Modified
            "dateAudit"     => "",          // Date Comment Audit
            "custom"        => array(),     // Custom Data
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
         |  HELPER :: FILL LOG FILE
         |  @since  0.1.0
         |
         |  @param  string  The respective method for the log (Use __METHOD__)
         |  @param  string  The respective error message to be logged.
         |  @param  array   Additional values AS array for the `vsprintf` function.
         */
        private function log($method, $string, $args){
            $strings = array(
                "error-comment-uid"     => "The comment UID is invalid or does not exist [%s]",
                "error-page-key"        => "The page key is invalid or does not exist [%s]",
                "error-create-dir"      => "The comment directory could not be created [%s]",
                "error-create-file"     => "The comment file could not be created [%s]",
                "error-comment-file"    => "The comment file does not exist [%s]",
                "error-comment-update"  => "The comment file could not be updated [%s]",
                "error-comment-remove"  => "The comment file could not be deleted [%s]",
                "error-update-db"       => "The comment database could not be updated"
            );
            if(array_key_exists($string, $strings)){
                $string = $strings[$string];
            }
            Log::set($method . LOG_SEP . vsprintf("Error occured: {$string}", $args), LOG_TYPE_ERROR);
        }

        /*
         |  HELPER :: GENERATE UNIQUE COMMENT ID
         |  @since  0.1.0
         */
        private function generateUID(){
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
        private function generateUUID($username = null){
            global $security;
            return md5($security->getUserIp() . $_SERVER["HTTP_USER_AGENT"]);
        }


        /*
         |  PUBLIC :: GET DEFAULT FIELDS
         |  @since  0.1.0
         |
         |  @return array   An array with all default fields and values per entry.
         */
        public function getDefaultFields(){
            return $this->dbFields;
        }


        /*
         |  DATA :: GET DATABASE
         |  @since  0.1.0
         |
         |  @param  bool    TRUE to just return the keys, FALSE to return the complete DB.
         |
         |  @return array   The complete database entries (or keys) within an ARRAY.
         */
        public function getDB($keys = true){
            if($keys){
                return array_keys($this->db);
            }
            return $this->db;
        }

        /*
         |  DATA :: GET PENDING DATABASE
         |  @since  0.1.0
         |
         |  @param  bool    TRUE to just return the keys, FALSE to return the complete DB.
         |
         |  @return array   The respective database entries (or keys) within an ARRAY.
         */
        public function getPendingDB($keys = true){
            $temp = $this->db;
            foreach($temp AS $key => $fields){
                if($fields["status"] !== "pending"){
                    unset($temp[$key]);
                }
            }
            if($keys){
                return array_keys($temp);
            }
            return $temp;
        }

        /*
         |  DATA :: GET PUBLIC DATABASE
         |  @since  0.1.0
         |
         |  @param  bool    TRUE to just return the keys, FALSE to return the complete DB.
         |
         |  @return array   The respective database entries (or keys) within an ARRAY.
         */
        public function getPublicDB($keys = true){
            $temp = $this->db;
            foreach($temp AS $key => $fields){
                if($fields["status"] !== "approved"){
                    unset($temp[$key]);
                }
            }
            if($keys){
                return array_keys($temp);
            }
            return $temp;
        }

        /*
         |  DATA :: GET REJECTED DATABASE
         |  @since  0.1.0
         |
         |  @param  bool    TRUE to just return the keys, FALSE to return the complete DB.
         |
         |  @return array   The respective database entries (or keys) within an ARRAY.
         */
        public function getRejectedDB($keys = true){
            $temp = $this->db;
            foreach($temp AS $key => $fields){
                if($fields["status"] !== "rejected"){
                    unset($temp[$key]);
                }
            }
            if($keys){
                return array_keys($temp);
            }
            return $temp;
        }

        /*
         |  DATA :: GET SPAM DATABASE
         |  @since  0.1.0
         |
         |  @param  bool    TRUE to just return the keys, FALSE to return the complete DB.
         |
         |  @return array   The respective database entries (or keys) within an ARRAY.
         */
        public function getSpamDB($keys = true){
            $temp = $this->db;
            foreach($temp AS $key => $fields){
                if($fields["status"] !== "spam"){
                    unset($temp[$key]);
                }
            }
            if($keys){
                return array_keys($temp);
            }
            return $temp;
        }

        /*
         |  DATA :: GET COMMENTS BY PAGE
         |  @since  0.1.0
         |
         |  @param  string  The content page slug / key.
         |  @param  bool    TRUE to just return the keys, FALSE to return the complete DB.
         |
         |  @return array   The respective database entries (or keys) within an ARRAY.
         */
        public function getPageCommentsDB($page_key, $keys = true){
            $temp = $this->db;
            foreach($temp AS $key => $fields){
                if($fields["page_key"] !== $page_key){
                    unset($temp[$key]);
                }
                if($fields["status"] !== "approved"){
                    unset($temp[$key]);
                }
            }
            if($keys){
                return array_keys($temp);
            }
            return $temp;
        }

        /*
         |  DATA :: GET COMMENT ITEM
         |  @since  0.1.0
         |
         |  @param  string  The unique comment ID.
         |
         |  @return array   The comment data array on success, FALSE on failure.
         */
        public function getCommentDB($uid){
            if($this->exists($uid)){
                return $this->db[$uid];
            }
            return false;
        }

        /*
         |  DATA :: CHECK IF COMMENT ITEM EXISTS
         |  @since  0.1.0
         |
         |  @param  string  The unique comment ID.
         |
         |  @return bool    TRUE if the comment ID exists, FALSE if not.
         */
        public function exists($uid){
            return isset($this->db[$uid]);
        }

        /*
         |  DATA :: LIST COMMENTS
         |  @since  0.1.0
         |
         |  @param  int     The current comment page.<, startin with 1.
         |  @param  int     The number of comments to be shown per page.
         |  @param  multi   The desired comment status as STRING, multiple as ARRAY.
         |                  Pass `null` to get each comment status.
         |  @param  multi   The desired content page as STRING, multiple as ARRAY.
         |                  Pass `null` to get each comment of each content page.
         |
         |  @return array   The respective database keys with an ARRAY or FALSE on failure.
         */
        public function getList($page, $limit, $status = "approved", $page_key = null){
            $status = is_string($status)? array($status): $status;
            if(!is_array($status)){
                $status = null;
            }
            $page_key = is_string($page_key)? array($page_key): $page_key;
            if(!is_array($page_key)){
                $page_key = null;
            }

            // Format List
            $list = array();
            foreach($this->db AS $key => $fields){
                if($page_key !== null && !in_array($fields["page_key"], $page_key)){
                    continue;
                }
                if($status !== null && !in_array($fields["status"], $status)){
                    continue;
                }
                array_push($list, $key);
            }

            // Limit
            if($limit == -1){
                return $list;
            }

            // Offset
            $offset = $limit * ($page - 1);
            $count  = min(($offset + $limit - 1), count($list));
            if($offset < 0 || $offset > $count){
                return false;
            }
            return array_slice($list, $offset, $limit, true);
        }

        /*
         |  DATA :: COUNT COMMENTS
         |  @since  0.1.0
         |
         |  @param  multi   The desired comment status as STRING, multiple as ARRAY.
         |                  Pass `null` to get each comment status.
         |  @param  multi   The desired content page as STRING, multiple as ARRAY.
         |                  Pass `null` to get each comment of each content page.
         |
         |  @return int     The total number of comments.
         */
        public function count($status = "approved", $page_key = null){
            $status = is_string($status)? array($status): $status;
            if(!is_array($status)){
                $status = null;
            }
            $page_key = is_string($page_key)? array($page_key): $page_key;
            if(!is_array($page_key)){
                $page_key = null;
            }

            // Count All
            if($status === null && $page_key === null){
                return count($this->db);
            }

            // Count
            $count = 0;
            foreach($this->db AS $key => $fields){
                if($page_key !== null && !in_array($fields["page_key"], $page_key)){
                    continue;
                }
                if($status !== null && !in_array($fields["status"], $status)){
                    continue;
                }
                $count++;
            }
            return $count;
        }


        /*
         |  HANDLE :: ADD A NEW COMMENT
         |  @since  0.1.0
         |
         |  @param  array   The respective comment array.
         |
         |  @return multi   The comment UID on success, FALSE on failure.
         */
        public function add($args){
            global $pages;

            // Loop Default Fields
            $row = array();
            foreach($this->dbFields AS $field => $value){
                if(isset($args[$field])){
                    $final = is_string($args[$field])? Sanitize::html($args[$field]): $args[$field];
                } else {
                    $final = $value;
                }
                settype($final, gettype($value));
                $row[$field] = $final;
            }

            // Check Page Key
            if(!isset($row["page_key"]) || !$pages->exists($row["page_key"])){
                $this->log(__METHOD__, "error-page-key", array($row["page_key"]));
                return false;
            }
            $uid = $this->generateUID();

            // Check Parent UID
            if(!isset($row["parent_uid"]) || !$this->exists($row["parent_uid"])){
                $row["parent_uid"] = null;
            }

            // Set Comment Data
            $row["date"] = Date::current(DB_DATE_FORMAT);
            $row["dateModified"] = null;
            $row["dateAudit"] = ($row["status"] !== "pending")? Date::current(DB_DATE_FORMAT): null;

            // Set User Data
            if($row["uuid"] !== "bludit"){
                $row["uuid"] = $this->generateUUID($row["username"]);
            }

            // Create Comment Directory
            $path = PATH_PAGES . $row["page_key"] . DS . "comments";
            if(!file_exists($path) && Filesystem::mkdir($path, true) === false){
                $this->log(__METHOD__, "error-create-dir", array($path));
                return false;
            }

            // Create Comment File
            $file = $path . DS . "c_" . $uid . ".php";
            if(file_put_contents($file, $args["comment"]) === false){
                $this->log(__METHOD__, "error-create-file", array($file));
                return false;
            }

            // Insert and Return
            $this->db[$uid] = $row;
            $this->sortBy();
            if($this->save() !== true){
                Log::set(__METHOD__, "error-update-db");
                return false;
            }
            return $uid;
        }

        /*
         |  HANDLE :: EDIT AN EXISTING COMMENT
         |  @since  0.1.0
         |
         |  @param  string  The unique comment ID as STRING.
         |  @param  array   The respective comment data, whcih you want to update.
         |
         |  @return multi   The comment UID on success, FALSE on failure.
         */
        public function edit($uid, $args){
            global $pages;

            // Check Comment UID
            if(!$this->exists($uid)){
                $this->log(__METHOD__, "error-comment-uid", array($uid));
                return false;
            }
            $data = $this->db[$uid];

            // Loop Default Fields
            $row = array();
            foreach($this->dbFields AS $field => $value){
                if(isset($args[$field])){
                    $final = is_string($args[$field])? Sanitize::html($args[$field]): $args[$field];
                } else {
                    $final = $data[$field];
                }
                settype($final, gettype($value));
                $row[$field] = $final;
            }

            // Check Page Key
            if($data["page_key"] !== $row["page_key"]){
                if(!isset($row["page_key"]) || !$pages->exists($row["page_key"])){
                    $this->log(__METHOD__, "error-page-key", array($row["page_key"]));
                    return false;
                }
            }

            // Check Parent UID
            if(!isset($row["parent_uid"]) || !$this->exists($row["parent_uid"])){
                $row["parent_uid"] = $data["parent_uid"];
            }

            // Set Comment Data
            $row["date"] = $data["date"];   // Cannot be changed
            $row["dateModified"] = Date::current(DB_DATE_FORMAT);
            $row["dateAudit"] = ($row["status"] !== "pending")? Date::current(DB_DATE_FORMAT): null;

            // Set User Data
            if($data["uuid"] !== $row["uuid"]){
                $row["uuid"] = $this->generateUUID($row["username"]);
            }

            // Check Comment File
            $path = PATH_PAGES . $row["page_key"] . DS . "comments" . DS;
            $file = $path . "c_" . $uid . ".php";
            if(!file_exists($path) || !file_exists($file)){
                Log::set(__METHOD__, "error-comment-file", $file);
                return false;
            }

            // Update Comment File
            if(isset($args["comment"]) && !empty($args["comment"])){
                if(file_put_contents($file, $args["comment"]) === false){
                    Log::set(__METHOD__, "error-comment-update", $file);
                    return false;
                }
            }

            // Insert and Return
    		unset($this->db[$uid]);
            $this->db[$uid] = $row;
            $this->sortBy();

            if($this->save() !== true){
                Log::set(__METHOD__, "error-update-db");
                return false;
            }
            return $uid;
        }

        /*
         |  HANDLE :: DELETE AN EXISTING COMMENT
         |  @since  0.1.0
         |
         |  @param  array   The respective comment UID to delete.
         |
         |  @return bool    TRUE on success, FALSE on failure.
         */
        public function delete($uid){
            if(!isset($this->db[$uid])){
                return false;
            }
            $row = $this->db[$uid];

            // Check Comment File
            $path = PATH_PAGES . $row["page_key"] . DS . "comments" . DS;
            $file = $path . "c_" . $uid . ".php";
            if(!file_exists($path) || !file_exists($file)){
                Log::set(__METHOD__, "error-comment-file", $file);
                return false;
            }

            // Remove Comment File
            if(!Filesystem::rmfile($file)){
                Log::set(__METHOD__, "error-comment-remove", $file);
                return false;
            }

            // Remove Database Item
            unset($this->db[$uid]);
            if($this->save() === true){
                Log::set(__METHOD__, "error-update-db");
                return false;
            }
            return true;
        }

        /*
         |  INTERNAL :: SORT COMMENTS
         |  @since  0.1.0
         |
         |  @return bool    TRUE
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
