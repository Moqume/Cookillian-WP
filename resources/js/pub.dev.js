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
    $(document).ready(function($){
        var cookillian_alert = $('.cookillian-alert');

        // Move it to the top, forcing existing content down
        if (!cookillian._manual)
            cookillian_alert.hide().detach().prependTo('body').fadeIn('slow');

        $('.close', cookillian_alert).click(function() {
           cookillian_alert.fadeOut('slow');

           return false;
        });
    });
})(jQuery);
