import Settings from '../Settings';

let formElement = $('#ojsxc-admin');

class AdminForm {
   constructor(private formElement) {
      this.registerServerTypeHandler();
      this.triggerInitialServerTypeUpdate();

      this.registerConnectionHandler();
      this.registerSubmitHandler();
      this.registerServiceHandler();
      this.registerReadonlyHandler();

      new RegistrationForm();
   }

   private showServerTypeSpecificFields(serverType: string) {
      this.formElement.find('.ojsxc-external, .ojsxc-internal, .ojsxc-managed').hide();
      this.formElement.find('.ojsxc-external, .ojsxc-internal, .ojsxc-managed').find('.required').removeAttr('required');
      this.formElement.find('.ojsxc-' + serverType).show();
      this.formElement.find('.ojsxc-' + serverType).find('.required').attr('required', 'true');
   }
   private registerServerTypeHandler() {
      let self = this;

      this.formElement.find('[name="xmpp_serverType"]').change(function() {
         self.showServerTypeSpecificFields(<string> $(this).val());
      });
   }
   private triggerInitialServerTypeUpdate() {
      this.formElement.find('[name="xmpp_serverType"]:checked').change();
   }

   private registerConnectionHandler() {
      $('#boshUrl, #xmppDomain').on('input', function() {
         ConnectionParamter.validate(<string> $('#boshUrl').val(), <string> $('#xmppDomain').val());
      });
   }

   private registerSubmitHandler() {
      this.formElement.submit(function(event) {
         event.preventDefault();

         AdminSettings.save();
      });
   }

   private registerServiceHandler() {
      this.formElement.find('.add-input').click(function(ev) {
         ev.preventDefault();

         ExternalServiceList.addNewField();
      });

      $('#insert-upload-service').click(function(ev) {
         ev.preventDefault();

         ExternalServiceList.addUploadServices();
      });
   }

   private registerReadonlyHandler() {
      this.formElement.find('input[readonly]').focus(function() {
         if (typeof (<any> this).select === 'function') {
            (<any> this).select();

            //@TODO show copied icon
            document.execCommand('copy');
         }
      });
   }
}

class ConnectionParamter {
   private static timeout;

   public static validate(url: string, domain: string) {
      if (ConnectionParamter.timeout) {
         clearTimeout(ConnectionParamter.timeout);
      }

      if (!url || !domain) {
         // we need url and domain to test BOSH server
         return;
      }

      let statusContainer = formElement.find('.boshUrl-msg');
      statusContainer.empty();

      let statusElement = $('<div>');
      statusElement.html('<img src="img/loading.gif" alt="wait" width="16px" height="16px" /> Testing BOSH Server...');
      statusElement.appendTo(statusContainer);

      // test only every 2 seconds
      ConnectionParamter.timeout = setTimeout(function() {
         JSXC.testBOSHServer(url, domain).then(result => {
            statusElement.addClass('jsxc_success');
            statusElement.html(result);
         }).catch(err => {
            statusElement.addClass('jsxc_error');
            statusElement.html(err.message);
         });
      }, 2000);
   }
}

class AdminSettings {
   public static save() {
      if (OC.PasswordConfirmation && OC.PasswordConfirmation.requiresPasswordConfirmation()) {
         OC.PasswordConfirmation.requirePasswordConfirmation(AdminSettings.save);
         return;
      }

      let post = formElement.serialize();

      formElement.find('.msg').html('<div>');
      let status = formElement.find('.msg div');
      status.html('<img src="img/loading.gif" alt="wait" width="16px" height="16px" /> Saving...');

      Settings.saveAdmin(post).then(function(isSuccessful) {
         if (isSuccessful) {
            status.addClass('jsxc_success').text('Settings saved. Please log out and in again.');
         } else {
            status.addClass('jsxc_fail').text('Error!');
         }

         setTimeout(function() {
            status.hide('slow');
         }, 3000);
      });
   }
}

class ExternalServiceList {
   public static addNewField() {
      let lastElement = formElement.find('[name="externalServices[]"]').last();
      let newElement = lastElement.clone();
      newElement.val('');

      lastElement.after(newElement);

      return newElement;
   }

   public static addUploadServices() {
      let existingServices = <string[]> $('[name="externalServices[]"]').map(function() {
         let inputField = $(this);

         return <any> inputField.val() || null;
      }).toArray();

      let uploadService = ExternalServiceList.discoverUploadService();

      if (!uploadService || existingServices.indexOf(uploadService) > -1) {
         return;
      }

      let element = formElement.find('[name="externalServices[]"]:empty').first();

      if (element.length === 0) {
         element = ExternalServiceList.addNewField();
      }

      element.val(uploadService);
   }

   private static discoverUploadService() {
      //@TODO discover upload service
      return '';
   }
}

class ManagedServer {
   public static register(promotionCode: string) {
      return new Promise((resolve, reject) => {
         $.ajax({
            method: 'POST',
            url: OC.generateUrl('apps/ojsxc/managedServer/register'),
            data: {
               promotionCode
            }
         }).always(function(responseJSON) {

            if (responseJSON && responseJSON.result === 'success') {
               return resolve();
            }

            if (responseJSON.responseJSON) {
               responseJSON = responseJSON.responseJSON;
            }

            let errorMsg = (responseJSON && responseJSON.data) ? responseJSON.data.msg : 'unknown error';
            let requestId = (responseJSON && responseJSON.data) ? responseJSON.data.requestId : 'no-request-id';

            reject([errorMsg, requestId]);
         });
      });
   }
}

class RegistrationForm {
   constructor() {
      $('#ojsxc-register').click(this.onRegisterClickHandler);
      $('.ojsxc-refresh-registration').click(this.onRefreshClickHandler);

      $('#ojsxc-legal, #ojsxc-dp').on('change', function() {
         $('#ojsxc-register').prop('disabled', !$('#ojsxc-legal').prop('checked') || !$('#ojsxc-dp').prop('checked'));
      });
   }

   private onRegisterClickHandler() {
      let el = $(this);
      let statusElement = el.parents('.ojsxc-managed').find('.msg');
      let promotionCode = <string> $('#ojsxc-managed-promotion-code').val();

      if (promotionCode.length > 0 && !/^[0-9a-z]+$/i.test(promotionCode)) {
         statusElement.addClass('jsxc_fail');
         statusElement.text('Your promotion code is invalid.');

         $('#ojsxc-managed-promotion-code').one('input', function() {
            statusElement.removeClass('jsxc_fail');
            statusElement.text('');
         });

         return;
      }

      el.prop('disabled', 'disabled');
      el.val(el.attr('data-toggle-value'));
      el.addClass('jsxc-loading');

      ManagedServer.register(promotionCode).then(() => {
         $('.ojsxc-managed-registration').hide();

         statusElement.addClass('jsxc_success');
         statusElement.text('Congratulations! You got your own XMPP server. Please log out and in again.');

         let submitElement = $('#ojsxc input[type="submit"]');
         submitElement.prop('disabled', 'disabled');
         submitElement.val('Please reload this page to continue');
      }).catch(([err, requestId]) => {
         statusElement.addClass('jsxc_fail');
         statusElement.append($('<span>').text('Sorry we couldn\'t complete your registration.'));
         statusElement.append($('<br><br>'));
         statusElement.append($('<span>').text(err));
         statusElement.append($('<br><br>'));
         statusElement.append($('<span>').html('Please report this to our <a href="https://jsxc.ch/managed-issue-tracker" target="_blank">issue tracker</a> and mention the request id ' + requestId + '.'));

         el.val('Registration failed');
      });
   }

   private onRefreshClickHandler(ev) {
      ev.preventDefault();

      let statusElement = $(this).parents('.msg');

      statusElement.removeClass('jsxc_success');
      statusElement.empty();

      $('.ojsxc-managed-registration').show();
   }
}

if (formElement.length === 1) {
   new AdminForm(formElement);
}
