/*!
 * Copyright (c) 2012 Mike Green <myatus@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
if(cookillian===undefined){var cookillian={}}(function(b){b(document).ready(function(a){var d=a(".cookillian-alert");if(!cookillian._manual){d.hide().detach().prependTo("body").fadeIn("slow")}a(".close",d).click(function(){d.fadeOut("slow");return false})})})(jQuery);