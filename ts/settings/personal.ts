import Settings from '../Settings';

function onLoginFormChanged() {
   let value = <string> $(this).val();
   console.log('onLoginFormChanged', value);
   $.ajax({
      method: 'POST',
      url: OC.generateUrl('apps/ojsxc/settings/user'),
      data: {
         disabled: value.match(/^true|false$/) ? JSON.parse(value) : null
      },
      success(data) {
         if (data && data.status === 'success') {
            console.log('loginFormEnable saved.');
         }
      }
   });
}

function savePersonalSettings() {
   if (OC.PasswordConfirmation && OC.PasswordConfirmation.requiresPasswordConfirmation()) {
      OC.PasswordConfirmation.requirePasswordConfirmation(savePersonalSettings);
      return;
   }

   let post = $('#ojsxc').serialize();

   let statusContainer = $('#ojsxc .msg');
   statusContainer.empty();

   let statusElement = $('<div/>');
   statusElement.text('Saving...');
   statusElement.appendTo(statusContainer);

   Settings.saveUser(post).then((isSuccessful) => {
      if (isSuccessful) {
         statusElement.addClass('jsxc_success').text('Settings saved. Please log out and in again.');
      } else {
         statusElement.addClass('jsxc_fail').text('Error!');
      }

      setTimeout(function() {
         statusElement.hide('slow');
      }, 3000);
   });
}

$('#ojsxc-settings [name="disabled"]').change(onLoginFormChanged);

//@REVIEW id
$('#ojsxc').submit(function(ev) {
   ev.preventDefault();

   savePersonalSettings();
});
