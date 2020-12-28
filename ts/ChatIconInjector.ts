
export default function injectChatIcon(toggleRoster: () => void) {
   let div = $('<div/>');

   div.addClass('ojsxc-chat-icon');
   div.on('click', function() {
      toggleRoster();
   });

   if ($('#header .header-right').length > 0) {
      $('#header .header-right').prepend(div);
   } else {
      $('#header form.searchbox').after(div);
   }
}
