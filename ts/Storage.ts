
const PREFIX = 'ojsxc:';

const BACKEND = localStorage;

export default class Storage {

   private static instance;

   public static get(): Storage {
      if (!Storage.instance) {
         Storage.instance = new Storage();
      }

      return Storage.instance;
   }

   private constructor() {

   }

   public setItem(key: string, value: any): void {
      try {
         value = JSON.stringify(value);
      } catch (err) {
         console.warn('Error while stringifing', err);

         return;
      }

      BACKEND.setItem(PREFIX + key, value);
   }

   public getItem(key: string): any {
      let value = BACKEND.getItem(PREFIX + key);

      if (typeof value === 'string') {
         value = JSON.parse(value);
      }

      return value;
   }

   public removeItem(key: string): void {
      BACKEND.removeItem(PREFIX + key);
   }
}
