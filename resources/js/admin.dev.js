/*!
 * Copyright (c) 2011-2012 Mike Green <myatus@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

(function($){
    $(document).ready(function($){
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
    });
})(jQuery);
