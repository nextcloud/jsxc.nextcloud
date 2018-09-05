
export default function injectChatIcon() {
   var div = $('<div/>');

   div.addClass('jsxc_chatIcon');
   div.click(function() {
      jsxc.toggleRoster();
   });

   $('#header form.searchbox').after(div);
}
