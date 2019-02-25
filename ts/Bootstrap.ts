import { DEPENDENCIES } from './CONST'
import Settings from './Settings';
import injectChatIcon from './ChatIconInjector';
import { addChatSubmitButton } from './ChatSubmitButtonInjector';
import { IJID } from 'jsxc/src/JID.interface';
import defaultAvatar from './DefaultAvatar';
import Storage from './Storage';

export default class Bootstrap {
   public static check() {
      Bootstrap.checkDependencies();
      Bootstrap.checkFrame();
      Bootstrap.checkSpecialPage();
   }

   private static checkDependencies() {
      for (let dependency of DEPENDENCIES) {
         if (typeof (<any>window)[dependency] === 'undefined') {
            throw `Dependency "${dependency}" is missing.`;
         }
      }
   }

   private static checkFrame() {
      if (window.parent && window !== window.parent) {
         throw `Abort, because we are running inside a frame.`;
      }
   }

   private static checkSpecialPage() {
      if (/^(\/index.php)?\/s\//.test(location.pathname)) {
         throw `Abort, because we dont want to start chat on public shares.`;
      }

      if (OC.generateUrl('login/flow') === window.location.pathname) {
         throw `Abort, because chat is not needed on flow login.`;
      }
   }

   public static start() {
      if (typeof OJSXC_CONFIG === 'undefined') {
         setTimeout(Bootstrap.start, 100);

         return;
      }

      Bootstrap.initJSXC();
      Bootstrap.addWatcher();
      Bootstrap.addAlternativeLogin();

      injectChatIcon();
   }

   private static initJSXC() {
      let numberOfCachedAccounts = jsxc.init({
         appName: 'Nextcloud',
         rosterVisibility: OJSXC_CONFIG.startMinimized ? 'hidden' : 'shown',
         loadConnectionOptions: Settings.loadConnection,
         loadOptions: Settings.load,
         onOptionChange: Settings.onOptionChange,
         avatarPlaceholder: (element: JQuery, name: string, color: string, jid: IJID) => {
            defaultAvatar(element, name, jid);
         },
         onUserRequestsToGoOnline: Bootstrap.onUserRequestsToGoOnline,
      });

      if (numberOfCachedAccounts === 0 && oc_current_user) {
         jsxc.start();
      }

      if (OJSXC_CONFIG.serverType === 'internal') {
         let storage = Storage.get();
         let jid = storage.getItem('internal:jid');
         let url = storage.getItem('internal:url');

         if (jid && url) {
            jsxc.start(url, jid, 'sid', '1234');

            storage.removeItem('internal:jid');
            storage.removeItem('internal:url');
         }
      }
   }

   private static addWatcher() {
      let formElement = $('#body-login form[name="login"]');
      let usernameElement = $('#user');
      let passwordElement = $('#password');

      if (formElement.length && usernameElement.length && passwordElement.length) {
         jsxc.watchForm(formElement, usernameElement, passwordElement);
      }

      let logoutElement = $('[data-id="logout"] a');

      if (logoutElement.length) {
         jsxc.watchLogoutClick(logoutElement);
      }
   }

   private static addAlternativeLogin() {
      let formElement = $('#body-login form[name="login"]');

      addChatSubmitButton(formElement);
   }

   private static async onUserRequestsToGoOnline() {
      if (!Storage.get().getItem('serverIsOmniscient') && OJSXC_CONFIG.serverType !== 'internal') {
         jsxc.showLoginBox();

         return;
      }

      try {
         let settings = await Settings.loadConnection(undefined, undefined);

         if (!settings) {
            throw new Error('No settings provided');
         }

         let xmpp = settings.xmpp;
         let jid = xmpp.node + '@' + xmpp.domain + '/' + xmpp.resource;

         if (OJSXC_CONFIG.serverType === 'internal') {
            jsxc.start(xmpp.url, jid, 'sid', '1234');
         } else {
            jsxc.start(xmpp.url, jid, xmpp.password || '');
         }
      } catch(err) {
         console.log('Error during log in', err);

         jsxc.showLoginBox();
      }
   }
}
