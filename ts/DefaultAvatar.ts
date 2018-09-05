import Storage from "./Storage";
import { IJID } from "jsxc/src/JID.interface";

enum Presence {
   online,
   chat,
   away,
   xa,
   dnd,
   offline
}

interface Avatar {
   username: string,
   type: 'url' | 'placeholder',
   displayname?: string,
   url?: string;
}

export default function defaultAvatar(elements, jid: IJID, name: string, presence) {
   let storage = Storage.get();

   let adminSettings = storage.getItem('adminSettings') || {};

   $(elements).each(function() {
      let element = $(this);

      if (element.length === 0) {
         return;
      }

      let size = <number>element.width();
      let username = jid.node;
      let key = username + '@' + size;
      let cache = storage.getItem('avatar:' + key);
      let isExternalUser = jid.domain !== adminSettings.xmppDomain;

      if (cache) {
         displayAvatar(element, cache);
      } else if (isExternalUser || presence === Presence.offline) {
         setPlaceholder(element, username);
      } else {
         requestAvatar(element, username, size);
      }
   });
}

function requestAvatar(element: JQuery<any>, username: string, size: number) {
   let url = getAvatarUrl(username, size);

   $.get(url, function(result) {
      let avatar: Avatar = {
         username: username,
         type: typeof result === 'string' ? 'url' : 'placeholder',
         displayname: result.data && result.data.displayname ? result.data.displayname : undefined,
         url: typeof result === 'string' ? result : undefined,
      };

      displayAvatar(element, avatar);

      Storage.get().setItem('avatar:' + username + '@' + size, avatar);
   });
}

function displayAvatar(element, avatar: Avatar) {
   if (avatar.type === 'url') {
      element.css('backgroundImage', 'url(' + avatar.url + ')');
      element.text('');
   } else {
      setPlaceholder(element, avatar.username, avatar.displayname);
   }
}

function getAvatarUrl(username: string, size: number) {
   return OC.generateUrl('/avatar/' + encodeURIComponent(username) + '/' + size + '?requesttoken={requesttoken}', {
      user: username,
      size: size,
      requesttoken: oc_requesttoken
   })
}

function setPlaceholder(element, username: string, displayname?: string) {
   (<any>element).imageplaceholder(username, displayname);
}
