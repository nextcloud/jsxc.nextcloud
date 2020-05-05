
export function armXMPPSchemaHandler(executeUri: (uri: string) => boolean) {
   $('body').click(ev => {
      let targetElement = $(ev.target);
      let linkElement = targetElement.is('[href]') ? targetElement : targetElement.parents('[href]');
      let href = linkElement.attr('href') || '';

      if (!href.startsWith('xmpp:')) {
         return;
      }

      try {
         if (executeUri(href)) {
            ev.preventDefault();
         }
      } catch (err) {
         console.log('Error while executing uri query', err);
      }
   });
}
