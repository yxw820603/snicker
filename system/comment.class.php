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

    class Comment{
        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         */
        public function __construct($key){
            global $comments;

            // Get Item
            $this->vars["key"] = $key;
            if($key === false){
                $row = $comments->getDefaultFields();
            } else {
                if(Text::isEmpty($key) || !$comments->exists($key)){
                    // @todo Throw Error
                }
                $row = $comments->getCommentDB($key);
            }

            // Set Class
            foreach($row AS $field => $value){
                if(strpos($field, "date") === 0){
                    $this->vars["{$field}Raw"] = $value;
                } else {
                    $this->vars[$field] = $value;
                }
            }
        }

        /*
         |  PUBLIC :: GET VALUE
         |  @since  0.1.0
         */
        public function getValue($field, $default = false){
            if(isset($this->vars[$field])){
                return $this->vars[$field];
            }
            return $default;
        }

        /*
         |  PUBLIC :: SET FIELD
         |  @since  0.1.0
         */
        public function setField($field, $value = NULL){
            if(is_array($field)){
                foreach($field AS $k => $v){
                    $this->setField($k,  $v);
                }
                return true;
            }
            $this->vars[$field] = $value;
            return true;
        }


        /*
         |  FIELD :: COMMENT RAW
         |  @since  0.1.0
         */
        public function commentRaw($sanitize = false){
            if(isset($this->commentRaw)){
                return ($sanitize)? Sanitize::html($this->commentRaw): $this->commentRaw;
            }
            $key = $this->page_key();
            $path = PATH_PAGES . $key . DS . "comments" . DS;
            $file = "comment_" . $this->vars["uid"] . ".php";

            // Check File
            if(!file_exists($path . $file)){
                return false;
            }
            $this->commentRaw = file_get_contents($path . $file);
            return $this->commentRaw($sanitize);
        }

        /*
         |  FIELD :: COMMENT
         |  @since  0.1.0
         */
        public function comment($sanitize = false){
            global $snicker;

            // Shortcut
            if(isset($this->comment)){
                return ($sanitize)? Sanitize::html($this->comment): $this->comment;
            }

            // Prepare Comment
            if(($comment = $this->commentRaw()) === false){
                return false;
            }

            // Parse HTML
            $allowed  = "<a><b><strong><i><em><u><del><ins><s><strike><p><br><br/><br />";
            $allowed .= "<mark><abbr><acronym><dfn><ul><ol><li><dl><dt><dd><hr><hr/><hr />";
            if($snicker->getValue("comment_markup_html") == 1){
                $comment = strip_tags($comment, $allowed);
            } else {
                $comment = strip_tags($comment);
            }

            // Parse Markdown
            if($snicker->getValue("comment_markup_markdown") == 1){
        		$parsedown = new Parsedown();
        		$comment = $parsedown->text($comment);
            }

            // Return Content
            $this->comment = $comment;
            return $this->comment($sanitize);
        }

        /*
         |  FIELD :: GET DEPTH
         |  @since  0.1.0
         */
        public function depth(){
            return 0;
        }

        /*
         |  FIELD :: GET DATE
         |  @since  0.1.0
         */
        public function date($format = false){
            global $site;
            $date = $this->getValue("dateRaw");
            return Date::format($date, DB_DATE_FORMAT, ($format? $format: $site->dateFormat()));
        }

        /*
         |  FIELD :: GET MODIFIED DATE
         |  @since  0.1.0
         */
        public function dateModified($format = false){
            global $site;
            $date = $this->getValue("dateModifiedRaw");
            return Date::format($date, DB_DATE_FORMAT, ($format? $format: $site->dateFormat()));
        }

        /*
         |  FIELD :: TITLE
         |  @since  0.1.0
         */
        public function title(){
            return $this->getValue("title");
        }

        /*
         |  FIELD :: TYPE
         |  @since  0.1.0
         */
        public function type(){
            return $this->getValue("type");
        }
        public function isPending(){
            return $this->getValue("type") === "pending";
        }
        public function isPublic(){
            return $this->getValue("type") === "approved";
        }
        public function isApproved(){
            return $this->getValue("type") === "approved";
        }
        public function isRejected(){
            return $this->getValue("type") === "rejected";
        }
        public function isSpam(){
            return $this->getValue("type") === "spam";
        }

        /*
         |  FIELD :: LIKE
         |  @since  0.1.0
         */
        public function like(){
            return (int) $this->getValue("like");
        }

        /*
         |  FIELD :: DISLIKE
         |  @since  0.1.0
         */
        public function dislike(){
            return (int) $this->getValue("dislike");
        }

        /*
         |  FIELD :: USERNAME
         |  @since  0.1.0
         */
        public function username(){
            return $this->getValue("username");
        }

        /*
         |  FIELD :: EMAIL
         |  @since  0.1.0
         */
        public function email(){
            global $user, $users;

            if($this->getValue("email") === "*"){
                if($users->exists($this->getValue("username"))){
                    $user = new User($this->getValue("username"));
                    if(!empty($user->email())){
                        return $user->email();
                    }
                }
                return "comment@" . $_SERVER["SERVER_NAME"];
            }
            return $this->getValue("email");
        }

        /*
         |  FIELD :: WEBSITE
         |  @since  0.1.0
         */
        public function website(){
            return $this->getValue("website");
        }

        /*
         |  FIELD :: UNIQUE COMMENT ID
         |  @since  0.1.0
         */
        public function uid(){
            return $this->getValue("uid");
        }

        /*
         |  FIELD :: KEY
         |  @since  0.1.0
         */
        public function key(){
            return $this->getValue("page_key") . "/comment_" . $this->getValue("uid");
        }

        /*
         |  FIELD :: PAGE KEY
         |  @since  0.1.0
         */
        public function page_key(){
            return $this->getValue("page_key");
        }

        /*
         |  FIELD :: PARENT COMMENT ID
         |  @since  0.1.0
         */
        public function parent_id(){
            return $this->getValue("parent_id");
        }

        /*
         |  FIELD :: HAS SUBSCRIBED
         |  @since  0.1.0
         */
        public function hasSubscribed(){
            return $this->getValue("subscribe");
        }
    }
