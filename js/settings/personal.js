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

      function savePersonalSettings() {
        if (OC.PasswordConfirmation && OC.PasswordConfirmation.requiresPasswordConfirmation()) {
            OC.PasswordConfirmation.requirePasswordConfirmation(savePersonalSettings);
            return;
         }

         var post = $('#ojsxc').serialize();

         $('#ojsxc .msg').html('<div>');
         var status = $('#ojsxc .msg div');
         status.html('<img src="' + jsxc.options.root + '/img/loading.gif" alt="wait" width="16px" height="16px" /> Saving...');

         $.post(OC.generateUrl('apps/ojsxc/settings/user'), post, function(data) {
            if (data && data.status === 'success') {
               status.addClass('jsxc_success').text('Settings saved. Please log out and in again.');
            } else {
               status.addClass('jsxc_fail').text('Error!');
            }

            setTimeout(function() {
               status.hide('slow');
            }, 3000);
         });
      }

      $('#ojsxc').submit(function(ev) {
         ev.preventDefault()

         savePersonalSettings();
      })
   });
}(jQuery));
