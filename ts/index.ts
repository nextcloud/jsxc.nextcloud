import Bootstrap from './Bootstrap';
import './settings/personal';
import './settings/admin';

$(document).on('ajaxSend', function(_elm, xhr, settings) {
	if (settings.crossDomain === false) {
		xhr.setRequestHeader('requesttoken', OC.requestToken);
		xhr.setRequestHeader('OCS-APIREQUEST', 'true');
	}
});

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
