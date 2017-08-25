/*!
 * ojsxc v3.3.0 - 2017-08-25
 * 
 * Copyright (c) 2017 Klaus Herberth <klaus@jsxc.org> <br>
 * Released under the MIT license
 * 
 * Please see http://www.jsxc.org/
 * 
 * @author Klaus Herberth <klaus@jsxc.org>
 * @version 3.3.0
 * @license MIT
 */

/* global jsxc, oc_appswebroots, OC, oc_requesttoken, dijit, oc_config */
/* jshint latedef: nofunc */


(function($) {
    "use strict";

    function observeContactsMenu() {
        var target = document.getElementById('contactsmenu');

        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.target.id !== 'contactsmenu-contacts') {
                    return;
                }

                $(mutation.target).find('[href^="xmpp:"]').addClass('jsxc_statusIndicator');

                $(mutation.target).find('.contact').each(function(){
                   updateContactItem($(this));
                });

                jsxc.gui.detectUriScheme(mutation.target);
            });
        });

        var config = {
            attributes: true,
            childList: true,
            characterData: true,
            subtree: true
        };

        observer.observe(target, config);
    }

    function updateContactItem(contactElement) {
      var xmppAddresses = contactElement.find('[href^="xmpp:"]').map(function(){
         return $(this).attr('href').replace(/^xmpp:/, '');
      });

      if (xmppAddresses.length === 0) {
         return;
      }

      var lastMessages = [];
      var highestPresent = jsxc.CONST.STATUS.indexOf('offline');
      var highestPresentBid = xmppAddresses.get(0);

      xmppAddresses.each(function(index, bid){
         var lastMsg = jsxc.getLastMsg(bid);

         if (lastMsg) {
            lastMessages.push(lastMsg);
         }

         var data = jsxc.storage.getUserItem('buddy', bid) || {};

         if (data.status > highestPresent) {
            highestPresent = data.status;
            highestPresentBid = bid;
         }
      });

      var latestMsg = {date: 0};
      $(lastMessages).each(function(index, msg){
         if (msg.date > latestMsg.date) {
            latestMsg = msg;
         }
      });

      if (latestMsg.date > 0) {
         // replace emoticons from XEP-0038 and pidgin with shortnames
         $.each(jsxc.gui.emotions, function(i, val) {
            latestMsg.text = latestMsg.text.replace(val[2], ':' + val[1] + ':');
         });

         // translate shortnames to images
         latestMsg.text = jsxc.gui.shortnameToImage(latestMsg.text);

         contactElement.find('.last-message').html(latestMsg.text);
      }

      if (highestPresent > 0) {
         var status = jsxc.CONST.STATUS[highestPresent];

         contactElement.removeClass('jsxc_' + jsxc.CONST.STATUS.join(' jsxc_')).addClass('jsxc_' + status);
      }

      if (highestPresentBid) {
         contactElement.find('.avatar').click(function() {
            jsxc.gui.queryActions.message(highestPresentBid);
         });
      }
    }

    function injectChatIcon() {
        var div = $('<div/>');

        div.addClass('jsxc_chatIcon');
        div.click(function() {
            jsxc.gui.roster.toggle();
        });

        if ($('#contactsmenu').length > 0) {
            $('#contactsmenu').before(div);
        } else {
            $('#settings').after(div);
        }
    }

    function onRosterToggle(ev, state, duration) {
        $('body').removeClass('jsxc-roster-hidden jsxc-roster-shown').addClass('jsxc-roster-' + state);

        // trigger nextcloud/owncloud triggers
        setTimeout(function() {
            $(window).resize();
        }, duration + 50);
    }

    function onRosterReady(ev, rosterState) {
        injectChatIcon();

        $('body').removeClass('jsxc-roster-hidden jsxc-roster-shown').addClass('jsxc-roster-' + rosterState);

        // update webodf
        $(window).on('hashchange', function() {
            if (window.location.pathname.match(/\/documents\/$/)) {
                var docNo = window.location.hash.replace(/^#/, '');

                if (docNo.match(/[0-9]+/) && typeof dijit !== 'undefined') {
                    dijit.byId("mainContainer").resize();
                }
            }
        });
    }

    function defaultAvatar(element, jid) {
        var adminSettings = jsxc.options.get('adminSettings') || {};
        var cache = jsxc.storage.getUserItem('defaultAvatars') || {};
        var data = jsxc.storage.getUserItem('buddy', jsxc.jidToBid(jid)) || {};

        var node = Strophe.getNodeFromJid(jid);
        var domain = Strophe.getDomainFromJid(jid);
        var user = Strophe.unescapeNode(node);

        $(element).each(function() {

            var $div = $(this).find('.jsxc_avatar');
            var size = $div.width();
            var key = user + '@' + size;

            var handleResponse = function(result) {
                if (typeof(result) === 'object') {
                    if (result.data && result.data.displayname) {
                        $div.imageplaceholder(user, result.data.displayname);
                    } else {
                        $div.imageplaceholder(user);
                    }
                } else {
                    $div.css('backgroundImage', 'url(' + result + ')');
                    $div.text('');
                }
            };

            if (domain !== adminSettings.xmppDomain) {
                // probably external user, don't request avatar
                $div.imageplaceholder(user);
            } else if (typeof cache[key] === 'undefined' || cache[key] === null) {
                if (data.status === 0) {
                    // don't query avatar for offline users
                    $div.imageplaceholder(user);

                    return;
                }

                var url;

                url = OC.generateUrl('/avatar/' + encodeURIComponent(user) + '/' + size + '?requesttoken={requesttoken}', {
                    user: user,
                    size: size,
                    requesttoken: oc_requesttoken
                });

                $.get(url, function(result) {

                    var val = (typeof result === 'object') ? result : url;
                    handleResponse(val);

                    jsxc.storage.updateItem('defaultAvatars', key, val, true);
                });

            } else {
                handleResponse(cache[key]);
            }
        });
    }

    function loadSettings(username, password, cb) {
        $.ajax({
            type: 'POST',
            url: OC.generateUrl('apps/ojsxc/settings'),
            data: {
                username: username,
                password: password
            },
            success: function(d) {
                if (d.result === 'success' && d.data && d.data.serverType !== 'internal' && d.data.xmpp.url !== '' && d.data.xmpp.url !== null) {
                    cb(d.data);
                } else if (d.data && d.data.serverType === 'internal') {
                    // fake successful connection
                    jsxc.bid = username.toLowerCase() + '@' + window.location.host;

                    jsxc.storage.setItem('jid', jsxc.bid + '/internal');
                    jsxc.storage.setItem('sid', 'internal');
                    jsxc.storage.setItem('rid', '123456');

                    jsxc.options.set('xmpp', {
                        url: OC.generateUrl('apps/ojsxc/http-bind')
                    });

                    jsxc.options.set('adminSettings', d.data.adminSettings);

                    if (d.data.loginForm) {
                        jsxc.options.set('loginForm', {
                            startMinimized: d.data.loginForm.startMinimized
                        });
                    }

                    cb(false);
                } else {
                    cb(false);
                }
            },
            error: function(xhr) {
                jsxc.error('XHR error on getSettings.php');

                if (xhr.responseJSON && xhr.responseJSON.message) {
                   jsxc.debug('Error message: ' + xhr.responseJSON.message);
                }

                if (xhr.status === 412) {
                   jsxc.debug('Refresh page to get a new CSRF token');

                   window.location.href = window.location.href;
                   return;
                }

                cb(false);
            }
        });
    }

    function saveSettinsPermanent(data, cb) {
        $.ajax({
            type: 'POST',
            url: OC.generateUrl('apps/ojsxc/settings/user'),
            data: data,
            success: function(data) {
                cb(data && data.status === 'success');
            },
            error: function() {
                cb(false);
            }
        });
    }

    function getUsers(search, cb) {
        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/ojsxc/settings/users'),
            data: {
                search: search
            },
            success: cb,
            error: function() {
                jsxc.error('XHR error on getUsers.php');
            }
        });
    }

    function getViewportSize() {
        var w = $(window).width() - $('#jsxc_windowListSB').width();
        var h = $(window).height() - $('#header').height() - 10;

        if (jsxc.storage.getUserItem('roster') === 'shown') {
            w -= $('#jsxc_roster').outerWidth(true);
        }

        return {
            width: w,
            height: h
        };
    }

    // initialization
    $(function() {
        if (location.pathname.substring(location.pathname.lastIndexOf("/") + 1) === 'public.php') {
            // abort on shares
            return;
        }

        if (window.parent && window !== window.parent) {
            // abort if inside a frame
            return;
        }

        if (typeof jsxc === 'undefined' || typeof emojione === 'undefined') {
            // abort if core or dependencies threw an error
            return;
        }

        if (typeof oc_config === 'undefined' || typeof oc_appswebroots === 'undefined' || typeof OC === 'undefined') {
           // abort if a dependency is missing
           return;
        }

        if (OC.generateUrl('login/flow') === window.location.pathname) {
           // abort on login flow
           return;
        }

        $(document).one('ready-roster-jsxc', onRosterReady);
        $(document).on('toggle.roster.jsxc', onRosterToggle);

        $(document).on('connected.jsxc', function() {
            // reset default avatar cache
            jsxc.storage.removeUserItem('defaultAvatars');
        });

        $(document).on('status.contacts.count status.contact.updated', function() {
            if (jsxc.restoreCompleted) {
                setTimeout(function() {
                    jsxc.gui.detectEmail($('table#contactlist'));
                }, 500);
            } else {
                $(document).on('restoreCompleted.jsxc', function() {
                    jsxc.gui.detectEmail($('table#contactlist'));
                });
            }
        });

        jsxc.init({
            app_name: 'Nextcloud',
            loginForm: {
                form: '#body-login form',
                jid: '#user',
                pass: '#password',
                ifFound: 'force',
                onConnecting: (oc_config.version.match(/^([8-9]|[0-9]{2,})+\./)) ? 'quiet' : 'dialog'
            },
            logoutElement: $('#logout'),
            rosterAppend: 'body',
            root: oc_appswebroots.ojsxc + '/js/jsxc',
            RTCPeerConfig: {
                url: OC.generateUrl('apps/ojsxc/settings/iceServers')
            },
            displayRosterMinimized: function() {
                return OC.currentUser != null;
            },
            defaultAvatar: function(jid) {
               defaultAvatar(this, jid);
            },
            loadSettings: loadSettings,
            saveSettinsPermanent: saveSettinsPermanent,
            getUsers: getUsers,
            viewport: {
                getSize: getViewportSize
            }
        });

        // Add submit link without chat functionality
        if (jsxc.el_exists(jsxc.options.loginForm.form) && jsxc.el_exists(jsxc.options.loginForm.jid) && jsxc.el_exists(jsxc.options.loginForm.pass)) {

            var link = $('<a/>').text($.t('Log_in_without_chat')).attr('href', '#').click(function() {
                jsxc.submitLoginForm();
            });

            var alt = $('<p id="jsxc_alt"/>').append(link);
            $('#body-login form:eq(0) fieldset').append(alt);

            Strophe.log = function(level, msg) {
                if (level === 3 && /^request id/.test(msg)) {
                    console.warn('Something went wrong during BOSH connection establishment. Continue without chat.');

                    jsxc.submitLoginForm();
                }
            };
        }

        if ($('#contactsmenu').length > 0) {
            observeContactsMenu();
        }
    });
}(jQuery));
