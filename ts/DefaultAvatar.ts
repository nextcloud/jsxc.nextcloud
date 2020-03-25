import Storage from './Storage';
import { IJID } from 'jsxc/src/JID.interface';

interface IAvatar {
   username: string;
   type: 'url' | 'placeholder';
   displayName?: string;
   url?: string;
}

export default async function defaultAvatar(elements: JQuery, name: string, jid?: IJID) {
   let storage = Storage.get();

   let defaultDomain = storage.getItem('defaultDomain');
   let isExternalUser = jid && jid.domain !== defaultDomain;
   let avatar: IAvatar = {
      username: jid ? jid.node : name,
      displayName: name,
      type: 'placeholder',
   };

   if (!isExternalUser) {
      let maxSize = elements.get().reduce((currentMax, element) => {
         if ($(element).width() > currentMax) {
            currentMax = $(element).width();
         }
         if ($(element).height() > currentMax) {
            currentMax = $(element).height();
         }
         return currentMax;
      }, 0);

      avatar = await getAvatar(avatar.username, maxSize);
   }

   $(elements).each(function() {
      let element = $(this);

      if (element.length === 0) {
         return;
      }

      displayAvatar(element, avatar);
   });
}

async function getAvatar(username: string, size: number): Promise<IAvatar> {
   let key = username + '@' + size;
   let cache = Storage.get().getItem('avatar:' + key);

   if (cache) {
      return cache;
   }

   let avatar = await requestAvatar(username, size);

   Storage.get().setItem('avatar:' + key, avatar);

   return avatar;
}

function requestAvatar(username: string, size: number): Promise<IAvatar> {
   let url = getAvatarUrl(username, size);

   return new Promise(resolve => {
      $.get(url, function(result, textStatus, jqXHR) {
         if (jqXHR.getResponseHeader('content-type').match(/^image\//i)) {
            resolve({
               username,
               type: 'url',
               displayName: undefined,
               url,
            });
         } else {
            resolve({
               username,
               type: typeof result === 'string' ? 'url' : 'placeholder',
               displayName: result.data && result.data.displayname ? result.data.displayname : undefined,
               url: typeof result === 'string' ? result : undefined,
            });
         }
      }).fail(() => {
         resolve({
            username,
            type: 'placeholder',
         });
      });
   });
}

function displayAvatar(element: JQuery, avatar: IAvatar) {
   if (avatar && avatar.type === 'url') {
      element.css('backgroundImage', 'url(' + avatar.url + ')');
      element.text('');
   } else {
      setPlaceholder(element, avatar.username, avatar.displayName);
   }
}

function getAvatarUrl(username: string, size: number) {
   return OC.generateUrl('/avatar/' + encodeURIComponent(username) + '/' + size, {
      user: username,
      size,
      requesttoken: OC.requestToken || oc_requesttoken
   });
}

function setPlaceholder(element, username: string, displayName?: string) {
   (<any> element).imageplaceholder(username, displayName);
}
