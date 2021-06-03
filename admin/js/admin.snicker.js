/*
 |  Snicker     A small Comment System 4 Bludit
 |  @file       ./admin/js/admin.snicker.js
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

    /*
     |  HELPER :: LOOP
     |  @since  0.1.0
     */
    var each = function(elements, callback){
        if(elements instanceof HTMLElement){
            callback.call(elements, elements);
        } else if(elements.length && elements.length > 0){
            for(var l = elements.length, i = 0; i < l; i++){
                callback.call(elements[i], elements[i], i);
            }
        }
    };

    // Ready?
    d.addEventListener("DOMContentLoaded", function(){
        "use strict";

        /*
         |  PRE-SELECT MENU
         |  @since  0.1.0
         */
        each(document.querySelectorAll("[data-handle='tabs']"), function(){
            var hash = window.location.hash,
                tabs = this.querySelectorAll("li > a");

            if(tabs.length > 0){
                for(var l = tabs.length, i = 0; i < l; i++){
                    if(tabs[i].getAttribute("href") == hash || (hash.length <= 1 && i == 0)){
                        tabs[i].click();
                        break;
                    }
                }
            }
        });
    });
})(window);
