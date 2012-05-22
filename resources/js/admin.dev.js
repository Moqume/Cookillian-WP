/*!
 * Copyright (c) 2012 Mike Green <myatus@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

if (cookillian === undefined)
    var cookillian = {};

(function($){
    cookillian = {
        showHideCustomAlert : function() {
            var alert_content_type = $('input[name="alert_content_type"]:checked')
                , alert_normal     = $('.alert_normal')
                , alert_custom     = $('.alert_custom');

            if (!alert_content_type.length)
                return;

            if (alert_content_type.val() == "custom") {
                alert_custom.show();
                alert_normal.hide();
            } else {
                alert_custom.hide();
                alert_normal.show();
            }
        },

        showHideExtras : function() {
            var maxmind_settings    = $(".maxmind_settings")
                , geo_checked       = $('input[name="geo_service"]:checked');

            if (!geo_checked.length)
                return;

            maxmind_settings.toggle(geo_checked.val() == 'maxmind');
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
            $('<tr class="new_cookie" style="display:none;"><td class="col_name"><input type="text" value="" name="known_cookies[' + rand_name + '][name]" placeholder="Enter the cookie name here"/></td><td class="col_desc"><textarea name="known_cookies[' + rand_name + '][desc]"></textarea></td><td class="col_group"><input type="text" value="" name="known_cookies[' + rand_name + '][group]" /></td><td class="col_req"><input type="checkbox" name="known_cookies[' + rand_name + '][required]" /></td><td class="col_del"><input type="checkbox" name="known_cookies[' + rand_name + '][delete]" /></td></tr>').prependTo($('table tbody', cookie_table)).fadeIn('slow');

            // Focus it
            $('input:first', cookie_table).focus();
            return false;
        });

        /* Show or hide ... */
        $('input[name="alert_content_type"]').change(cookillian.showHideCustomAlert); cookillian.showHideCustomAlert();
        $('input[name="geo_service"]').change(cookillian.showHideExtras);             cookillian.showHideExtras();

        /* Show or hide debug information */
        $('#footer_debug_link').click(function() { $('#footer_debug').toggle(); return false; });

    });
})(jQuery);
