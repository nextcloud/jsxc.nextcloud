/* global $, OC */

$(document).ready(function() {
   /**
    * Test if bosh server is up and running.
    *
    * @param  {string}   url    BOSH url
    * @param  {string}   domain host domain for BOSH server
    * @param  {Function} cb     called if test is done
    */
   function testBoshServer(url, domain, cb) {
      var rid = jsxc.storage.getItem('rid') || '123456';

      function fail(m) {
         var msg = 'BOSH server NOT reachable or misconfigured.';

         if (typeof m === 'string') {
            msg += '<br /><br />' + m;
         }

         cb({
            status: 'fail',
            msg: msg
         });
      }

      $.ajax({
         type: 'POST',
         url: url,
         data: "<body rid='" + rid + "' xmlns='http://jabber.org/protocol/httpbind' to='" + domain + "' xml:lang='en' wait='60' hold='1' content='text/xml; charset=utf-8' ver='1.6' xmpp:version='1.0' xmlns:xmpp='urn:xmpp:xbosh'/>",
         global: false,
         dataType: 'xml'
      }).done(function(stanza) {
         if (typeof stanza === 'string') {
            // shouldn't be needed anymore, because of dataType
            stanza = $.parseXML(stanza);
         }

         var body = $(stanza).find('body[xmlns="http://jabber.org/protocol/httpbind"]');
         var condition = (body) ? body.attr('condition') : null;
         var type = (body) ? body.attr('type') : null;

         // we got a valid xml response, but we have test for errors

         if (body.length > 0 && type !== 'terminate') {
            cb({
               status: 'success',
               msg: 'BOSH Server reachable.'
            });
         } else {
            if (condition === 'internal-server-error') {
               fail('Internal server error: ' + body.text());
            } else if (condition === 'host-unknown') {
               if (url) {
                  fail('Host unknown: ' + domain + ' is unknown to your XMPP server.');
               } else {
                  fail('Host unknown: Please provide a XMPP domain.');
               }
            } else {
               fail(condition);
            }
         }
      }).fail(function(xhr, textStatus) {
         // no valid xml, not found or csp issue

         var fullurl;
         if (url.match(/^https?:\/\//)) {
            fullurl = url;
         } else {
            fullurl = window.location.protocol + '//' + window.location.host;
            if (url.match(/^\//)) {
               fullurl += url;
            } else {
               fullurl += window.location.pathname.replace(/[^/]+$/, "") + url;
            }
         }

         if(xhr.status === 0) {
            // cross-side
            fail('Cross domain request was not possible. Either your BOSH server does not send any ' +
               'Access-Control-Allow-Origin header or the content-security-policy (CSP) blocks your request. ' +
               'Starting from Owncloud 9.0 your CSP will be updated in any app which uses the appframework (e.g. files) ' +
               'after you save these settings and reload.' +
               'The savest way is still to use Apache ProxyRequest or Nginx proxy_pass.');
         } else if (xhr.status === 404) {
            // not found
            fail('Your server responded with "404 Not Found". Please check if your BOSH server is running and reachable via ' + fullurl + '.');
         } else if (textStatus === 'parsererror') {
            fail('Invalid XML received. Maybe ' + fullurl + ' was redirected. You should use an absolute url.');
         } else {
            fail(xhr.status + ' ' + xhr.statusText);
         }
      });
   }

   $('#ojsxc [name=serverType]').change(function(){
      $('#ojsxc .ojsxc-external, #ojsxc .ojsxc-internal').hide();
      $('#ojsxc .ojsxc-external, #ojsxc .ojsxc-internal').find('.required').removeAttr('required');
      $('#ojsxc .ojsxc-' + $(this).val()).show();
      $('#ojsxc .ojsxc-' + $(this).val()).find('.required').attr('required', 'true');
   });
   $('#ojsxc [name=serverType]:checked').change();

   $('#boshUrl, #xmppDomain').on('input', function(){
      var self = $(this);
      var timeout = self.data('timeout');

      if (timeout) {
         clearTimeout(timeout);
      }

      var url = $('#boshUrl').val();
      var domain = $('#xmppDomain').val();

      if (!url || !domain) {
         // we need url and domain to test BOSH server
         return;
      }

      $('#ojsxc .boshUrl-msg').html('<div></div>');
      var status = $('#ojsxc .boshUrl-msg div');
      status.html('<img src="' + jsxc.options.root + '/img/loading.gif" alt="wait" width="16px" height="16px" /> Testing BOSH Server...');

      // test only every 2 seconds
      timeout = setTimeout(function() {
         testBoshServer(url, domain, function(res) {
            status.addClass('jsxc_' + res.status);
            status.html(res.msg);
         });
      }, 2000);

      self.data('timeout', timeout);
   });

   function saveAdminSettings() {
      if (OC.PasswordConfirmation && OC.PasswordConfirmation.requiresPasswordConfirmation()) {
         OC.PasswordConfirmation.requirePasswordConfirmation(saveAdminSettings);
         return;
      }

      var post = $('#ojsxc').serialize();

      $('#ojsxc .msg').html('<div>');
      var status = $('#ojsxc .msg div');
      status.html('<img src="' + jsxc.options.root + '/img/loading.gif" alt="wait" width="16px" height="16px" /> Saving...');

      $.post(OC.filePath('ojsxc', 'ajax', 'setAdminSettings.php'), post, function(data) {
         if (data) {
            status.addClass('jsxc_success').text('Settings saved. Please log out and in again.');
         } else {
            status.addClass('jsxc_fail').text('Error!');
         }

         setTimeout(function(){
            status.hide('slow');
         }, 3000);
      });
   }

   $('#ojsxc').submit(function(event) {
      event.preventDefault();

      saveAdminSettings();
   });

   $('#ojsxc .add-input').click(function(ev) {
      ev.preventDefault();

      var clone = $(this).prev().clone();
      clone.val('');

      $(this).before(clone);
   });

   $('#insert-upload-service').click(function(ev) {
      ev.preventDefault();

      if (!jsxc.xmpp.conn || !jsxc.xmpp.conn.connected) {
         console.warn('Not connected to any XMPP server.');
         return;
      }

      var options = jsxc.options.get('httpUpload') || {};

      var services = $('[name="externalServices[]"]').map(function(){
         var inputField = $(this);

         return inputField.val() || null;
      });

      if (options.server && services.toArray().indexOf(options.server) < 0) {
         // insert service
         var emptyInputFields = $('[name="externalServices[]"]').filter(function(){
            return $(this).val() === '';
         });

         var targetInputField;

         if(emptyInputFields.length === 0) {
            $(this).parents('.form-group').find('.add-input').click();
            targetInputField = $('[name="externalServices[]"]').last();
         } else {
            targetInputField = $(emptyInputFields[0]);
         }

         targetInputField.val(options.server);
      }
   });

   var apiUrl = window.location.origin + OC.filePath('ojsxc', 'ajax', 'externalApi.php');
   $('#jsxc-api-url').val(apiUrl);

   $('#ojsxc input[readonly]').focus(function(){
      if(typeof this.select === 'function') {
         this.select();
      }
   });
});
