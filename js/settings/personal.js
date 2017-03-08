/* global OC */

(function($) {
   "use strict";

   $(function(){
      $('#ojsxc-settings [name="loginFormEnable"]').change(function(){
         var loginFormData = {
            enable: $(this).prop('checked')
         };

         if (jsxc.bid) {
            loginFormData = $.extend(jsxc.options.get('loginForm'), loginFormData);
            jsxc.options.set('loginForm', loginFormData);
         }

         $.ajax({
            method: 'POST',
            url: OC.filePath('ojsxc', 'ajax', 'setUserSettings.php'),
            data: {
               loginForm: loginFormData
            },
            success: function(data){
               if (data === 'true') {
                  console.log('loginFormEnable saved.');
               }
            }
         });
      });
   });
}(jQuery));
