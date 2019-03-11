<?php
/*
 |  Snicker     A small Comment System 4 Bludit
 |  @file       ./system/themes/default/snicker.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.0
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2018 - 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    class Default_SnickerTemplate extends SnickerTemplate{
        const SNICKER_NAME = "Default Theme";

        const SNICKER_JS = "snicker.js";
        const SNICKER_CSS = "snicker.css";

        /*
         |  RENDER :: COMMENT FORM
         |  @since  0.1.0
         */
        public function form($username = "", $email = "", $title = "", $message = ""){
            global $comments, $login, $page, $security, $snicker;

            $login = new Login();
            if(empty($security->getTokenCSRF())){
                $security->generateTokenCSRF();
            }

            $reply = isset($_GET["snicker"]) && $_GET["snicker"] == "reply";
            if($reply && isset($_GET["uid"]) && $comments->exists($_GET["uid"])){
                $reply = new Comment($_GET["uid"]);
            }
            ?>
                <form class="comment-form" method="post" action="<?php echo $page->permalink(); ?>?snicker=comment#snicker">
                    <?php if(!$login->isLogged()){ ?>
                        <header>
                            <div class="aside aside-left">
                                <input type="text" id="comment-user" name="comment[username]" value="<?php echo $username; ?>" placeholder="Your Username" />
                            </div>
                            <div class="aside aside-right">
                                <input type="email" id="comment-mail" name="comment[email]" value="<?php echo $email; ?>" placeholder="Your eMail Address" />
                            </div>
                        </header>
                    <?php } else { ?>
                        <?php $user = new User($login->username()); ?>

                        <header>
                            <input type="hidden" id="comment-user" name="comment[user]" value="<?php echo $user->username(); ?>" />
                            <input type="hidden" id="comment-token" name="comment[token]" value="<?php echo md5($user->tokenAuth()); ?>" />
                            <div class="inner">
                                Logged in as <?php echo $user->nickname(); ?>
                            </div>
                        </header>
                    <?php } ?>

                    <article>
                        <?php if(Alert::get("snicker-alert") !== false){ ?>
                            <div class="comment-alert alert-error">
                                <?php Alert::p("snicker-alert"); ?>
                            </div>
                        <?php } else if(Alert::get("snicker-success") !== false){ ?>
                            <div class="comment-alert alert-success">
                                <?php Alert::p("snicker-success"); ?>
                            </div>
                        <?php } ?>

                        <?php if($title !== false){ ?>
                            <p>
                                <input type="text" id="comment-title" name="comment[title]" value="<?php echo $title; ?>" placeholder="Comment Title" />
                            </p>
                        <?php } ?>
                        <p>
                            <textarea id="comment-text" name="comment[comment]" placeholder="Your Comment..."><?php echo $message; ?></textarea>
                        </p>

                        <?php if(is_a($reply, "Comment")){ ?>
                            <div class="comment-reply">
                                <a href="<?php echo $page->permalink(); ?>" class="reply-cancel"></a>
                                <div class="reply-title">
                                    <?php echo $reply->username(); ?> wrotes:
                                </div>
                                <div class="reply-content">
                                    <?php echo $reply->comment(); ?>
                                </div>
                            </div>
                        <?php } ?>
                    </article>

                    <footer>
                        <div class="aside aside-left">
                            <input type="checkbox" id="comment-subscribe" name="comment[subscribe]" value="1" />
                            <label for="comment-subscribe">Subscribe via eMail</label>
                        </div>
                        <div class="aside aside-right">
                            <input type="hidden" name="tokenCSRF" value="<?php echo $security->getTokenCSRF(); ?>" />
                            <input type="hidden" name="comment[page_key]" value="<?php echo $page->key(); ?>" />
                            <input type="hidden" name="action" value="snicker" />
                            <input type="hidden" name="snicker" value="form" />
                            <?php if(is_a($reply, "Comment")){ ?>
                                <input type="hidden" name="comment[parent_uid]" value="<?php echo $reply->uid(); ?>" />
                                <button name="type" value="reply" data-string="Comment">Answer</button>
                            <?php } else { ?>
                                <button name="type" value="comment" data-string="Answer">Comment</button>
                            <?php } ?>
                        </div>

                        <?php if($snicker->getValue("frontend_terms") === "default"){ ?>
                            <div class="aside aside-full terms-of-use">
                                <input type="checkbox" id="comment-terms" name="comment[terms]" value="1" />
                                <label for="comment-terms">I agree that the given data (incl. my hashed, anonymized IP address) gets stored!</label>
                            </div>
                        <?php } else if($snicker->getValue("frontend_terms") !== "disabled"){ ?>
                            <div class="aside aside-full terms-of-use">
                                <input type="checkbox" id="comment-terms" name="comment[terms]" value="1" />
                                <label for="comment-terms">I agree the <a href="" target="_blank">Terms of Use</a>!</label>
                            </div>
                        <?php } ?>
                    </footer>
                </form>
            <?php

            unset($_SESSION["s_snicker-alert"]);
            unset($_SESSION["s_snicker-success"]);
        }

        /*
         |  RENDER :: PAGINATION
         |  @since  0.1.0
         */
        public function pagination($location, $cpage, $limit, $count){
            global $url;

            // Data
            $link = DOMAIN . $url->uri() . "?cpage=%d#snicker-comments-list";
            $maxpages = (int) ceil($count / $limit);
            $prev = ($cpage === 1)? false: $cpage - 1;
            $next = ($cpage === $maxpages)? false: $cpage + 1;

            // Top Position
            if($location === "top"){
                ?>
                    <div class="pagination pagination-top">
                        <?php if($cpage === 1){ ?>
                            <span class="pagination-button button-previous disabled">Previous Comments</span>
                        <?php } else { ?>
                            <a href="<?php printf($link, $prev); ?>" class="pagination-button button-previous">Previous Comments</a>
                        <?php } ?>

                        <?php if($cpage < $maxpages){ ?>
                            <a href="<?php printf($link, $next); ?>" class="pagination-button button-next">Next Comments</a>
                        <?php } else { ?>
                            <span class="pagination-button button-next disabled">Next Comments</span>
                        <?php } ?>
                    </div>
                <?php
            }

            // Bottom Position
            if($location === "bottom"){
                ?>
                    <div class="pagination pagination-bottom">
                    <div class="pagination-inner">
                        <?php if($prev === false){ ?>
                            <span class="pagination-button button-first disabled">&laquo;</span>
                            <span class="pagination-button button-previous disabled">&lsaquo;</span>
                        <?php } else { ?>
                            <a href="<?php printf($link, 1); ?>" class="pagination-button button-first">&laquo;</a>
                            <a href="<?php printf($link, $prev); ?>" class="pagination-button button-previous">&lsaquo;</a>
                        <?php } ?>

                        <?php
                            if($maxpages < 6){
                                $start = 1;
                                $stop = $maxpages;
                            } else {
                                $start = ($cpage > 3)? $cpage - 3: $cpage;
                                $stop = ($cpage + 3 > $maxpages)? $cpage + 3: $maxpages;
                            }

                            if($start > 1){
                                ?><span class="pagination-separator">...</span><?php
                            }
                            for($i = $start; $i <= $stop; $i++){
                                $active = ($i == $cpage)? "active": "";
                                ?>
                                    <a href="<?php printf($link, $i); ?>" class="pagination-button button-number <?php echo $active; ?>"><?php echo $i; ?></a>
                                <?php
                            }
                            if($stop < $maxpages){
                                ?><span class="pagination-separator">...</span><?php
                            }
                        ?>

                        <?php if($next !== false){ ?>
                            <a href="<?php printf($link, $next); ?>" class="pagination-button button-next">&rsaquo;</a>
                            <a href="<?php printf($link, $maxpages); ?>" class="pagination-button button-last">&raquo;</a>
                        <?php } else { ?>
                            <span class="pagination-button button-next disabled">&rsaquo;</span>
                            <span class="pagination-button button-last disabled">&raquo;</span>
                        <?php } ?>
                    </div>
                    </div>
                <?php
            }
        }

        /*
         |  RENDER :: COMMENT
         |  @since  0.1.0
         */
        public function comment($comment, $key){
            global $users, $security, $snicker;

            // Get Page
            $page = new Page($comment->page_key());

            // Check User
            $user = $users->exists($comment->getValue("username"));
            if($user && $comment->getValue("uuid") === "bludit"){
                $user = new User($comment->getValue("username"));
            }

            // Render
            $token = $security->getTokenCSRF();
            $depth = (int) $snicker->getValue("comment_depth");
            $url = $page->permalink() . "?action=snicker&snicker=form&page_key=%s&uid=%s&tokenCSRF=%s";
            $url = sprintf($url, $comment->page_key(), $comment->uid(), $token);
            ?>
                <div id="comment-<?php echo $comment->uid(); ?>" class="comment">
                    <div class="comment-inner">
                        <div class="comment-avatar">
                            <img src="<?php echo $this->gravatar($comment->email()); ?>" alt="<?php echo $comment->username(); ?>" />
                            <?php
                                if($user && $user->username() === $page->username()){
                                    echo '<span class="avatar-role">Author</span>';
                                } else if($user && $user->role() === "admin"){
                                    echo '<span class="avatar-role">Admin</span>';
                                }
                            ?>
                        </div>

                        <div class="comment-content">
                            <?php if($snicker->getValue("comment_title") !== "disabled" && !empty($comment->title())){ ?>
                                <div class="comment-title"><?php echo $comment->title(); ?></div>
                            <?php } ?>
                            <div class="comment-meta">
                                <span class="meta-author">Written by <span class="author-username"><?php echo $comment->username(); ?></span></span>
                                <span class="meta-date">on <?php echo $comment->date(); ?></span>
                            </div>
                            <div class="comment-comment">
                                <?php echo $comment->comment(); ?>
                            </div>
                            <div class="comment-action">
                                <div class="action-left">
                                    <?php if($snicker->getValue("comment_enable_like")){ ?>
                                        <a href="<?php echo $url; ?>&type=like" class="action-like <?php echo ($this->hasLiked($comment->uid())? "active": ""); ?>">
                                            Like <span data-snicker="like"><?php echo $comment->like(); ?></span>
                                        </a>
                                    <?php } ?>
                                    <?php if($snicker->getValue("comment_enable_dislike")){ ?>
                                        <a href="<?php echo $url; ?>&type=dislike" class="action-dislike <?php echo ($this->hasDisliked($comment->uid())? "active": ""); ?>">
                                            Dislike <span data-snicker="dislike"><?php echo $comment->dislike(); ?></span>
                                        </a>
                                    <?php } ?>
                                </div>
                                <div class="action-right">
                                    <?php if($depth === 0 || $depth > $comment->depth()){ ?>
                                        <a href="<?php echo $page->permalink(); ?>?snicker=reply&uid=<?php echo $comment->key(); ?>#snicker-comments-form" class="action-reply">Reply</a>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
        }

        /*
         |  HELPER :: HAS LIKED
         |  @since  0.1.0
         */
        protected function hasLiked($uid){
            if(($rate = Session::get("snicker-ratings")) === false){
                return false;
            }

            // Check Comment UID
            $rate = json_decode($rate, true);
            if(!array_key_exists($uid, $rate)){
                return false;
            }
            return $rate[$uid] === "like";
        }

        /*
         |  HELPER :: HAS DISLIKED
         |  @since  0.1.0
         */
        protected function hasDisliked($uid){
            if(($rate = Session::get("snicker-ratings")) === false){
                return false;
            }

            // Check Comment UID
            $rate = json_decode($rate, true);
            if(!array_key_exists($uid, $rate)){
                return false;
            }
            return $rate[$uid] === "dislike";
        }

        /*
         |  HELPER :: GRAVATAR
         |  @since  0.1.0
         */
        protected function gravatar($email){
            $hash = md5(strtolower(trim($email)));
            return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=125";
        }
    }
