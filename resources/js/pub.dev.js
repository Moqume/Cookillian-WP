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
         */
        init : function() {
            var true_referrer = document.referrer || false
                , resp, default_handler;

            // Default handler for a valid response
            default_handler = function(r) {
                if (typeof r.debug !== "undefined" && typeof console === "object") {
                    // We have debug details, show it in the console if it's available
                    console.log(r);
                }

                if (typeof r.header_script !== "undefined") {
                    // "head" exists at this point (it's where we're called from), so add
                    // any extra header scripts now. Footer scripts will need to wait.
                    $("head").append(r.header_script);

                    delete r.header_script;
                }

                // Extend ourselves with the response
                $.extend(cookillian, r);
            };

            // Provide some defaults
            $.extend(this, {
                "blocked_cookies" : true,
                "implied_consent" : false,
                "opted_out"       : false,
                "opted_in"        : false,
                "is_manual"       : false,
                "has_nst"         : false
            });

            if (!cookillian.use_async_ajax) {
                // Synchronous AJAX call
                resp = cookillian.getAjaxData('init', {"true_referrer" : true_referrer});

                if (resp) {
                    default_handler(resp);
                }
            } else {
                // Asynchronous AJAX call
                cookillian.getAjaxData('init', {"true_referrer" : true_referrer}, function(r) {
                    default_handler(r);

                    // Perform post intialization now
                    cookillian.postInit();
                });
            }
        },

        /**
         * Performs post-Initialization
         */
        postInit : function() {
            // Perform when document is ready:
            $(document).ready(function($) {
                // Inject footer script, if we have any
                if (typeof cookillian.footer_script !== "undefined") {
                    $("body").append(cookillian.footer_script);

                    delete cookillian.footer_script;
                }

                // Display the alert (if needed)
                cookillian.displayAlert();

                $(document).trigger('cookillian_ready', cookillian);
            });

            // Perform now:
            $(document).trigger('cookillian_load', cookillian); // Event triggered immediately
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

        // ----------- API ----------- //

        /**
         * Deletes the cookies (user func)
         *
         * @api
         */
        deleteCookies : function() {
            return cookillian.getAjaxData('delete_cookies', true);
        },

        /**
         * Opts a visitor in
         *
         * @api
         */
        optIn : function() {
            return cookillian.getAjaxData('opt_in', true);
        },

        /**
         * Opts a visitor out
         *
         * @api
         */
        optOut : function() {
            return cookillian.getAjaxData('opt_out', true);
        },

        /**
         * Resets the user's choice of opt in or out
         *
         * @api
         */
        resetOptinout : function() {
            return cookillian.getAjaxData('reset_optinout', true);
        },

        /**
         * Inserts an arbitrary string, depending on the value
         *
         * @api
         * @param string where Selector where to insert the string
         * @param string true_string String to write when tf_value is true
         * @param string false_string String to write when tf_value is false (optional)
         * @param string|bool tf_value If a string, compares against Cookillian variables ("blocked_cookes" by default), otherwise a simple true/false trigger
         */
        insertString : function(where, true_string, false_string, tf_value) {
            var selector = $(where);

            // Return if there's no valid selector
            if (!selector.length) {
                return;
            }

            // Set a default value to check against
            if (typeof tf_value === "undefined") {
                tf_value = "blocked_cookies";
            }

            $(document).on("cookillian_ready", function() {
                if (typeof tf_value === "string") {
                    tf_value = Boolean(cookillian[tf_value]);
                }

                if (tf_value) {
                    // True. Append true_string, if there's one
                    if (true_string) {
                        $(selector).append(true_string);
                    }
                } else if (false_string) {
                    // False and there's a false_string to append
                    $(selector).append(false_string);
                }
            });
        }
    });

    // ! Initialize Cookillian ASAP !
    cookillian.init();

    if (!cookillian.use_async_ajax) {
        // Perform post initialization if we're not using asynchronous AJAX
        cookillian.postInit();
    }
}(jQuery));
