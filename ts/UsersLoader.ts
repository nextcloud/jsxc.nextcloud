function getUsers(search, cb) {
   $.ajax({
      type: 'GET',
      url: OC.generateUrl('apps/ojsxc/settings/users'),
      data: {
         search: search
      },
      success: cb,
      error: function() {
         console.warn('XHR error on getUsers.php');
      }
   });
}
