import Storage from './Storage';

export function addChatSubmitButton(formElement: JQuery<any>, translate: (key: string) => string) {
   let storage = Storage.get();
   let defaultEnable = OJSXC_CONFIG.defaultLoginFormEnable;
   let submitWrapperElement = $('<div>');
   submitWrapperElement.attr('id', 'jsxc-submit-wrapper');

   let submitElement = $('<input>');
   submitElement.attr({
      type: 'button',
   });
   submitElement.addClass('login primary jsxc-submit');

   let submitElementWithout = submitElement.clone();
   submitElementWithout.val(translate('Log_in_without_chat'));
   submitElementWithout.click(function() {
      storage.setItem('loginForm:disable', true);

      formElement.submit();
   });

   let submitElementWith = submitElement.clone();
   submitElementWith.val(translate('Log_in_with_chat'));
   submitElementWith.click(function() {
      storage.setItem('loginForm:disable', false);

      formElement.submit();
   });

   submitWrapperElement.append(submitElementWithout);
   submitWrapperElement.append(submitElementWith);

   if (formElement.find('.login-additional').length > 0) {
      formElement.find('.login-additional').prepend(submitWrapperElement);
   } else {
      formElement.find('#submit-wrapper').after(submitWrapperElement);
   }

   $('#lost-password').mouseup(function(ev) {
      ev.preventDefault();

      submitWrapperElement.slideUp().fadeOut();
   });
   $('#lost-password-back').mouseup(function(ev) {
      ev.preventDefault();

      submitWrapperElement.slideDown().fadeIn();
   });
}
