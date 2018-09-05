
export default class FormWatcher {
   public static callback(username: string, password: string) {
      return new Promise(resolve => {
         $.ajax({
            type: 'POST',
            url: OC.generateUrl('apps/ojsxc/settings'),
            data: {
               username: username,
               password: password
            },
            success: response => FormWatcher.success(response, resolve),
            error: xhr => FormWatcher.error(xhr, resolve),
         });
      });
   }

   private static success(response, resolve) {
      if (response.result !== 'success') {
         resolve(false);

         return;
      }

      let xmpp = response.data.xmpp || {};

      if (!xmpp.url) {
         resolve(false);

         return;
      }

      resolve({
         xmpp: {
            url: xmpp.url,
            domain: xmpp.domain,
         }
      });
   }

   private static error(xhr, resolve) {
      if (xhr.responseJSON && xhr.responseJSON.message) {
         throw 'Error message: ' + xhr.responseJSON.message;
      }

      if (xhr.status === 412) {
         console.log('Refresh page to get a new CSRF token');

         window.location.href = window.location.href;
         return;
      }

      resolve(false);
   }
}
