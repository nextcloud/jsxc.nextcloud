import Bootstrap from './Bootstrap';
import './settings/personal'
import './settings/admin'

(function() {
   let bootstrap = new Bootstrap();

   try {
      bootstrap.check();
   } catch (err) {
      console.warn('Abort JSXC', err);

      return;
   }

   bootstrap.start();
})();
