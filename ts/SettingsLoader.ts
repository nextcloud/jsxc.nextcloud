import { SERVER_TYPE } from "./CONST";
import Storage from "./Storage";

export function loadSettings(username, password) {
   return new Promise((resolve, reject) => {
      $.ajax({
         type: 'POST',
         url: OC.generateUrl('apps/ojsxc/settings'),
         data: {
            username: username,
            password: password
         },
         success: (d) => resolve(d),
         error: xhr => reject(xhr),
      });
   })
      .then(handleResponse)
      .catch(handleError);
}

function handleResponse(response) {
   if (response.result !== 'success' || !response.data) {
      throw 'Received unsuccessful response.';
   }

   let data = response.data;
   let serverType = SERVER_TYPE[data.serverType.toUpperCase()];
   let xmpp = data.xmpp || {};

   Storage.get().setItem('serverType', serverType);

   if (serverType !== SERVER_TYPE.INTERNAL && xmpp.url) {

      // if (forceLoginFormEnable) {
      //     response.data.loginForm.enable = true;
      // }

      return {
         xmpp: {
            url: xmpp.url,
            domain: xmpp.domain,
         }
      };
   } else if (serverType === SERVER_TYPE.INTERNAL) {
      // var node = username || OC.currentUser;
      // jsxc.bid = node.toLowerCase() + '@' + window.location.host;

      // jsxc.options.set('adminSettings', response.data.adminSettings);

      // if (response.data.loginForm) {
      //     jsxc.options.set('loginForm', {
      //         startMinimized: response.data.loginForm.startMinimized
      //     });
      // }
   }

   Storage.get().removeItem('serverType');

   return false;
}

function handleError(xhr) {
   console.warn('XHR error on getSettings.php');

   if (xhr.responseJSON && xhr.responseJSON.message) {
      console.log('Error message: ' + xhr.responseJSON.message);
   }

   if (xhr.status === 412) {
      console.log('Refresh page to get a new CSRF token');

      window.location.href = window.location.href;

      return new Promise(() => { });
   }

   return false;
}
