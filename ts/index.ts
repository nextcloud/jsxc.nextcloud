// import jsxc from 'jsxc/src';
import FormWatcher from './FormWatcher';
import Bootstrap from './Bootstrap';
import { loadSettings } from './SettingsLoader';
import injectChatIcon from './ChatIconInjector';

(function() {
   try {
      Bootstrap.start();
   } catch (err) {
      console.warn('Abort JSXC', err);

      return;
   }

   let numberOfCachedAccounts = jsxc.init({
      loadSettings: loadSettings,
   });

   if (numberOfCachedAccounts === 0 && oc_current_user) {
      jsxc.start();
   }

   injectChatIcon();

   let formElement = $('#body-login form');
   let usernameElement = $('#user');
   let passwordElement = $('#password');

   if (formElement.length > 0) {
      jsxc.watchForm(formElement, usernameElement, passwordElement, FormWatcher.callback);
   }
})();
