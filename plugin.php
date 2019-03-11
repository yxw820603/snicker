<?php
/*
 |  Snicker     A small Comment System 4 Bludit
 |  @file       ./plugin.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.0
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2018 - 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    class SnickerPlugin extends Plugin{
        /*
         |  BACKEND VARIABLES
         */
        private $backend = false;               // Is Backend
        private $backendView = null;            // Backend View / File
        private $backendRequest = null;         // Backend Request Type ("post", "get", "ajax")

        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         */
        public function __construct(){
            global $snicker;
            $snicker = $this;
            parent::__construct();
        }

        /*
         |  HELPER :: SELECTED
         |  @since  0.1.0
         |
         |  @param  string  The respective option key.
         |  @param  multi   The value to compare with.
         |  @param  bool    TRUE to print `selected="selected"`, FALSE to return the string.
         |                  Use `null` to return as boolean!
         |
         |  @return multi   The respective string, nothing or a BOOLEAN indicator.
         */
        public function selected($field, $value = true, $print = true){
            if($this->getValue($field) == $value){
                $selected = 'selected="selected"';
            } else {
                $selected = '';
            }
            if($print === null){
                return !empty($selected);
            }
            if(!$print){
                return $selected;
            }
            print($selected);
        }

        /*
         |  HELPER :: CHECKED
         |  @since  0.1.0
         |
         |  @param  string  The respective option key.
         |  @param  multi   The value to compare with.
         |  @param  bool    TRUE to print `checked="checked"`, FALSE to return the string.
         |                  Use `null` to return as boolean!
         |
         |  @return multi   The respective string, nothing or a BOOLEAN indicator.
         */
        public function checked($field, $value = true, $print = true){
            if($this->getValue($field) == $value){
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }
            if($print === null){
                return !empty($checked);
            }
            if(!$print){
                return $checked;
            }
            print($checked);
        }

        /*
         |  HELPER :: RESPONSE
         |  @since  0.1.0
         |
         |  @param  bool    TRUE on success, FALSE on error.
         |  @param  array   An additional array with response data.
         |
         |  @return die()
         */
        private function handleResponse($status, $data = array()){
            global $L, $url;

            // Translate
            if(isset($data["success"])){
                $data["success"] = $L->get($data["success"]);
            }
            if(isset($data["error"])){
                $data["error"] = $L->get($data["error"]);
            }

            // POST Redirect
            if($this->backendRequest !== "ajax"){
                if($status){
                    Alert::set($data["success"], ALERT_STATUS_OK, "snicker-success");
                } else {
                    Alert::set($data["error"], ALERT_STATUS_FAIL, "snicker-alert");
                }
                $action = isset($_GET["action"])? $_GET["action"]: $_POST["action"];

                if($data["referer"]){
                    Redirect::url($data["referer"]);
                } else {
                    Redirect::url(HTML_PATH_ADMIN_ROOT . $url->slug() . "#{$action}");
                }
                die();
            }

            // AJAX Print
            if(!is_array($data)){
                $data = array();
            }
            $data["status"] = ($status)? "success": "error";
            $data = json_encode($data);

            header("Content-Type: application/json");
            header("Content-Length: " . strlen($data));
            print($data);
            die();
        }

        /*
         |  PLUGIN :: INIT
         |  @since  0.1.0
         */
        public function init(){
            global $url;

            // Init Default Settings
            $this->dbFields = array(
                "moderation"                => "each",
                "comment_title"             => "optional",
                "comment_limit"             => 0,
                "comment_depth"             => 0,
                "comment_markup_html"       => true,
                "comment_markup_markdown"   => false,
                "comment_enable_like"       => true,
                "comment_enable_dislike"    => true,
                "frontend_terms"            => "default",
                "frontend_filter"           => "pageEnd",
                "frontend_template"         => "default",
                "frontend_order"            => "date_desc",
                "frontend_per_page"         => 15,
                "frontend_ajax"             => false,
                "subscription"              => true,
                "subscription_from"         => "ticker@" . $_SERVER["SERVER_NAME"],
                "subscription_reply"        => "noreply@" . $_SERVER["SERVER_NAME"],
                "subscription_optin"        => "default",
                "subscription_ticker"       => "default",

                "string_success_1"          => "Thanks for your comment!",
                "string_success_2"          => "Thanks for your comment, please confirm your subscription via the link we sent to your eMail address!",
                "string_success_3"          => "Thanks for voting this comment!",
                "string_error_1"            => "An unknown error occured, please reload the page and try it again.",
                "string_error_2"            => "An error occured: The Username or the eMail address is missing!",
                "string_error_3"            => "An error occured: The comment text is missing!",
                "string_error_4"            => "An error occured: The comment title is missing!",
                "string_error_5"            => "An error occured: You need to accept the Terms to comment!",
                "string_error_6"            => "An error occured: Your IP address or eMail address has been marked as Spam!",
                "string_error_7"            => "An error occured: You already rated this comment!",
            );

            // Check Backend
            $this->backend = (trim($url->activeFilter(), "/") == ADMIN_URI_FILTER);
        }

        /*
         |  PLUGIN :: INIT IF INSTALLED
         |  @since  0.1.0
         */
        public function installed(){
            global $comments;

            if(file_exists($this->filenameDb)){
                if(!defined("SNICKER")){
                    define("SNICKER", true);
                    define("SNICKER_PATH", PATH_PLUGINS . basename(__DIR__) . "/");
                    define("SNICKER_DOMAIN", DOMAIN_PLUGINS . basename(__DIR__) . "/");
                    define("SNICKER_VERSION", "0.1.0");
                    define("DB_SNICKER_COMMENTS", $this->workspace() . "comments.php");

                    // Load Plugin
                    require_once("system/comments.class.php");
                    require_once("system/comment.class.php");
                    require_once("system/snicker-template.class.php");
                } else {
                    $comments = new Comments();
                    $this->loadThemes();
                    $this->handle();
                }
                return true;
            }
            return false;
        }

        /*
         |  PLUGIN :: HANDLE
         |  @since  0.1.0
         */
        public function handle(){
            global $url, $security;

            // Get Data
            if(isset($_POST["action"]) && isset($_POST["snicker"])){
                $data = $_POST;
                $this->backendRequest = "post";
            } else if(isset($_GET["action"]) && isset($_GET["snicker"])){
                $data = $_GET;
                $this->backendRequest = "get";
            } else {
                return null; // No Snicker Stuff here
            }

            // Start Session
            if(!Session::started()){
                Session::start();
            }

            // Check AJAX
            $ajax = "HTTP_X_REQUESTED_WITH";
            if(strpos($url->slug(), "snicker/ajax") === 0){
                if(isset($_SERVER[$ajax]) && $_SERVER[$ajax] === "XMLHttpRequest"){
                    $this->backendRequest = "ajax";
                } else {
                    return Redirect::url(HTML_PATH_ADMIN_ROOT . "snicker/");
                }
            } else if(isset($_SERVER[$ajax]) && $_SERVER[$ajax] === "XMLHttpRequest"){
                print("Invalid AJAX Call"); die();
            }
            if($this->backendRequest === "ajax" && !$this->getValue("frontend_template")){
                print("AJAX Calls has been disabled"); die();
            }

            // Validate Call
            if(!isset($data["tokenCSRF"]) || !$security->validateTokenCSRF($data["tokenCSRF"])){
                return $this->handleResponse(false, array(
                    "error"     => "snicker-response-001"
                ));
            }

            // Handle Frontend
            if($data["action"] === "snicker" && $data["snicker"] === "form"){
                return $this->handleFrontend($data);
            }

            // AJAX Protection
            if($this->backendRequest === "ajax"){
                return $this->handleResponse(false, array("error" => "snicker-response-002"));
            }

            // Handle Backend
            if($data["action"] === "snicker" && $data["snicker"] === "manage"){
                return $this->handleBackend($data);
            }

            // Handle Admin
            if($data["action"] === "snicker" && $data["snicker"] === "config"){
                return $this->handleConfig($data);
            }

            // Unknown Action
            return $this->handleResponse(false, array("error" => "snicker-response-011"));
        }

        /*
         |  PLUGIN :: HANDLE FRONTEND
         |  @since  0.1.0
         */
        public function handleFrontend($data){
            global $pages, $comments;

            // Validate
            if((!isset($data["comment"]) && !isset($data["uid"])) || !isset($data["type"])){
                return $this->handleResponse(false, array("error" => "snicker-response-004"));
            }
            $type = $data["type"];

            // Write Comment
            if($type === "comment" || $type === "reply"){
                return $this->writeComment($data["comment"]);
            }

            // Like Comment
            if($type === "like"){
                if(!$this->getValue("comment_enable_like") || !isset($data["uid"])){
                    return $this->handleResponse(false, array("error" => "snicker-response-011"));
                }
                return $this->rateComment($data["uid"], "like");
            }

            // Dislike Comment
            if($type === "dislike"){
                if(!$this->getValue("comment_enable_dislike") || !isset($data["uid"])){
                    return $this->handleResponse(false, array("error" => "snicker-response-011"));
                }
                return $this->rateComment($data["uid"], "dislike");
            }
        }

        /*
         |  PLUGIN :: HANDLE BACKEND
         |  @since  0.1.0
         */
        public function handleBackend($data){
            global $login, $pages, $comments;

            // Validate
            if(!isset($data["uid"]) || !isset($data["type"])){
                return $this->handleResponse(false, array("error" => "snicker-response-004"));
            }

            // Check Rights
            if(!isset($login) || !is_a($login, "Login")){
                $login = new Login();
            }
            if($login->role() !== "admin"){
                return $this->handleResponse(false, array("error" => "snicker-response-002"));
            }

            // Delete Comment
            if($data["type"] === "delete"){
                return $this->deleteComment($data["uid"]);
            }

            // Edit Comment
            if($data["type"] === "edit"){
                return $this->editComment($data["uid"], $data["comment"]);
            }

            // Change Comment Type
            return $this->changeCommentType($data["uid"], $data["type"]);
        }

        /*
         |  PLUGIN :: HANDLE CONFIG
         |  @since  0.1.0
         */
        public function handleConfig($data){
            global $login, $security;
            $login = new Login();

            // Validate Call
            if(!$login->isLogged() || $login->role() !== "admin"){
                return $this->handleResponse(false, array("error" => "snicker-response-002"));
            }

            // Loop Configuration
            $config = array();
            $checkboxes = array(
                "comment_markup_html", "comment_markup_markdown", "comment_enable_like",
                "comment_enable_dislike", "frontend_ajax", "subscription"
            );
            foreach($this->dbFields AS $key => $value){
                if(isset($data[$key])){
                    if(in_array($key, $checkboxes)){
                        $config[$key] = $data[$key] === "true";
                    } else {
                        $config[$key] = $data[$key];
                    }
                } else if(in_array($key, $checkboxes)){
                    $config[$key] = false;
                } else {
                    $config[$key] = "";
                }
            }

            // Validate Data
            if(!in_array($config["moderation"], array("each", "pass"))){
                $config["moderation"] = "each";
            }
            if(!in_array($config["comment_title"], array("optional", "required", "disabled"))){
                $config["comment_title"] = "optional";
            }
            if($config["comment_limit"] < 0 || !is_numeric($config["comment_limit"])){
                $config["comment_limit"] = 0;
            }
            if($config["frontend_per_page"] < 0 || !is_numeric($config["frontend_per_page"])){
                $config["frontend_per_page"] = 0;
            }

            // Validate Filter
            $filter = array("disable", "pageBegin", "pageEnd", "siteBodyBegin", "siteBodyEnd");
            if(!in_array($config["frontend_filter"], $filter)){
                $config["frontend_filter"] = "disabled";
            }

            // Validate eMails
            if(!Valid::email($config["subscription_from"])){
                Alert::set("The eMail 'From' Address for the Subscription is invalid!", ALERT_STATUS_FAIL, "snicker-error-alert");
                $config["subscription_from"] = $this->dbFields["subscription_from"];
            } else {
                $config["subscription_from"] = Sanitize::email($config["subscription_from"]);
            }

            if(!Valid::email($config["subscription_reply"])){
                Alert::set("The eMail 'Reply' Address for the Subscription is invalid!", ALERT_STATUS_FAIL, "snicker-error-alert");
                $config["subscription_reply"] = $this->dbFields["subscription_reply"];
            } else {
                $config["subscription_reply"] = Sanitize::email($config["subscription_reply"]);
            }

            // Set and Update
            $this->db = array_merge($this->db, $config);
            $this->save();
            return $this->handleResponse(true, array("success" => "snicker-response-003"));
        }


##
##  THEMES
##
        /*
         |  THEME :: LOAD THEMES
         |  @since  0.1.0
         */
        public function loadThemes(){
            $dir = SNICKER_PATH . "themes" . DS;
            if(!is_dir($dir)){
                //@todo Error
                return false;
            }

            // Fetch Themes
            $themes = array();
            if(($handle = opendir($dir))){
                while(($theme = readdir($handle)) !== false){
                    if(!is_dir($dir . $theme) || in_array($theme, array(".", ".."))){
                        continue;
                    }
                    if(!file_exists($dir . $theme . DS . "snicker.php")){
                        continue;
                    }
                    require_once($dir . $theme . DS . "snicker.php");

                    // Load Class
                    if(!class_exists(ucFirst($theme) . "_SnickerTemplate")){
                        continue;
                    }
                    $class = ucFirst($theme) . "_SnickerTemplate";
                    $themes[$theme] = new $class();
                }
            }

            // Check Themes
            if(empty($themes)){
                //@todo Error
                return false;
            }
            $this->themes = $themes;
            return true;
        }

        /*
         |  THEME :: GET METHOD
         |  @since  0.1.0
         */
        public function getTheme(){
            if(empty($this->themes)){
                return false;
            }

            // Get Theme
            if(array_key_exists($this->getValue("frontend_template"), $this->themes)){
                return $this->themes[$this->getValue("frontend_template")];
            }
            return false;
        }

        /*
         |  THEME :: RENDER METHOD
         |  @since  0.1.0
         */
        public function renderTheme($method, $args = array()){
            if(($theme = $this->getTheme()) === false){
                //@todo Error
                return false;
            }

            // Render Theme
            ob_start();
            call_user_func_array(array($theme, $method), $args);
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }


##
##  COMMENTS
##
        /*
         |  COMMENTS :: WRITE COMMENT
         |  @since 0.1.0
         */
        public function writeComment($comment){
            global $comments, $pages, $url, $users;
            $referer = DOMAIN . $url->uri();

            // Temp
            if(!Session::started()){
                Session::start();
            }
            Session::set("snicker-comment", $comment);

            // Check Basics
            if(!isset($comment["page_key"]) || !$pages->exists($comment["page_key"])){
                return $this->handleResponse(false, array(
                    "referer"   => $referer . "#snicker-comments-form",
                    "error"     => "snicker-response-004"));
            }
            if(isset($comment["parent_uid"])){
                if(!$comments->exists($comment["parent_uid"])){
                    $comment["parent_uid"] = null;
                } else {
                    $parent = $comments->getCommentDB($comment["parent_uid"]);
                    $comment["type"] = "reply";
                    $comment["depth"] = $parent["depth"]+1;
                }
            }

            // Sanitize Terms
            if($this->getValue("frontend_terms") !== "disabled"){
                if(!isset($comment["terms"]) || $comment["terms"] !== "1"){
                    return $this->handleResponse(false, array(
                        "referer"   => $referer . "#snicker-comments-form",
                        "error"     => $this->getValue("string_error_5")
                    ));
                }
            }

            // Sanitize Title
            if($this->getValue("comment_title") === "required"){
                if(!isset($comment["title"]) || empty($comment["title"])){
                    return $this->handleResponse(false, array(
                        "referer"   => $referer . "#snicker-comments-form",
                        "error"     => $this->getValue("string_error_4")
                    ));
                }
            }
            $comment["title"] = isset($comment["title"])? Sanitize::html($comment["title"]): "";

            // Sanitize Comment
            if(!isset($comment["comment"]) || empty($comment["comment"])){
                return $this->handleResponse(false, array(
                    "referer"   => $referer . "#snicker-comments-form",
                    "error"     => $this->getValue("string_error_3")
                ));
            }
            $comment["comment"] = Sanitize::html($comment["comment"]);

            // Sanitize User
            if(isset($comment["user"]) && isset($comment["token"])){
                if(!$users->exists($comment["user"])){
                    return $this->handleResponse(false, array(
                        "referer"   => $referer . "#snicker-comments-form",
                        "error"     => $this->getValue("string_error_2")
                    ));
                }
                $user = new User($comment["user"]);

                if(md5($user->tokenAuth()) !== $comment["token"]){
                    return $this->handleResponse(false, array(
                        "referer"   => $referer . "#snicker-comments-form",
                        "error"     => $this->getValue("string_error_2")
                    ));
                }
                unset($comment["user"], $comment["token"]);

                $comment["uuid"] = "bludit";
                $comment["status"] = "approved";
                $comment["username"] = $user->username();
                $comment["email"] = null;
            } else if(isset($comment["username"]) && isset($comment["email"])){
                if(!Valid::email($comment["email"])){
                    return $this->handleResponse(false, array(
                        "referer"   => $referer . "#snicker-comments-form",
                        "error"     => $this->getValue("string_error_2")
                    ));
                }
                $comment["uuid"] = null;
                $comment["status"] = "pending";
                $comment["username"] = Sanitize::html(strip_tags($comment["username"]));
                $comment["email"] = Sanitize::email($comment["email"]);
            } else {
                return $this->handleResponse(false, array(
                    "referer"   => $referer . "#snicker-comments-form",
                    "error"     => $this->getValue("string_error_2")
                ));;
            }

            // Sanitize Data
            $comment["like"] = 0;
            $comment["dislike"] = 0;
            $comment["subscribe"] = isset($comment["subscribe"]);

            // Check
            if(($uid = $comments->add($comment)) === false){
                return $this->handleResponse(false, array(
                    "referer"   => $referer . "#snicker-comments-form",
                    "error"     => $this->getValue("string_error_1")
                ));
            }

            Session::set("snicker-comment", null);
            return $this->handleResponse(true, array(
                "referer"   => $referer . "#comment-" . $uid,
                "success"   => $this->getValue("string_success_" . ((int) $comment["subscribe"] + 1)),
                "comment"   => $this->renderTheme("comment", array(new Comment($uid), $uid))
            ));
        }

        /*
         |  COMMENTS :: EDIT COMMENT
         |  @since 0.1.0
         */
        public function editComment($uid, $comment){
            global $comments, $pages;

            // Check Basics
            if(!$comments->exists($uid)){
                return $this->handleResponse(false, array("error" => "snicker-response-012"));
            }
            $data = new Comment($uid);

            // Sanitize Title
            if($this->getValue("comment_title") === "required"){
                if(!isset($comment["title"]) || empty($comment["title"])){
                    return $this->handleResponse(false, array("error" => "snicker-response-017"));
                }
            }
            $comment["title"] = isset($comment["title"])? Sanitize::html($comment["title"]): "";

            // Sanitize Comment
            if(!isset($comment["comment"]) || empty($comment["comment"])){
                return $this->handleResponse(false, array("error" => "snicker-response-017"));
            }
            $comment["comment"] = Sanitize::html($comment["comment"]);

            // Sanitize User
            if(isset($comment["user"]) && isset($comment["token"])){
                if(!$users->exists($comment["user"])){
                    return $this->handleResponse(false, array("error" => "snicker-response-018"));
                }
                $user = new User($comment["user"]);

                if(md5($user->tokenAuth()) !== $comment["token"]){
                    return $this->handleResponse(false, array("error" => "snicker-response-018"));
                }
                unset($comment["user"], $comment["token"]);

                $comment["uuid"] = "bludit";
                $comment["status"] = "approved";
                $comment["username"] = $user->username();
                $comment["email"] = null;
            } else if(isset($comment["username"]) && isset($comment["email"])){
                if(!Valid::email($comment["email"])){
                    return $this->handleResponse(false, array("error" => "snicker-response-018"));
                }
                $comment["uuid"] = null;
                $comment["status"] = "pending";
                $comment["username"] = Sanitize::html(strip_tags($comment["username"]));
                $comment["email"] = Sanitize::email($comment["email"]);
            }

            // Check
            if(!$comments->edit($uid, $comment)){
                return $this->handleResponse(false, array("error" => "snicker-response-014"));
            }
            return $this->handleResponse(true, array("success" => "snicker-response-009"));
        }

        /*
         |  COMMENTS :: CHANGE COMMENT TYPE
         |  @since 0.1.0
         */
        public function changeCommentType($uid, $type){
            global $comments, $login;

            // Check Parameters
            if(!$comments->exists($uid)){
                return $this->handleResponse(false, array("error" => "snicker-response-012"));
            }
            if(!in_array($type, array("pending", "rejected", "approved", "spam"))){
                return $this->handleResponse(false, array("error" => "snicker-response-013"));
            }

            // Check Rights
            if(!isset($login) || !is_a($login, "Login")){
                $login = new Login();
            }
            if($login->role() !== "admin"){
                return $this->handleResponse(false, array("error" => "snicker-response-002"));
            }

            // Change Comment
            $comment = $comments->getCommentDB($uid);
            if($comment["status"] === $type){
                return $this->handleResponse(true, array("success" => "snicker-response-016"));
            }

            $comment["status"] = $type;
            if(!$comments->edit($uid, $comment)){
                return $this->handleResponse(false, array("error" => "snicker-response-014"));
            }
            return $this->handleResponse(true, array("error" => "snicker-response-015"));
        }

        /*
         |  COMMENTS :: RATE COMMENT
         |  @since 0.1.0
         */
        public function rateComment($uid, $type = "like"){
            global $comments, $login, $url;
            $referer = DOMAIN . $url->uri();

            // Check Comment
            if(!$comments->exists($uid)){
                return $this->handleResponse(false, array(
                    "referer"   => $referer . "#snicker-comments-form",
                    "error"     => $this->getValue("string_error_1")
                ));
            }
            $comment = new Comment($uid);

            // Check Session
            if(!Session::started()){
                Session::start();
            }
            $rating = $comment->rating();

            // Has already rated?
            if(($rate = Session::get("snicker-ratings")) !== false){
                $rate = json_decode($rate, true);

                if(array_key_exists($uid, $rate)){
                    if($rate[$uid] === $type){
                        return $this->handleResponse(false, array(
                            "referer"   => $referer . "#comment-" . $comment->uid(),
                            "error"     => $this->getValue("string_error_7"),
                            "rating"    => $rating
                        ));
                    } else {
                        $rating[($rate[$uid] === "like"? 0: 1)]--;
                        unset($rate[$uid]);
                    }
                }
            }

            // Handle
            $rating[($type === "like"? 0: 1)]++;
            if(($uid = $comments->edit($uid, array("rating" => $rating))) === false){
                return $this->handleResponse(false, array(
                    "referer"   => $referer . "#comment-" . $comment->uid(),
                    "error"     => $this->getValue("string_error_1"),
                    "rating"    => $rating
                ));
            }

            // Update and Return
            $rate[$uid] = $type;
            Session::set("snicker-ratings", json_encode($rate));
            return $this->handleResponse(true, array(
                "referer"   => $referer . "#comment-" . $comment->uid(),
                "success"   => $this->getValue("string_success_3"),
                "rating"    => $rating
            ));
        }

        /*
         |  COMMENTS :: DELETE COMMENT
         |  @since 0.1.0
         */
        public function deleteComment($uid){
            global $comments, $login;

            // Check Comment
            if(!$comments->exists($uid)){
                return false;
            }
            $comment = new Comment($uid);

            // Check Rights
            if(!isset($login) || !is_a($login, "Login")){
                $login = new Login();
            }
            if($login->role() !== "admin" && $login->username() !== $comment->getValue("username")){
                return false;
            }

            // Delete Comment
            if(!$comments->delete($uid)){
                return false;
            }
            return true;
        }

        /*
         |  COMMENTS :: RENDER COMMENTS SECTION
         |  @since 0.1.0
         */
        public function renderComments(){
            global $page, $comments;

            // Get Temp
            if(!Session::started()){
                Session::start();
            }
            $data = Session::get("snicker-comment");
            $limit = $this->getValue("frontend_per_page");
            $count = $comments->count("approved", $page->key());

            // Fetch Data
            if(is_array($data)){
                $user = isset($data["username"])? $data["username"]: "";
                $mail = isset($data["email"])? $data["email"]: "";
                $title = isset($data["title"])? $data["title"]: "";
                $comment = isset($data["comment"])? $data["comment"]: "";
            } else {
                $user = $mail = $title = $comment = "";
            }
            if($this->getValue("comment_title") === "disabled"){
                $title = false;
            }

            // Render Form
            if($page->allowComments()){
                ?><div id="snicker-comments-form" class="snicker-comments"><?php
                print($this->renderTheme("form", array($user, $mail, $title, $comment)));
                ?></div><?php
            }

            // Render Comment List
            $max = ceil($count / $limit);
            if(isset($_GET["cpage"]) && $_GET["cpage"] > 1){
                $num = ($_GET["cpage"] < $max)? $_GET["cpage"]: $max;
            } else {
                $num = 1;
            }
            $list = $comments->getList($num, $limit, "approved", $page->key());

            ?><div id="snicker-comments-list" class="snicker-comments-list"><?php
            if(count($list) < 1){
                // empty
            } else {
                if($count > $limit){
                    print($this->renderTheme("pagination", array("top", $num, $limit, $count)));
                }
                foreach($list AS $key){
                    $comment = new Comment($key);
                    print($this->renderTheme("comment", array($comment, $key)));
                }
                if($count > $limit){
                    print($this->renderTheme("pagination", array("bottom", $num, $limit, $count)));
                }
            }
            ?></div><?php
        }


##
##  BACKEND
##

        /*
         |  HOOK :: INIT ADMINISTRATION
         |  @since  0.1.0
         */
        public function beforeAdminLoad(){
            global $url;

            //  Check if the current View is the "snicker"
            if(strpos($url->slug(), "snicker") !== 0){
                return false;
            }
            checkRole(array("admin"));

            // Set Backend View
            $split = str_replace("snicker", "", trim($url->slug(), "/"));
            if(!empty($split) && $split !== "/" && isset($_GET["uid"])){
                $this->backendView = "edit";
            } else {
                $this->backendView = "index";
            }
        }

        /*
         |  HOOK :: LOAD ADMINISTRATION FILES
         |  @since  0.1.0
         */
        public function adminHead(){
            $css = SNICKER_DOMAIN . "admin/css/";
            ob_start();
            ?>
                <link type="text/css" rel="stylesheet" href="<?php echo $css; ?>admin.snicker.css" />
            <?php
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }

        /*
         |  HOOK :: BEFORE ADMIN CONTENT
         |  @info   Fetch the HTML content, to inject the paw.designer page.
         |  @since  0.1.0
         */
        public function adminBodyBegin(){
            if(!$this->backend || !$this->backendView){
                return false;
            }
            ob_start();
        }

        /*
         |  HOOK :: AFTER ADMIN CONTENT
         |  @info   Handle the HTML content, to inject the paw.designer page.
         |  @since  0.1.0
         */
        public function adminBodyEnd(){
            global $snicker;
            if(!$this->backend || !$this->backendView){
                return false;
            }
            $content = ob_get_contents();
            ob_end_clean();

            // Snicker Admin Content
            ob_start();
            require("admin/{$this->backendView}.php");
            $add = ob_get_contents();
            ob_end_clean();

            // Inject Code
            $regexp = "#(\<div class=\"col-lg-10 pt-3 pb-1 h-100\"\>)(.*?)(\<\/div\>)#s";
            $content = preg_replace($regexp, "$1{$add}$3", $content);
            print($content);
        }

        /*
         |  HOOK :: SHOW SIDEBAR MENU
         |  @since  0.1.0
         */
        public function adminSidebar(){
            global $comments;

            $count = count($comments->getPendingDB(false));
            if($count > 99){
                $count = "99+";
            }

            ob_start();
                ?>
                    <a href="<?php echo HTML_PATH_ADMIN_ROOT; ?>snicker" class="nav-link" style="white-space: nowrap;">
                        <span class="oi oi-comment-square"></span>Snicker Comments
                        <?php if(!empty($count)){ ?>
                            <span class="badge badge-success badge-pill"><?php echo $count; ?></span>
                        <?php } ?>
                    </a>
                <?php
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }


##
##  FRONTEND
##

        /*
         |  HOOK :: BEFOTE FRONTEND LOAD
         |  @since  0.1.0
         */
        public function beforeSiteLoad(){
            global $security;

            // Start Session
            if(!Session::started()){
                Session::start();
            }
        }

        /*
         |  HOOK :: FRONTEND HEADER
         |  @since  0.1.0
         */
        public function siteHead(){
            if(($theme = $this->getTheme()) === false){
                //@todo Error
                return false;
            }
            if(!empty($theme::SNICKER_JS)){
                $file = SNICKER_DOMAIN . "themes/" . $this->getValue("frontend_template") . "/" . $theme::SNICKER_JS;
                ?>
                    <script type="text/javascript">
                        var SNICKER_AJAX = <?php echo $this->getValue("frontend_ajax")? "true": "false"; ?>;
                        var SNICKER_PATH = "<?php echo HTML_PATH_ADMIN_ROOT ?>snicker/ajax/";
                    </script>
                    <script id="snicker-js" type="text/javascript" src="<?php echo $file; ?>"></script>
                <?php
            }
            if(!empty($theme::SNICKER_CSS)){
                $file = SNICKER_DOMAIN . "themes/" . $this->getValue("frontend_template") . "/" . $theme::SNICKER_CSS;
                ?>
                    <link id="snicker-css" type="text/css" rel="stylesheet" href="<?php echo $file; ?>" />
                <?php
            }
        }

        /*
         |  HOOK :: FRONTEND CONTENT
         |  @since  0.1.0
         */
        public function siteBodyBegin(){
            if($this->getValue("frontend_filter") !== "siteBodyBegin"){
                return false; // owo
            }
            print($this->renderComments());
        }

        /*
         |  HOOK :: FRONTEND CONTENT
         |  @since  0.1.0
         */
        public function pageBegin(){
            if($this->getValue("frontend_filter") !== "pageBegin"){
                return false; // Owo
            }
            print($this->renderComments());
        }

        /*
         |  HOOK :: FRONTEND CONTENT
         |  @since  0.1.0
         */
        public function pageEnd(){
            if($this->getValue("frontend_filter") !== "pageEnd"){
                return false; // owO
            }
            print($this->renderComments());
        }

        /*
         |  HOOK :: FRONTEND CONTENT
         |  @since  0.1.0
         */
        public function siteBodyEnd(){
            if($this->getValue("frontend_filter") !== "siteBodyEnd"){
                return false; // OwO
            }
            print($this->renderComments());
        }
    }
