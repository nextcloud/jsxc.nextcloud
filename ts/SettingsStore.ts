function saveSettinsPermanent(data, cb) {
   $.ajax({
      type: 'POST',
      url: OC.generateUrl('apps/ojsxc/settings/user'),
      data: data,
      success: function(data) {
         cb(data && data.status === 'success');
      },
      error: function() {
         cb(false);
      }
   });
}
