import { DEPENDENCIES } from './CONST'

export default class Bootstrap {
   public static start() {
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
}
