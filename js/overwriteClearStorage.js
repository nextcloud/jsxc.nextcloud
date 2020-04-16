window.localStorage.clear = function () {
    Object.keys(window.localStorage).filter((key) => {
       return key.indexOf('jsxc') !== 0;
    }).forEach((key) => window.localStorage.removeItem(key));
 };
