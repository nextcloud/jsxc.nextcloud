import Bootstrap from './Bootstrap';
import './settings/personal'
import './settings/admin'

(function() {
   try {
      Bootstrap.check();
   } catch (err) {
      console.warn('Abort JSXC', err);

      return;
   }

   Bootstrap.start();
})();
