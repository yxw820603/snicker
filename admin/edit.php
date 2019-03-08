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

?><h2 class="mt-0 mb-3">
    <span class="oi oi-comment-square" style="font-size: 0.7em;"></span> Snicker Comments / Edit
</h2>
<form method="post" action="<?php echo HTML_PATH_ADMIN_ROOT; ?>snicker/edit">
    <div class="card" style="margin: 1.5rem 0;">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6">
                    <input type="hidden" id="tokenUser" name="tokenUser" value="<?php echo $login->username(); ?>" />
                    <input type="hidden" id="tokenCSRF" name="tokenCSRF" value="<?php echo $security->getTokenCSRF(); ?>" />
                    <input type="hidden" id="sn-action" name="action" value="snicker" />
                    <button class="btn btn-primary" name="snicker" value="config">Update Comment</button>
                </div>

                <div class="col-sm-6 text-right">
                    <button class="btn btn-danger" name="snicker" value="config">Delete Comment</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col">
            <input type="text" name="comment[title]" class="form-control form-control-lg" placeholder="Comment Title" />
        </div>
    </div>

    <div class="row">
        <div class="col-sm-8">
            <textarea name="comment[comment]" class="form-control" placeholder="Comment Text" style="min-height: 275px;"></textarea>
        </div>
        <div class="col-sm-4">
            <div class="card">
                <div class="card-header">Meta Settings</div>
                <div class="card-body">
                    <p>
                        <input type="text" name="comment[username]" class="form-control" placeholder="Comment Username" />
                    </p>
                    <p>
                        <input type="text" name="comment[email]" class="form-control" placeholder="Comment eMail" />
                    </p>
                    <p>
                        <input type="text" name="comment[website]" class="form-control" placeholder="Comment Website" />
                    </p>
                    <p>
                        <select name="comment[type]" class="custom-select">
                            <option value="rejected">Rejected</option>
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                            <option value="spam">Spam</option>
                        </select>
                    </p>
                </div>
            </div>

            <p class="mt-4 text-center">
                <a href="" target="_blank" class="btn btn-primary">View Page</a>
            </p>
        </div>
    </div>
</form>
