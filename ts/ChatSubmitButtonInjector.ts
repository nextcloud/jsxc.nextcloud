import Storage from './Storage'

function addChatSubmitButton(formElement: JQuery<any>) {
   let storage = Storage.get();
   let defaultEnable = OJSXC_CONFIG.defaultLoginFormEnable;
   let submitWrapperElement = $('<div>');
   submitWrapperElement.attr('id', 'jsxc-submit-wrapper');

   let submitElement = $('<input>');
   submitElement.attr({
      type: 'button',
      id: 'jsxc-submit',
   });
   submitElement.addClass('login primary');
   if (defaultEnable) {
      submitElement.val('Log_in_without_chat'); //@TODO translate
      submitElement.click(function() {
         // submit form without login
      });
   } else {
      submitElement.val('Log_in_with_chat');
      submitElement.click(function() {
         let forceLoginFormEnable = true;
         formElement.submit();
      });
   }

   submitWrapperElement.append(submitElement);
   $('.login-additional').prepend(submitWrapperElement);

   $('#lost-password').mouseup(function(ev) {
      ev.preventDefault();

      submitWrapperElement.slideUp().fadeOut();
   });
   $('#lost-password-back').mouseup(function(ev) {
      ev.preventDefault();

      submitWrapperElement.slideDown().fadeIn();
   });
}
