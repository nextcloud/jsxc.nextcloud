declare var OC: {
    generateUrl(string, any?): string,
    PasswordConfirmation: any,
    getCurrentUser(): {uid: string, displayName: string},
    requestToken: string,
};
declare var oc_requesttoken: string;
declare var JSXC: any;
declare var OJSXC_CONFIG: {
    defaultLoginFormEnable: boolean,
    startMinimized: boolean,
    serverType: 'external' | 'internal' | 'managed',
};
