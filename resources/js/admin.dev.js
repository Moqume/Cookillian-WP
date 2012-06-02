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
    if (!$.isFunction($.fn.showHide)) {
        $.fn.showHide = function(showOrHide){
            var element = $(this[0]);

            if (showOrHide) {
                element.fadeIn("slow");
            } else {
                element.fadeOut("slow");
            }

            return this;
        };
    }

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
         * Shows/hides Custom Alert box
         */
        showHideCustomAlert : function() {
            var alert_content_type = $('input[name="alert_content_type"]:checked')
                , alert_normal     = $(".alert_normal")
                , alert_custom     = $(".alert_custom")
                , is_custom;

            if (!alert_content_type.length) {
                return;
            }

            is_custom = (alert_content_type.val() === "custom");

            alert_custom.showHide(is_custom);
            alert_normal.showHide(!is_custom);
        },

        /**
         * Shows/hides Alert Style box
         */
        showHideCustomAlertStyle : function() {
            var alert_style                = $('input[name="alert_style"]:checked')
                , alert_custom_style_extra = $(".alert_custom_style_extra");

            if (!alert_style.length) {
                return;
            }

            alert_custom_style_extra.showHide(alert_style.val() === "custom");
        },

        /**
         * Shows/hides MaxMind extras
         */
        showHideExtras : function() {
            var maxmind_settings    = $(".maxmind_settings")
                , geo_checked       = $('input[name="geo_service"]:checked');

            if (!geo_checked.length) {
                return;
            }

            maxmind_settings.showHide(geo_checked.val() === 'maxmind');
        },

        /**
         * Initializes the Settings page JS functionality
         */
        initSettingsPage : function() {
            var clear_geo_cache_btn = $("#clear_geo_cache");

            /* Highlight helper for the multi_checkbox */
            $('.multi_checkbox li').each(function() {
                var li = $(this), checkbox = $('input[type="checkbox"]', this), checked = checkbox.is(':checked');

                // Highlight now
                if (checked) {
                    li.addClass('highlighted');
                }

                // Higlight when changed
                checkbox.change(function(e) {
                    li.toggleClass('highlighted');
                });
            });

            // AJAX-ify the clear_geo_cache button
            clear_geo_cache_btn.click(function(e) {
                clear_geo_cache_btn.prop("disabled", true).css("cursor", "wait");

                cookillian.getAjaxData("clear_geo_cache", true, function() {
                    clear_geo_cache_btn.prop("disabled", false).css("cursor", "");
                });

                e.preventDefault();
            });

            /* Show or hide ... */
            $('input[name="alert_content_type"]').change(cookillian.showHideCustomAlert); cookillian.showHideCustomAlert();
            $('input[name="alert_style"]').change(cookillian.showHideCustomAlertStyle);   cookillian.showHideCustomAlertStyle();
            $('input[name="geo_service"]').change(cookillian.showHideExtras);             cookillian.showHideExtras();

            /* Show or hide debug information */
            $('#footer_debug_link').click(function() { $('#footer_debug').toggle(); return false; });
        },

        /**
         * Initializes the JS functionality on the Cookie page
         */
        initCookiePage : function() {
            var delete_buttons    = $(".cookie_table .delete-btn")
                , group_dropdowns = $(".group-dropdown")
                , cookie_table    = $(".cookie_table")
                , clone, row, rand_name, my_parent;

            /* Resize the blank portion of the cookie table according to the width of the nested table */
            if (cookie_table.length) {
                $('.col_blank', cookie_table).width(
                    cookie_table.width() - $('table', cookie_table).width()
                );
            }

            // Make the "Add new cookie" button on the Cookies page functional (it's hidden on non-JS browsers)
            $('#add_new_cookie_btn').click(function() {
                rand_name = 'new_' + Math.floor((Math.random()*99999));
                clone     = $("#row_clonable").clone(true);

                // Change ID and names
                clone.prop("id", "row_" + rand_name);
                $(".col_name input[type=\"text\"]", clone).prop("name", "known_cookies[" + rand_name + "][name]");
                $(".col_desc textarea", clone).prop("name", "known_cookies[" + rand_name + "][desc]");
                $(".col_group input[type=\"text\"]", clone).prop("name", "known_cookies[" + rand_name + "][group]");
                $(".col_req input[type=\"checkbox\"]", clone).prop("name", "known_cookies[" + rand_name + "][required]");
                $(".col_del button", clone).attr("data-row", "row_" + rand_name);

                // Add the clone to the existing cookies
                clone.prependTo($('table tbody', cookie_table)).fadeIn('slow');

                // Focus it
                $('input:first', cookie_table).focus();
                return false;
            });

            // Make the "Remove" button after each cookie functional
            if (delete_buttons.length) {
                delete_buttons.click(function(e) {
                    row = $("#" + $(this).attr("data-row"));

                    if (row.length) {
                        $(".delete-cb", row).prop("checked", true);

                        row.fadeOut("slow", function() {
                            if (!$(".delete-cb", row).length) {
                                row.remove();
                            }
                        });
                    }

                    e.preventDefault();
                });
            }

            // Allow the user to select a group from a dropdown, or optionally edit it
            if (group_dropdowns.length) {
                // If the dropdown is changed, set the associated input field.
                $("select", group_dropdowns).change(function() {
                    $("input[type=\"text\"]", $(this).closest("td")).val($(":selected", this).val());
                });

                // Switch between a dropdown or input field
                $(".edit-group", group_dropdowns.parent()).click(function(e) {
                    my_parent = $(this).parent();

                    if ($(this).hasClass("add")) {
                        // Switch to "select"
                        $(this).text(cookillian_translate.sel_cookie_group);

                        $("input[type=\"text\"]", my_parent).show().focus();
                        $("select", my_parent).hide();
                    } else {
                        // Switch to "add"
                        $(this).text(cookillian_translate.add_cookie_group);

                        $("input[type=\"text\"]", my_parent).hide();
                        $("select", my_parent).show();
                    }

                    // Toggle the class
                    $(this).toggleClass("add");

                    e.preventDefault();
                });
            }

        },

        /**
         * Initializes the JS functionality on the Statistics page
         */
        initStatsPage : function() {
            var stats_form = $("#stats_form")
                , details;

            if (!stats_form.length) {
                return;
            }

            $("#clear-stats").click(function(e) {
                if (!confirm(cookillian_translate.are_you_sure)) {
                    e.preventDefault();
                }
            });

            $(".month-row").click(function() {
                details = $("." + $(this).attr('id') + "_details");

                if ($(this).hasClass("expanded")) {
                    $(this).removeClass("expanded");
                    $(".collapse-btn", this).html("&#9660;");
                    details.fadeOut();
                } else {
                    $(this).addClass("expanded");
                    $(".collapse-btn", this).html("&#9650;");
                    details.fadeIn();
                }
            });
        }
    });

    $(document).ready(function($){
        cookillian.initSettingsPage();
        cookillian.initCookiePage();
        cookillian.initStatsPage();
    });
}(jQuery));
