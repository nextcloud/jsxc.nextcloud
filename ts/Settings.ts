import Storage from './Storage';

const VALIDITY = 30000;

export default class Settings {
   private static cache: any;
   private static cacheTime: Date;

   public static onOptionChange(id: string, key: string, value: any, exportId: () => any) {
      let ignoreKeys = ['hideOfflineContacts', 'rosterVisibility'];

      if (ignoreKeys.indexOf(key) > -1) {
         return;
      }

      let data = exportId();

      ignoreKeys.forEach(key => delete data[key]);

      console.log('onOptionChange', id, key, value, data);

      Settings.saveUser({ [id]: data }).then(isSuccess => console.log('saveUser', isSuccess));
   }

   public static saveUser(data: any): Promise<boolean> {
      return Settings.save(OC.generateUrl('apps/ojsxc/settings/user'), data);
   }

   public static saveAdmin(data: any): Promise<boolean> {
      return Settings.save(OC.generateUrl('apps/ojsxc/settings/admin'), data);
   }

   private static save(url: string, data: any): Promise<boolean> {
      return new Promise((resolve) => {
         $.ajax({
            type: 'POST',
            url,
            data,
            success(data) {
               resolve(data && data.status === 'success');
            },
            error() {
               resolve(false);
            }
         });
      });
   }

   public static load(username?: string, password?: string) {
      if (Settings.isCached()) {
         return Promise.resolve(Settings.cache);
      }

      return Settings.requestSettings(username, password)
         .then(Settings.handleLoadResponse)
         .catch(Settings.handleLoadError);
   }

   private static requestSettings(username?: string, password?: string) {
      return new Promise((resolve, reject) => {
         $.ajax({
            type: 'POST',
            url: OC.generateUrl('apps/ojsxc/settings'),
            data: {
               username,
               password
            },
            success: (d) => resolve(d),
            error: xhr => reject(xhr),
         });
      });
   }

   public static async loadConnection(username: string, password: string) {
      try {
         let settings = await Settings.load(username, password);

         return Settings.getConnectionOptions(settings);
      } catch (err) {
         return false;
      }
   }

   private static getConnectionOptions(options) {
      if (!options) {
         return false;
      }

      let xmpp = options.xmpp || {};

      if (!xmpp.url) {
         return false;
      }

      let storage = Storage.get();
      storage.setItem('defaultDomain', xmpp.defaultDomain);
      storage.setItem('serverIsOmniscient', !!xmpp.password);

      let loginFormForcedDisable = storage.getItem('loginForm:disable');
      let disabled = typeof loginFormForcedDisable === 'boolean' ? loginFormForcedDisable : !OJSXC_CONFIG.defaultLoginFormEnable;

      storage.removeItem('loginForm:disable');

      return {
         disabled,
         xmpp: {
            url: xmpp.url,
            node: xmpp.node,
            domain: xmpp.domain,
            password: xmpp.password,
            resource: xmpp.resource,
         }
      };
   }

   private static isCached() {
      return Settings.cache && Settings.cacheTime && ((new Date()).getTime() - Settings.cacheTime.getTime()) < VALIDITY;
   }

   private static handleLoadResponse(response) {
      if (!response || response.result !== 'success' || !response.data) {
         throw new Error('Received unsuccessful response.');
      }

      Settings.cache = response.data;
      Settings.cacheTime = new Date();

      return response.data;
   }

   private static handleLoadError(err) {
      console.warn('Error during settings retrieval.');

      Settings.cache = undefined;
      Settings.cacheTime = undefined;

      if (err.responseJSON && err.responseJSON.message) {
         console.log('Error message: ' + err.responseJSON.message);
      }

      if (err.status === 412) {
         console.log('Refresh page to get a new CSRF token');

         window.location.href = window.location.href;

         return new Promise(() => { });
      }

      return false;
   }
}
