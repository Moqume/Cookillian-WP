/*!
 * Copyright (c) 2012 Mike Green <myatus@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

if (typeof cookillian === "undefined") {
    var cookillian = {};
}

(function($){
    $.extend(cookillian, {
        /**
         * This is a simple wrapper for calling an Ajax function and obtaining its response
         *
         * @param string ajaxFunc The Ajax function to perform
         * @param mixed ajaxData the data to send along with the function
         * @param function callback A callback to trigger on an asynchronous call
         * @return mixed Returns the response from the Ajax function, or `false` if there was an error
         */
        getAjaxData : function(func, data, callback) {
            var has_callback = (typeof callback === "function"), resp = false;

            $.ajax({
                type     : 'POST',
                dataType : 'json',
                url      : cookillian_ajax.url,
                timeout  : 5000,
                async    : has_callback,
                data     : { "action": cookillian_ajax.action, "func": func, "data": data, "_ajax_nonce": cookillian_ajax.nonce },
                success  : function(ajax_resp) {
                    if (ajax_resp.nonce === cookillian_ajax.nonceresponse && ajax_resp.stat === 'ok') {
                        resp = ajax_resp.data;

                        if (has_callback) {
                            callback(resp);
                        }
                    }
                }
            });

            return resp;
        },

        /**
         * Initializes Cookillian
         *
         * Note: the AJAX call is NOT asynchronous because we need to keep the execution order
         */
        init : function() {
            var true_referer = (document.referer) ? document.referer : false
                , resp = cookillian.getAjaxData('init', {"true_referer" : true_referer});

            if (resp) {
                // We have a response
                if (typeof resp.debug !== "undefined" && typeof console === "object") {
                    // We have debug details, show it in the console if it's available
                    console.log(resp);
                }

                if (typeof resp.header_script !== "undefined") {
                    // "head" exists at this point (it's where we're called from), so add
                    // any extra header scripts now. Footer scripts will need to wait.
                    $("head").append(resp.header_script);

                    delete resp.header_script;
                }

                // And extend ourselves with the response
                $.extend(this, resp);
            } else {
                // Something went wrong, provide some defaults
                $.extend(this, {
                    "blocked_cookies" : true,
                    "opted_out"       : false,
                    "opted_in"        : false,
                    "is_manual"       : false,
                    "has_nst"         : false,
                });
            }
        },

        /**
         * Displays the cookie alert to the visitor
         */
        displayAlert : function() {
            var cookillian_alert = $(".cookillian-alert")
                , do_show = (cookillian.blocked_cookies && !cookillian.opted_out);

            if (!cookillian_alert.length) {
                return; // Nothing to do!
            }

            // Bind a click event to the "X" (close) button
            $('.close', cookillian_alert).click(function(e) {
               cookillian_alert.fadeOut('slow');
               e.preventDefault();
            });

            // Show the alert if needed
            if (do_show) {
                if (!cookillian.is_manual) {
                    // We have added the alert automatically, so move it from where it was inserted
                    // to the top of the content
                    cookillian_alert.detach().prependTo("body").fadeIn("slow");
                } else {
                    // The plugin admin decided where to add the alert, so we just make sure it's shown now
                    cookillian_alert.show();
                }

                // Give some feedback to the plugin that we decided to display an alert (and force it as async)
                if ((typeof cookillian.debug === "undefined" || !cookillian.debug.logged_in) && !cookillian.has_nst) {
                    cookillian.getAjaxData('displayed', true, function() {});
                }
            }
        },

        /**
         * Deletes the cookies (user func)
         */
        deleteCookies : function() {
            return cookillian.getAjaxData('delete_cookies', true);
        },

        /**
         * Opts a visitor in
         */
        optIn : function() {
            return cookillian.getAjaxData('opt_in', true);
        },

        /**
         * Opts a visitor out
         */
        optOut : function() {
            return cookillian.getAjaxData('opt_out', true);
        },

        /**
         * Resets the user's choice of opt in or out
         */
        resetOptinout : function() {
            return cookillian.getAjaxData('reset_optinout', true);
        }
    });

    // ! Initialize Cookillian *before* the document is ready !
    cookillian.init();

    $(document).ready(function($){
        // Inject footer script, if we have any
        if (typeof cookillian.footer_script !== "undefined") {
            $("body").append(cookillian.footer_script);

            delete cookillian.footer_script;
        }

        // Display the alert (if needed)
        cookillian.displayAlert();
    });
}(jQuery));
