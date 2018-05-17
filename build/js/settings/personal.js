/* global OC */

(function($) {
   "use strict";

   $(function() {
      $('#ojsxc-settings [name="loginFormEnable"]').change(function() {
         var loginFormData = {
            enable: $(this).val().match(/^true|false$/) ? JSON.parse($(this).val()) : null
         };

         if (jsxc.bid) {
            var options = jsxc.storage.getUserItem('options');

            if (loginFormData.enable === null && options.loginForm) {
               delete options.loginForm.enable;

               jsxc.storage.setUserItem('options', options);
            } else {
               loginFormData = $.extend(jsxc.options.get('loginForm'), loginFormData);

               jsxc.options.set('loginForm', loginFormData);
            }
         }

         $.ajax({
            method: 'POST',
            url: OC.generateUrl('apps/ojsxc/settings/user'),
            data: {
               loginForm: loginFormData
            },
            success: function(data) {
               if (data && data.status === 'success') {
                  jsxc.debug('loginFormEnable saved.');
               }
            }
         });
      });
   });
}(jQuery));
