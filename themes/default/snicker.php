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
            global $page, $login, $security;

            $login = new Login();
            if(empty($security->getTokenCSRF())){
                $security->generateTokenCSRF();
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
                        <header>
                            <div class="inner">
                                Logged in as ?php echo <?php echo $login->username(); ?>
                            </div>
                        </header>
                    <?php } ?>

                    <article>
                        <?php if($title !== false){ ?>
                            <p>
                                <input type="text" id="comment-title" name="comment[title]" value="" placeholder="Comment Title" />
                            </p>
                        <?php } ?>
                        <p>
                            <textarea id="comment-text" name="comment[comment]" placeholder="Your Comment..."><?php echo $message; ?></textarea>
                        </p>
                    </article>

                    <footer>
                        <div class="aside aside-left">
                            <input type="checkbox" id="comment-subscribe" name="comment[subscribe]" value="1" /><label for="comment-subscribe">Subscribe via eMail</label>
                        </div>
                        <div class="aside aside-right">
                            <input type="hidden" name="tokenCSRF" value="<?php echo $security->getTokenCSRF(); ?>" />
                            <input type="hidden" name="comment[page_key]" value="<?php echo $page->key(); ?>" />
                            <input type="hidden" name="action" value="snicker" />
                            <input type="hidden" name="snicker" value="form" />
                            <button name="type" value="comment">Comment</button>
                        </div>
                    </footer>
                </form>
            <?php
        }

        /*
         |  RENDER :: COMMENT
         |  @since  0.1.0
         */
        public function comment($comment, $key){
            global $page, $users, $security, $snicker;

            // Check User
            $user = $users->exists($comment->username());
            if($user && $comment->getValue("email") === "*"){
                $user = new User($comment->username());
            }

            // Render
            $token = $security->getTokenCSRF();
            $depth = (int) $snicker->getValue("comment_depth");
            $url = $page->permalink() . "?action=snicker&snicker=form&key=%s&uid=%s&token=%s";
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
                                <span class="meta-author">Written by <?php echo $comment->username(); ?></span>
                                <span class="meta-date">on <?php echo $comment->date(); ?></span>
                            </div>
                            <div class="comment-comment">
                                <?php echo $comment->comment(); ?>
                            </div>
                            <div class="comment-action">
                                <div class="action-left">
                                    <?php if($snicker->getValue("comment_enable_like")){ ?>
                                        <a href="<?php echo $url; ?>&type=like" class="action-like">
                                            Like <span><?php echo $comment->like(); ?></span>
                                        </a>
                                    <?php } ?>
                                    <?php if($snicker->getValue("comment_enable_dislike")){ ?>
                                        <a href="<?php echo $url; ?>&type=dislike" class="action-dislike">
                                            Dislike <span><?php echo $comment->dislike(); ?></span>
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
         |  HELPER :: GRAVATAR
         |  @since  0.1.0
         */
        protected function gravatar($email){
            $hash = md5(strtolower(trim($email)));
            return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=125";
        }
    }
