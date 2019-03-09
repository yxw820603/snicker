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

    class SnickerCommentsPlugin extends Plugin{
        /*
         |  BACKEND VARIABLES
         */
        private $backend = false;
        private $backendView = null;

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
                "frontend_filter"           => "pageEnd",
                "frontend_template"         => "default",
                "frontend_order"            => "date_desc",
                "frontend_per_page"         => 15,
                "frontend_ajax"             => false,
                "subscription"              => true,
                "subscription_from"         => "ticker@" . $_SERVER["SERVER_NAME"],
                "subscription_reply"        => "noreply@" . $_SERVER["SERVER_NAME"],
                "subscription_page"         => "default",

                "string_success_1"          => "Thanks for your comment!",
                "string_success_2"          => "Thanks for your comment, please confirm your subscription via the link we sent to your eMail address!",
                "string_success_3"          => "Thanks for voting this comment!",
                "string_error_1"            => "An error occured: The Username or the eMail address is missing!",
                "string_error_2"            => "An error occured: The comment text is missing!",
                "string_error_3"            => "An error occured: The comment title is missing!",
                "string_error_4"            => "An error occured: Your IP address or eMail address has been marked as Spam!",
                "string_error_5"            => "An unknown error occured, please reload the page and try it again.",
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
                if(defined("SNICKER")){
                    return true;
                }

                // Define Basics
                define("SNICKER", true);
                define("SNICKER_PATH", PATH_PLUGINS . basename(__DIR__) . "/");
                define("SNICKER_DOMAIN", DOMAIN_PLUGINS . basename(__DIR__) . "/");
                define("SNICKER_VERSION", "0.1.0");
                define("DB_SNICKER_COMMENTS", PATH_DATABASES . "comments.php");

                // Load Plugin
                require_once("system/comments.class.php");
                require_once("system/comment.class.php");
                require_once("system/snicker-template.class.php");

                // Init Plugin
                $comments = new Comments();
                $this->loadThemes();
                $this->handle();
                return true;
            }
            return false;
        }

        /*
         |  PLUGIN :: HANDLE
         |  @since  0.1.0
         */
        public function handle(){
            global $security;

            // Start Session
            if(!Session::started()){
                Session::start();
            }

            // Get Data
            if(isset($_POST["action"]) && isset($_POST["snicker"])){
                $data = $_POST;
            } else if(isset($_GET["action"]) && isset($_GET["snicker"])){
                $data = $_GET;
            } else {
                return null;
            }

            // Validate Call
            if(!isset($data["tokenCSRF"]) || !$security->validateTokenCSRF($data["tokenCSRF"])){
                return false;
            }

            // Handle Frontend
            if($data["action"] === "snicker" && $data["snicker"] === "form"){
                return $this->handleFrontend($data);
            }

            // Handle Backend
            if($data["action"] === "snicker" && $data["snicker"] === "manage"){
                return $this->handleBackend($data);
            }

            // Handle Backend
            if($data["action"] === "snicker" && $data["snicker"] === "config"){
                return $this->handleConfig($data);
            }
        }

        /*
         |  PLUGIN :: HANDLE FRONTEND
         |  @since  0.1.0
         */
        public function handleFrontend($data){
            global $pages, $comments;

            // Validate
            if(!isset($data["comment"]) || !isset($data["type"])){
                return false;
            }
            $type = $data["type"];

            // Write Comment
            if($type === "comment" || $type === "reply"){
                return $this->writeComment($data["comment"]);
            }

            // Like Comment
            if($type === "like"){
                if(!$this->getValue("comment_enable_like")){
                    return false;
                }
                return true;
            }

            // Dislike Comment
            if($type === "dislike"){
                if(!$this->getValue("comment_enable_dislike")){
                    return false;
                }
                return true;
            }

            // Unknown Action
            return false;
        }

        /*
         |  PLUGIN :: HANDLE BACKEND
         |  @since  0.1.0
         */
        public function handleBackend($data){
            global $login, $pages, $comments;

            // Validate
            if(!isset($data["key"]) || !isset($data["uid"]) || !isset($data["type"])){
                return false;
            }

            // Check Rights
            if(!isset($login) || !is_a($login, "Login")){
                $login = new Login();
            }
            if($login->role() !== "admin"){
                return false;
            }

            // Delete Comment
            if($data["type"] === "delete"){
                if(!$this->deleteComment($data["uid"])){
                    return false;
                }
                return true;
            }

            // Change Type
            if(!$this->changeCommentType($data["uid"], $data["type"])){
                return false;
            }
            return true;
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
                // @todo Error
                return false;
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

            // Validate eMail
            if(!Valid::email($config["subscription_from"])){
                Alert::set("The eMail 'From' Address for the Subscription is invalid!", 1, "error-from");
                $config["subscription_from"] = $this->dbFields["subscription_from"];
            } else {
                $config["subscription_from"] = Sanitize::email($config["subscription_from"]);
            }

            if(!Valid::email($config["subscription_reply"])){
                Alert::set("The eMail 'Reply' Address for the Subscription is invalid!", 1, "error-reply");
                $config["subscription_reply"] = $this->dbFields["subscription_reply"];
            } else {
                $config["subscription_reply"] = Sanitize::email($config["subscription_reply"]);
            }

            // Set and Update
            $this->db = array_merge($this->db, $config);
            $this->save();
            Alert::set("Settings have been stored successfully!", 0, "success");
            return Redirect::url(HTML_PATH_ADMIN_ROOT . "snicker#config");
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
            global $comments, $pages;

            // Check Data
            if(!isset($comment["page_key"]) || !$pages->exists($comment["page_key"])){
                return false;
            }

            // Check ParentID
            if(isset($comment["parent_id"])){
                if(!$comments->exists($comment["parent_id"])){
                    return false;
                }
            }

            // Sanitize Title
            if($this->getValue("comment_title") === "required"){
                if(!isset($comment["title"]) || empty($comment["title"])){
                    return false;
                }
            }
            $comment["title"] = isset($comment["title"])? Sanitize::html($comment["title"]): "";

            // Sanitize Comment
            if(!isset($comment["comment"]) || empty($comment["comment"])){
                return false;
            }
            $comment["comment"] = Sanitize::html($comment["comment"]);

            // Sanitize User
            $login = new Login();
            if($login->isLogged()){
                $comment["status"] = "approved";
                $comment["username"] = $login->username();
                $comment["email"] = null;
            } else {
                if(!isset($comment["username"]) || !isset($comment["email"])){
                    return false;
                }
                if(!Valid::email($comment["email"])){
                    return false;
                }
                $comment["username"] = Sanitize::html(strip_tags($comment["username"]));
                $comment["email"] = Sanitize::email($comment["email"]);
            }

            // Sanitize Data
            $comment["like"] = 0;
            $comment["dislike"] = 0;
            $comment["website"] = isset($comment["website"])? Sanitize::url($comment["website"]): "";
            $comment["subscribe"] = isset($comment["subscribe"]);

            // Check
            if(!$comments->add($comment)){
                return false;
            }
            return true;
        }

        /*
         |  COMMENTS :: EDIT COMMENT
         |  @since 0.1.0
         */
        public function editComment($comment){
            global $comments, $pages;

            // Check UID
            if(!isset($comment["uid"]))

            // Check Page Key
            if(!isset($comment["page_key"]) || !$pages->exists($comment["page_key"])){
                return false;
            }

            // Check ParentID
            if(isset($comment["parent_id"])){
                if(!$comments->exists("{$comment["page_key"]}/comment_{$comment["parent_id"]}")){
                    return false;
                }
            }

            // Sanitize Title
            if($this->getValue("comment_title") === "required"){
                if(!isset($comment["title"]) || empty($comment["title"])){
                    return false;
                }
            }
            $comment["title"] = isset($comment["title"])? Sanitize::html($comment["title"]): "";

            // Sanitize Comment
            if(!isset($comment["comment"]) || empty($comment["comment"])){
                return false;
            }
            $comment["comment"] = Sanitize::html($comment["comment"]);

            // Sanitize User
            $login = new Login();
            if($login->isLogged()){
                if($login->role() !== "admin" && $login->email() !== $comment["email"]){
                    return false;
                }
            }



        }

        /*
         |  COMMENTS :: CHANGE COMMENT TYPE
         |  @since 0.1.0
         */
        public function changeCommentType($uid, $type){
            global $comments, $login;

            // Check Parameters
            if(!$comments->exists($uid)){
                return false;
            }
            if(!in_array($type, array("pending", "rejected", "approved", "spam"))){
                return false;
            }

            // Check Rights
            if(!isset($login) || !is_a($login, "Login")){
                $login = new Login();
            }
            if($login->role() !== "admin"){
                return false;
            }

            // Change Comment
            $comment = $comments->getCommentDB($uid);
            if($comment["status"] === $type){
                return true;
            }

            $comment["uid"] = $uid;
            $comment["status"] = $type;
            if(!$comments->edit($comment)){
                return false;
            }
            return true;
        }

        /*
         |  COMMENTS :: LIKE COMMENT
         |  @since 0.1.0
         */
        public function likeComment($page_key, $uid){

        }

        /*
         |  COMMENTS :: DISLIKE COMMENT
         |  @since 0.1.0
         */
        public function dislikeComment($page_key, $uid){

        }

        /*
         |  COMMENTS :: DELETE COMMENT
         |  @since 0.1.0
         */
        public function deleteComment($page_key, $uid){
            global $comments, $login;

            // Check Comment
            if(!$comments->exists($uid)){
                return false;
            }

            // Check Rights
            if(!isset($login) || !is_a($login, "Login")){
                $login = new Login();
            }
            if($login->role() !== "admin"){
                return false;
            }

            // Delete Comment
            if(!$comments->delete($uid)){
                return false;
            }
            return true;
        }

        /*
         |  COMMENTS :: RENDER THEME
         |  @since 0.1.0
         */
        public function renderComments(){
            global $page, $comments;

            // Fetch Data
            $user = $mail = $title = $comment = "";
            if(isset($_POST["comment"])){
                $user = isset($_POST["comment"]["username"])? $_POST["comment"]["username"]: "";
                $mail = isset($_POST["comment"]["email"])? $_POST["comment"]["email"]: "";
                $title = isset($_POST["comment"]["title"])? $_POST["comment"]["title"]: "";
                $comment = isset($_POST["comment"]["comment"])? $_POST["comment"]["comment"]: "";
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
            $list = $comments->getPageCommentsDB($page->key());
            if(count($list) < 1){
                // empty
            } else {
                ?><div id="snicker-comments-list" class="snicker-comments-list"><?php
                foreach($list AS $key){
                    $comment = new Comment($key);
                    print($this->renderTheme("comment", array($comment, $key)));
                }
                ?></div><?php
            }
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

            if($this->getValue("frontend_ajax")){
                $file = SNICKER_DOMAIN . "/admin/js/ajax.snicker.js";
                ?>
                    <script id="snicker-ajax-js" type="text/javascript" src="<?php echo $file; ?>"></script>
                <?php
            }
            if(!empty($theme::SNICKER_JS)){
                $file = SNICKER_DOMAIN . "themes/" . $this->getValue("frontend_template") . "/" . $theme::SNICKER_JS;
                ?>
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
                return false;
            }
            print($this->renderComments());
        }

        /*
         |  HOOK :: FRONTEND CONTENT
         |  @since  0.1.0
         */
        public function pageBegin(){
            if($this->getValue("frontend_filter") !== "pageBegin"){
                return false;
            }
            print($this->renderComments());
        }

        /*
         |  HOOK :: FRONTEND CONTENT
         |  @since  0.1.0
         */
        public function pageEnd(){
            if($this->getValue("frontend_filter") !== "pageEnd"){
                return false;
            }
            print($this->renderComments());
        }

        /*
         |  HOOK :: FRONTEND CONTENT
         |  @since  0.1.0
         */
        public function siteBodyEnd(){
            if($this->getValue("frontend_filter") !== "siteBodyEnd"){
                return false;
            }
            print($this->renderComments());
        }
    }
