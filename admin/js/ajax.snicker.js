/*
 |  Snicker     A small Comment System 4 Bludit
 |  @file       ./admin/js/ajax.snicker.js
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.0
 |
 |  @website    https://github.com/pytesNET/snicker
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2018 - 2019 SamBrishes, pytesNET <info@pytes.net>
 */
;(function(root){
    "use strict";
    var w = root, d = root.document;

    // Ready?
    d.addEventListener("DOMContentLoaded", function(){
        "use strict";

        /*
         |  PREVENT FORM SUBMIT
         */
        if(d.querySelector("form.comment-form")){
            d.querySelector("form.comment-form").addEventListener("submit", function(event){
                event.preventDefault();
            });
        }
    });
})(window);
