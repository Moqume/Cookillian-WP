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

    cookillian = {
        showHideCustomAlert : function() {
            var alert_content_type = $('input[name="alert_content_type"]:checked')
                , alert_normal     = $(".alert_normal")
                , alert_custom     = $(".alert_custom")
                , is_custom;

            if (!alert_content_type.length)
                return;

            is_custom = (alert_content_type.val() == "custom");

            alert_custom.showHide(is_custom);
            alert_normal.showHide(!is_custom);
        },

        showHideCustomAlertStyle : function() {
            var alert_style                = $('input[name="alert_style"]:checked')
                , alert_custom_style_extra = $(".alert_custom_style_extra");

            if (!alert_style.length)
                return;

            alert_custom_style_extra.showHide(alert_style.val() == "custom");
        },

        showHideExtras : function() {
            var maxmind_settings    = $(".maxmind_settings")
                , geo_checked       = $('input[name="geo_service"]:checked');

            if (!geo_checked.length)
                return;

            maxmind_settings.showHide(geo_checked.val() == 'maxmind');
        },

        initCookieDeleteButtons : function() {
            var delete_buttons = $(".cookie_table .delete-btn")
                , row;

            if (!delete_buttons.length)
                return;

            delete_buttons.click(function(e) {
                row = $("#" + $(this).attr("data-row"));

                if (row.length) {
                    $(".delete-cb", row).prop("checked", true);
                    row.fadeOut("slow");
                }

                e.preventDefault();
            });
        }
    }

    $(document).ready(function($){
        var cookie_table = $(".cookie_table");

        /* Resize the blank portion according to the width of the nested table */
        if (cookie_table.length) {
            $('.col_blank', cookie_table).width(
                cookie_table.width() - $('table', cookie_table).width()
            );
        }

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

        /* Make the 'Add New Cookie' button functional */
        $('#add_new_cookie_btn').click(function() {
            var rand_name = 'new_' + Math.floor((Math.random()*99999));

            // Add new table row
            $('<tr id="row_' + rand_name + '" class="new_cookie" style="display:none;"><td class="col_name"><input type="text" value="" name="known_cookies[' + rand_name + '][name]" placeholder="Enter the cookie name here"/></td><td class="col_desc"><textarea name="known_cookies[' + rand_name + '][desc]"></textarea></td><td class="col_group"><input type="text" value="" name="known_cookies[' + rand_name + '][group]" /></td><td class="col_req"><input type="checkbox" name="known_cookies[' + rand_name + '][required]" /></td><td class="col_del"><td></td></tr>').prependTo($('table tbody', cookie_table)).fadeIn('slow');

            // Focus it
            $('input:first', cookie_table).focus();
            return false;
        });

        /* Provide an alert for resetting statistics */
        $("#clear-stats").click(function(e) {
            if (!confirm(cookillian_translate.are_you_sure)) {
                e.preventDefault();
            }
        });

        /* Show or hide ... */
        $('input[name="alert_content_type"]').change(cookillian.showHideCustomAlert); cookillian.showHideCustomAlert();
        $('input[name="alert_style"]').change(cookillian.showHideCustomAlertStyle);   cookillian.showHideCustomAlertStyle();
        $('input[name="geo_service"]').change(cookillian.showHideExtras);             cookillian.showHideExtras();

        /* Show or hide debug information */
        $('#footer_debug_link').click(function() { $('#footer_debug').toggle(); return false; });

        /* Additional inits */
        cookillian.initCookieDeleteButtons();
    });
})(jQuery);
