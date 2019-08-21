import Storage from './Storage';

export function addChatSubmitButton(formElement: JQuery<any>, translate: (key: string) => string) {
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
      submitElement.val(translate('Log_in_without_chat'));
      submitElement.click(function() {
         storage.setItem('loginForm:disable', true);

         formElement.submit();
      });
   } else {
      submitElement.val(translate('Log_in_with_chat'));
      submitElement.click(function() {
         storage.setItem('loginForm:disable', false);

         formElement.submit();
      });
   }

   submitWrapperElement.append(submitElement);
   formElement.find('.login-additional').prepend(submitWrapperElement);

   $('#lost-password').mouseup(function(ev) {
      ev.preventDefault();

      submitWrapperElement.slideUp().fadeOut();
   });
   $('#lost-password-back').mouseup(function(ev) {
      ev.preventDefault();

      submitWrapperElement.slideDown().fadeIn();
   });
}
