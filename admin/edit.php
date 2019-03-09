<?php
/*
 |  Snicker     A small Comment System 4 Bludit
 |  @file       ./admin/edit.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.0
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2018 - 2019 SamBrishes, pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")){ die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    global $login, $security;

    $comment = new Comment($_GET["uid"]);
    $page = new Page($comment->page_key());

?><h2 class="mt-0 mb-3">
    <span class="oi oi-comment-square" style="font-size: 0.7em;"></span> Snicker Comments / Edit
</h2>
<form method="post" action="<?php echo HTML_PATH_ADMIN_ROOT; ?>snicker">
    <div class="card" style="margin: 1.5rem 0;">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6">
                    <input type="hidden" id="tokenUser" name="tokenUser" value="<?php echo $login->username(); ?>" />
                    <input type="hidden" id="tokenCSRF" name="tokenCSRF" value="<?php echo $security->getTokenCSRF(); ?>" />
                    <input type="hidden" id="sn-action" name="action" value="snicker" />
                    <input type="hidden" id="sn-snicker" name="snicker" value="manage" />
                    <input type="hidden" id="sn-unique" name="uid" value="<?php echo $comment->uid(); ?>" />
                    <button class="btn btn-primary" name="type" value="edit">Update Comment</button>
                </div>

                <div class="col-sm-6 text-right">
                    <button class="btn btn-danger" name="type" value="delete">Delete Comment</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col">
            <input type="text" name="comment[title]" value="<?php echo $comment->title(); ?>"
                class="form-control form-control-lg" placeholder="Comment Title" />
        </div>
    </div>

    <div class="row">
        <div class="col-sm-8">
            <textarea name="comment[comment]" class="form-control" placeholder="Comment Text"
                style="min-height: 275px;"><?php echo $comment->commentRaw(); ?></textarea>
        </div>
        <div class="col-sm-4">
            <div class="card">
                <div class="card-header">Meta Settings</div>
                <div class="card-body">

                    <?php if($comment->getValue("uuid") === "bludit"){ ?>
                        <p>
                            <input type="text" value="<?php echo $comment->username(); ?>" class="form-control" disabled />
                        </p>
                        <p>
                            <input type="text" value="Registered User" class="form-control" disabled />
                        </p>
                    <?php } else { ?>
                        <p>
                            <input type="text" name="comment[username]" value="<?php echo $comment->username(); ?>"
                            class="form-control" placeholder="Comment USername" />
                        </p>
                        <p>
                            <input type="text" name="comment[email]" value="<?php echo $comment->email(); ?>"
                            class="form-control" placeholder="Comment eMail" />
                        </p>
                    <?php } ?>
                    <p>
                        <select name="comment[type]" class="custom-select">
                            <option value="pending"<?php echo ($comment->isPending())? ' selected="selected"': ''; ?>>Pending</option>
                            <option value="approved"<?php echo ($comment->isApproved())? ' selected="selected"': ''; ?>>Approved</option>
                            <option value="rejected"<?php echo ($comment->isRejected())? ' selected="selected"': ''; ?>>Rejected</option>
                            <option value="spam"<?php echo ($comment->isSpam())? ' selected="selected"': ''; ?>>Spam</option>
                        </select>
                    </p>
                </div>
            </div>

            <p class="mt-4 text-center">
                <a href="<?php echo $page->permalink(); ?>" target="_blank" class="btn btn-primary">View Page</a>
            </p>
        </div>
    </div>
</form>
