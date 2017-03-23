/* global jsxc, oc_appswebroots, OC, oc_requesttoken, dijit, oc_config */
/* jshint latedef: nofunc */


(function($) {
   "use strict";

   function injectChatIcon() {
      var div = $('<div/>');

      div.addClass('jsxc_chatIcon');
      div.click(function(){
         jsxc.gui.roster.toggle();
      });

      $('#settings').after(div);
   }

   function onRosterToggle(event, state, duration) {
      $('body').removeClass('jsxc-roster-hidden jsxc-roster-shown').addClass('jsxc-roster-' + state);

      // trigger nextcloud/owncloud triggers
      setTimeout(function(){
         $(window).resize();
      }, duration + 50);
   }

    function checkForChatRoom() {
        if($("#chatroom").length == 0) {
            if($(".jsxc_joinDocumentChat").length > 0) {
                $(".jsxc_joinDocumentChat").remove();
            }
        } else {
            if($(".jsxc_joinDocumentChat").length == 0) {
                var roomPassword = $("#chatroom").data("chatroom-password");
                var roomId = $("#chatroom").data("chatroom-name");
                roomId = "jsxcdocumentchat"+roomId;
                var roomName = $("#chatroom").data("chatroom-title");
                var conferenceServer = jsxc.options.get('muc').server;
                var room = roomId+"@"+conferenceServer;
                var nickname = Strophe.getNodeFromJid(jsxc.muc.conn.jid);
                var $div = $('<div id="joinButtonContainer" style="text-align: center; margin-top:-75px; width:100%; " />');
                $(".jsxc_bottom").append($div);
                var $list = $('<li class="jsxc_joinDocumentChat" data-i18n="Join document chat">Join document chat</li>');
                $list.insertAfter(".jsxc_about");
                $(".jsxc_joinDocumentChat").click(function(){
                    jsxc.gui.window.clear(room);
                    jsxc.storage.setUserItem('member', roomId, {});
                    jsxc.muc.join(room, nickname, roomPassword, roomName, "Document group chat", false, false);
                    jsxc.gui.window.open(room);
                });
            }
        }
        setTimeout(checkForChatRoom, 200);
    }

   function onRosterReady() {
      if (typeof $('#jsxc_roster').outerWidth() !== 'number') {
         setTimeout(onRosterReady, 200);
         return;
      }

      injectChatIcon();

      var rosterState = jsxc.storage.getUserItem('roster') || 'hidden';
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
       checkForChatRoom();
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

      if (typeof jsxc === 'undefined') {
         // abort if core or dependencies threw an error
         return;
      }

      $(document).one('ready.roster.jsxc', onRosterReady);
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
            onConnecting: (oc_config.version.match(/^([8-9]|[0-9]{2,})+\./))? 'quiet' : 'dialog'
         },
         logoutElement: $('#logout'),
         rosterAppend: 'body',
         root: oc_appswebroots.ojsxc + '/js/jsxc',
         RTCPeerConfig: {
            url: OC.filePath('ojsxc', 'ajax', 'getTurnCredentials.php')
         },
         displayRosterMinimized: function() {
            return OC.currentUser != null;
         },
         defaultAvatar: function(jid) {
            var cache = jsxc.storage.getUserItem('defaultAvatars') || {};
            var user = Strophe.unescapeNode(jid.replace(/@[^@]+$/, ''));

            $(this).each(function() {

               var $div = $(this).find('.jsxc_avatar');
               var size = $div.width();
               var key = user + '@' + size;

               var handleResponse = function(result) {
                  if (typeof (result) === 'object') {
                     if (result.data && result.data.displayname) {
                        $div.imageplaceholder(user, result.data.displayname);
                     } else {
                        $div.imageplaceholder(user);
                     }
                  } else {
                     $div.css('backgroundImage', 'url('+result+')');
                  }
               };

               if (typeof cache[key] === 'undefined' || cache[key] === null) {
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
         },
         loadSettings: function(username, password, cb) {
            $.ajax({
               type: 'POST',
               url: OC.filePath('ojsxc', 'ajax', 'getSettings.php'),
               data: {
                  username: username,
                  password: password
               },
               success: function(d) {
                  if (d.result === 'success' && d.data && d.data.serverType !== 'internal' && d.data.xmpp.url !== '' && d.data.xmpp.url !== null) {
                     cb(d.data);
                  } else if (d.data && d.data.serverType === 'internal') {
                     // fake successful connection
                     jsxc.bid = username + '@' + window.location.host;

                     jsxc.storage.setItem('jid', jsxc.bid + '/internal');
                     jsxc.storage.setItem('sid', 'internal');
                     jsxc.storage.setItem('rid', '123456');

                     jsxc.options.set('xmpp', {
                        url: OC.generateUrl('apps/ojsxc/http-bind')
                     });
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
               error: function() {
                  jsxc.error('XHR error on getSettings.php');

                  cb(false);
               }
            });
         },
         saveSettinsPermanent: function(data, cb) {
            $.ajax({
               type: 'POST',
               url: OC.filePath('ojsxc', 'ajax', 'setUserSettings.php'),
               data: data,
               success: function(data) {
                  cb(data.trim() === 'true');
               },
               error: function() {
                  cb(false);
               }
            });
         },
         getUsers: function(search, cb) {
            $.ajax({
               type: 'GET',
               url: OC.filePath('ojsxc', 'ajax', 'getUsers.php'),
               data: {
                  search: search
               },
               success: cb,
               error: function() {
                  jsxc.error('XHR error on getUsers.php');
               }
            });
         },
         viewport: {
            getSize: function() {
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
         }
      });

      // Add submit link without chat functionality
      if (jsxc.el_exists(jsxc.options.loginForm.form) && jsxc.el_exists(jsxc.options.loginForm.jid) && jsxc.el_exists(jsxc.options.loginForm.pass)) {

         var link = $('<a/>').text($.t('Log_in_without_chat')).attr('href', '#').click(function() {
            jsxc.submitLoginForm();
         });

         var alt = $('<p id="jsxc_alt"/>').append(link);
         $('#body-login form:eq(0) fieldset').append(alt);
      }
   });
}(jQuery));
