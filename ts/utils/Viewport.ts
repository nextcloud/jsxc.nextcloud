import Storage from '../Storage';

export default class Viewport {
   public static getSize(): { width: number, height: number } {
      let w = <number> $(window).width() - <number> $('#jsxc_windowListSB').width();
      let h = <number> $(window).height() - <number> $('#header').height() - 10;

      //@TODO
      if (Storage.get().getItem('roster') === 'shown') {
         w -= <number> $('#jsxc-roster').outerWidth(true);
      }

      return {
         width: w,
         height: h
      };
   }
}
