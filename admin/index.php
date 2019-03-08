<?php
/*
 |  Snicker     A small Comment System 4 Bludit
 |  @file       ./admin/index.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.0
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2018 - 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    global $comments, $L, $login, $security, $snicker;

    /*
     |   RENDER COMMENT TABLE
     |   @since  0.1.0
     */
    function render_snicker_table($type, $database){
        global $snicker, $security;

        // Render Empty
        if(count($database) < 1){
            ?>
                <div class="row justify-content-md-center">
                    <div class="col-sm-6">
                        <div class="card w-100 bg-light">
                            <div class="card-body text-center p-5">
                                <i>No Comments available!</i>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            return;
        }

        // Render
        $uri = DOMAIN_ADMIN . "snicker/?action=snicker&snicker=manage&key=%s&uid=%s&type=%s&tokenCSRF=%s";
        $token = $security->getTokenCSRF();
        ?>
        <table class="table mt-3">
            <?php foreach(array("thead", "tfoot") AS $tag){ ?>
                <<?php echo $tag; ?>>
                    <tr>
                        <th width="45%" class="border-0 text-uppercase text-muted">Comment</th>
                        <th width="15%" class="border-0 text-uppercase text-muted">Page</th>
                        <th width="20%" class="border-0 text-uppercase text-muted">Author</th>
                        <th width="20%" class="border-0 text-uppercase text-muted text-center">Actions</th>
                    </tr>
                </<?php echo $tag; ?>>
            <?php } ?>
            <tbody>
                <?php foreach($database AS $key){ ?>
                    <?php $comment = new Comment($key); ?>
                    <tr>
                        <td>
                            <?php
                                if($snicker->getValue("comment_title") !== "disabled"){
                                    $title = $comment->title();
                                    if(empty($title)){
                                        echo "<i class='d-inline-block mb-1'>No Comment Title available</i>";
                                    } else {
                                        echo "<b class='d-inline-block mb-1'>{$title}</b>";
                                    }
                                }

                                $content = strip_tags($comment->commentRaw());
                                if(strlen($content) > 150){
                                    $content  = substr($content, 0, 150);
                                    $content .= " [...]";
                                }
                                print("<small class='d-block'>{$content}</small>");
                            ?>
                        </td>
                        <td>
                            <?php $page = new Page($comment->page_key()); ?>
                            <a href="<?php echo $page->permalink(); ?>">View Page</a>
                        </td>
                        <td>
                            <span class="d-inline-block mb-1"><?php echo $comment->username(); ?></span>
                            <small class='d-block'><?php echo $comment->email(); ?></small>
                        </td>
                        <td class="text-center">
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-toggle="dropdown">
                                    Change
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <?php if($type !== "approved"){ ?>
                                        <a class="dropdown-item" href="<?php printf($uri, $comment->page_key(), $comment->uid(), "approved", $token); ?>">Approve Comment</a>
                                    <?php } ?>

                                    <?php if($type !== "rejected"){ ?>
                                        <a class="dropdown-item" href="<?php printf($uri, $comment->page_key(), $comment->uid(), "rejected", $token); ?>">Reject Comment</a>
                                    <?php } ?>

                                    <?php if($type !== "spam"){ ?>
                                        <a class="dropdown-item" href="<?php printf($uri, $comment->page_key(), $comment->uid(), "spam", $token); ?>">Mark as Spam</a>
                                    <?php } ?>

                                    <?php if($type !== "pending"){ ?>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="<?php printf($uri, $comment->page_key(), $comment->uid(), "pending", $token); ?>">Back to Pending</a>
                                    <?php } ?>
                                </div>
                            </div>
                            <a href="" class="btn btn-outline-primary btn-sm">Edit</a>
                            <a href="<?php printf($uri, $comment->page_key(), $comment->uid(), "delete", $token); ?>" class="btn btn-outline-danger btn-sm">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php
    }

    // Pending Counter
    $count = count($comments->getPendingDB(false));
    if($count > 99){
        $count = "99+";
    }

?><h2 class="mt-0 mb-3">
    <span class="oi oi-comment-square" style="font-size: 0.7em;"></span> Snicker Comments
</h2>

<ul class="nav nav-pills" role="tablist" data-handle="tabs">
    <li class="nav-item">
        <a class="nav-link nav-pending active" id="pending-tab" data-toggle="tab" href="#pending" role="tab">
            Pending
            <?php if(!empty($count)){ ?><span class="badge badge-primary"><?php echo $count; ?></span><?php } ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link nav-public" id="public-tab" data-toggle="tab" href="#public" role="tab">Public</a>
    </li>
    <li class="nav-item">
        <a class="nav-link nav-rejected" id="rejected-tab" data-toggle="tab" href="#rejected" role="tab">Rejected</a>
    </li>
    <li class="nav-item">
        <a class="nav-link nav-spam" id="spam-tab" data-toggle="tab" href="#spam" role="tab">Spam</a>
    </li>
    <li class="nav-item flex-grow-1"></li>
    <li class="nav-item float-right">
        <a class="nav-link" id="config-tab" data-toggle="tab" href="#config" role="tab">
            <span class="oi oi-cog"></span> Configuration
        </a>
    </li>
</ul>
<div class="tab-content">
    <div id="pending" class="tab-pane show active">
        <div class="card" style="margin: 1.5rem 0;">
            <div class="card-body">
                <form>
                    <div class="form-row align-items-center">
                        <div class="col-sm-4">
                            <input type="text" name="" value="" class="form-control" placeholder="Comment Title or Username" />
                        </div>
                        <div class="col-sm">
                            <button class="btn btn-primary" name="action" value="options">Search Comments</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php render_snicker_table("pending", $comments->getPendingDB()); ?>
    </div>

    <div id="public" class="tab-pane show">
        <div class="card" style="margin: 1.5rem 0;">
            <div class="card-body">
                <form>
                    <div class="form-row align-items-center">
                        <div class="col-sm-4">
                            <input type="text" name="" value="" class="form-control" placeholder="Comment Title or Username" />
                        </div>
                        <div class="col-sm">
                            <button class="btn btn-primary" name="action" value="options">Search Comments</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php render_snicker_table("approved", $comments->getPublicDB()); ?>
    </div>

    <div id="rejected" class="tab-pane show">
        <div class="card" style="margin: 1.5rem 0;">
            <div class="card-body">
                <form>
                    <div class="form-row align-items-center">
                        <div class="col-sm-4">
                            <input type="text" name="" value="" class="form-control" placeholder="Comment Title or Username" />
                        </div>
                        <div class="col-sm">
                            <button class="btn btn-primary" name="action" value="options">Search Comments</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php render_snicker_table("rejected", $comments->getRejectedDB()); ?>
    </div>

    <div id="spam" class="tab-pane show">
        <div class="card" style="margin: 1.5rem 0;">
            <div class="card-body">
                <form>
                    <div class="form-row align-items-center">
                        <div class="col-sm-4">
                            <input type="text" name="" value="" class="form-control" placeholder="Comment Title or Username" />
                        </div>
                        <div class="col-sm">
                            <button class="btn btn-primary" name="action" value="options">Search Comments</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php render_snicker_table("spam", $comments->getSpamDB()); ?>
    </div>

    <div id="config" class="tab-pane show">
        <form method="post" action="<?php echo HTML_PATH_ADMIN_ROOT; ?>snicker#config">
            <div class="card" style="margin: 1.5rem 0;">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <input type="hidden" id="tokenUser" name="tokenUser" value="<?php echo $login->username(); ?>" />
                            <input type="hidden" id="tokenCSRF" name="tokenCSRF" value="<?php echo $security->getTokenCSRF(); ?>" />
                            <input type="hidden" id="sn-action" name="action" value="snicker" />
                            <button class="btn btn-primary" name="snicker" value="config">Save Settings</button>
                        </div>

                        <div class="col-sm-6">

                        </div>
                    </div>
                </div>
            </div>

            <h6 class="mt-5 mb-4 pb-2 border-bottom text-uppercase">General Settings</h6>
            <div class="form-group row">
                <label for="sn-moderation" class="col-sm-3 col-form-label">Comment Moderation</label>
                <div class="col-sm-9">
                    <select id="sn-moderation" name="moderation" class="form-control custom-select">
                        <option value="each" <?php $snicker->selected("moderation", "each"); ?>>Moderate each Comment</option>
                        <option value="pass" <?php $snicker->selected("moderation", "pass"); ?>>Pass each Comment</option>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-comment-title" class="col-sm-3 col-form-label">Comment Title</label>
                <div class="col-sm-9">
                    <select id="sn-comment-title" name="comment_title" class="form-control custom-select">
                        <option value="optional" <?php $snicker->selected("comment_title", "optional"); ?>>Enable (Optional)</option>
                        <option value="required" <?php $snicker->selected("comment_title", "required"); ?>>Enable (Required)</option>
                        <option value="disabled" <?php $snicker->selected("comment_title", "disabled"); ?>>Disable</option>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-comment-limit" class="col-sm-3 col-form-label">Comment Limit</label>
                <div class="col-sm-9">
                    <input type="number" id="sn-comment-limit" name="comment_limit" value="<?php echo $snicker->getValue("comment_limit"); ?>"
                        class="form-control" min="0" placeholder="Use '0' to disable any limit!" />
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-comment-depth" class="col-sm-3 col-form-label">Comment Depth</label>
                <div class="col-sm-9">
                    <input type="number" id="sn-comment-depth" name="comment_depth" value="<?php echo $snicker->getValue("comment_depth"); ?>"
                        class="form-control" min="0" placeholder="Use '0' to disable any limit!" />
                    <small class="form-text text-muted">Use '0' to disable any limit!</small>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Comment Markup</label>
                <div class="col-sm-9">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" id="sn-markup-html" name="comment_markup_html" value="true"
                            class="custom-control-input" <?php $snicker->checked("comment_markup_html"); ?> />
                        <label class="custom-control-label" for="sn-markup-html">Allow Basic HTML</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" id="sn-markup-markdown" name="comment_markup_markdown" value="true"
                            class="custom-control-input" <?php $snicker->checked("comment_markup_markdown"); ?> />
                        <label class="custom-control-label" for="sn-markup-markdown">Allow Markdown</label>
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Comment Voting</label>
                <div class="col-sm-9">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" id="sn-like" name="comment_enable_like" value="true"
                            class="custom-control-input" <?php $snicker->checked("comment_enable_like"); ?> />
                        <label class="custom-control-label" for="sn-like">Allow to <b>Like</b> comments</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" id="sn-dislike" name="comment_enable_dislike" value="true"
                            class="custom-control-input" <?php $snicker->checked("comment_enable_dislike"); ?>/>
                        <label class="custom-control-label" for="sn-dislike">Allow to <b>Dislike</b> comments</label>
                    </div>
                </div>
            </div>

            <h6 class="mt-5 mb-4 pb-2 border-bottom text-uppercase">Frontend Settings</h6>
            <div class="form-group row">
                <label for="sn-filter" class="col-sm-3 col-form-label">Page Filter</label>
                <div class="col-sm-9">
                    <select id="sn-filter" name="frontend_filter" class="form-control custom-select">
                        <option value="disabled" <?php $snicker->selected("frontend_filter", "disabled"); ?>>Disable Page Filter</option>
                        <option value="pageBegin" <?php $snicker->selected("frontend_filter", "pageBegin"); ?>>Use 'pageBegin'</option>
                        <option value="pageEnd" <?php $snicker->selected("frontend_filter", "pageEnd"); ?>>Use 'pageEnd'</option>
                        <option value="siteBodyBegin" <?php $snicker->selected("frontend_filter", "siteBodyBegin"); ?>>Use 'siteBodyBegin'</option>
                        <option value="siteBodyEnd" <?php $snicker->selected("frontend_filter", "siteBodyEnd"); ?>>Use 'siteBodyEnd'</option>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-template" class="col-sm-3 col-form-label">Comment Template</label>
                <div class="col-sm-9">
                    <select id="sn-template" name="frontend_template" class="form-control custom-select">
                        <?php
                            foreach($snicker->themes AS $key => $theme){
                                ?>
                                    <option value="<?php echo $key; ?>" <?php $snicker->selected("frontend_template", $key); ?>><?php echo $theme::SNICKER_NAME;  ?></option>
                                <?php
                            }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-order" class="col-sm-3 col-form-label">Comment Order</label>
                <div class="col-sm-9">
                    <select id="sn-order" name="frontend_order" class="form-control custom-select">
                        <option value="date_desc" <?php $snicker->selected("frontend_order", "date_desc"); ?>>Newest Comments First</option>
                        <option value="date_asc" <?php $snicker->selected("frontend_order", "date_asc"); ?>>Oldest Comments First</option>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-per-page" class="col-sm-3 col-form-label">Comments Per Page</label>
                <div class="col-sm-9">
                    <input type="number" id="sn-per-page" name="frontend_per_page" value="<?php echo $snicker->getValue("frontend_per_page"); ?>"
                        class="form-control" min="0" step="1" placheolder="Use '0' to show all available comments!" />
                    <small class="form-text text-muted">Use '0' to show all available comments!</small>
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-ajax" class="col-sm-3 col-form-label">AJAX Script</label>
                <div class="col-sm-9">
                    <select id="sn-ajax" name="frontend_ajax" class="form-control custom-select">
                        <option value="true" <?php $snicker->selected("frontend_ajax", true); ?>>Embed AJAX Script</option>
                        <option value="false" <?php $snicker->selected("frontend_ajax", false); ?>>Don't use AJAX</option>
                    </select>
                <small class="form-text text-muted">The AJAX Script hands over the request (comment, like, dislike) directly without reloading the page!</small>
                </div>
            </div>

            <h6 class="mt-5 mb-4 pb-2 border-bottom text-uppercase">Subscription Settings</h6>
            <div class="form-group row">
                <label for="sn-subscription" class="col-sm-3 col-form-label">eMail Subscription</label>
                <div class="col-sm-9">
                    <select id="sn-subscription" name="subscription" class="form-control custom-select">
                        <option value="true" <?php $snicker->selected("subscription", true); ?>>Enable</option>
                        <option value="false" <?php $snicker->selected("subscription", false); ?>>Disable</option>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-subscription-from" class="col-sm-3 col-form-label">eMail 'From' Address</label>
                <div class="col-sm-9">
                    <input type="text" id="sn-subscription-from" name="subscription_from" value="<?php echo $snicker->getValue("subscription_from"); ?>"
                        class="form-control" placeholder="eMail 'From' Address" />
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-subscription-reply" class="col-sm-3 col-form-label">eMail 'ReplyTo' Address</label>
                <div class="col-sm-9">
                    <input type="text" id="sn-subscription-reply" name="subscription_reply" value="<?php echo $snicker->getValue("subscription_reply"); ?>"
                        class="form-control" placeholder="eMail 'ReplyTo' Address" />
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-subscription-body" class="col-sm-3 col-form-label">eMail Body (Page)</label>
                <div class="col-sm-9">
                    <select id="sn-subscription-body" class="form-control custom-select">
                        <option>Use default Notification eMail</option>
                    </select>
                    <small class="form-text text-muted">Read more about a custom Notification eMails <a href="#" target="_blank">here</a>!</small>
                </div>
            </div>

            <h6 class="mt-5 mb-4 pb-2 border-bottom text-uppercase">Strings</h6>
            <div class="form-group row">
                <label for="sn-success-1" class="col-sm-3 col-form-label">Default Thanks Message</label>
                <div class="col-sm-9">
                    <input type="text" id="sn-success-1" name="string_success_1" value="<?php echo $snicker->getValue("string_success_1"); ?>"
                        class="form-control" placeholder="<?php echo $snicker->dbFields["string_success_1"]; ?>" />
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-success-2" class="col-sm-3 col-form-label">Thanks Message with Subscription</label>
                <div class="col-sm-9">
                    <input type="text" id="sn-success-2" name="string_success_2" value="<?php echo $snicker->getValue("string_success_2"); ?>"
                        class="form-control" placeholder="<?php echo $snicker->dbFields["string_success_2"]; ?>" />
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-success-3" class="col-sm-3 col-form-label">Thanks Message for Voting</label>
                <div class="col-sm-9">
                    <input type="text" id="sn-success-3" name="string_success_3" value="<?php echo $snicker->getValue("string_success_3"); ?>"
                        class="form-control" placeholder="<?php echo $snicker->dbFields["string_success_3"]; ?>" />
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-error-1" class="col-sm-3 col-form-label">Error: Username or eMail is missing</label>
                <div class="col-sm-9">
                    <input type="text" id="sn-error-1" name="string_error_1" value="<?php echo $snicker->getValue("string_error_1"); ?>"
                        class="form-control" placeholder="<?php echo $snicker->dbFields["string_error_1"]; ?>" />
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-error-2" class="col-sm-3 col-form-label">Error: Comment Text is missing</label>
                <div class="col-sm-9">
                    <input type="text" id="sn-error-2" name="string_error_2" value="<?php echo $snicker->getValue("string_error_2"); ?>"
                        class="form-control" placeholder="<?php echo $snicker->dbFields["string_error_2"]; ?>" />
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-error-3" class="col-sm-3 col-form-label">Error: Comment Title is missing</label>
                <div class="col-sm-9">
                    <input type="text" id="sn-error-3" name="string_error_3" value="<?php echo $snicker->getValue("string_error_3"); ?>"
                        class="form-control" placeholder="<?php echo $snicker->dbFields["string_error_3"]; ?>" />
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-error-4" class="col-sm-3 col-form-label">Error: Marked as SPAM</label>
                <div class="col-sm-9">
                    <input type="text" id="sn-error-4" name="string_error_4" value="<?php echo $snicker->getValue("string_error_4"); ?>"
                        class="form-control" placeholder="<?php echo $snicker->dbFields["string_error_4"]; ?>" />
                </div>
            </div>

            <div class="form-group row">
                <label for="sn-error-5" class="col-sm-3 col-form-label">Error: Unknown Error, Try again</label>
                <div class="col-sm-9">
                    <input type="text" id="sn-error-5" name="string_error_5" value="<?php echo $snicker->getValue("string_error_5"); ?>"
                        class="form-control" placeholder="<?php echo $snicker->dbFields["string_error_5"]; ?>" />
                </div>
            </div>

            <div class="card mt-5 mb-4">
                <div class="card-body">
                    <button class="btn btn-primary" name="action" value="save">Save Settings</button>
                </div>
            </div>
        </form>
    </div>
</div>
