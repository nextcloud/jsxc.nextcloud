
export default function injectChatIcon() {
   var div = $('<div/>');

   div.addClass('ojsxc-chat-icon');
   div.click(function() {
      jsxc.toggleRoster();
   });

   $('#header form.searchbox').after(div);
}
