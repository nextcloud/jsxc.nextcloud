# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## 5.0.0-beta.1 (2022-01-04)
### Added
- bump Nextcloud support
- bump JSXC to 4.4.0-beta.1
- BREAKING CHANGE: remove internal backend

### Fixed
- remove deprecated app.php
- disable jsxc if not configured
- hide internal option if not used

### Misc
- change version requirements
- satisfy psalm
- fix xml schema
- update dependencies

## 4.3.1 (2021-07-08)
### Fixed
- update JSXC to 4.3.1 ([changelog](https://github.com/jsxc/jsxc/releases/tag/v4.3.1))
- add deprecation label to internal option

### Misc
- update jsxc
- update changelog
- update dependencies

## 4.3.0 (2021-07-05)
### Added
- update JSXC to 4.3.0 ([changelog](https://github.com/jsxc/jsxc/releases/tag/v4.3.0))
- bump Nextcloud to version 21

### Fixed
- remove obsolete variable
- command DI
- multiple DI issues
- php8 type error

### Misc
- internal server is deprecated
- use more automated DI
- align filename with class name
- add psalm
- update dependencies
- remove deprecated mock method
- refactor deprecated tests
- refactor hooks test
- fix compatibility between nc versions
- update phpunit
- fix phpunit
- fix nc version for php 8
- fix branch name
- move to github actions
- update ci badge

## 4.2.1 (2020-12-28)
### Fixed
- fix undefined host key
- fix undefined array key
- fix chat icon injection

### Misc
- update JSXC to 4.2.1 ([changelog](https://github.com/jsxc/jsxc/blob/master/CHANGELOG.md))

## 4.2.0 (2020-12-13)
### Added
- update JSXC to 4.2.0 ([changelog](https://github.com/jsxc/jsxc/blob/master/CHANGELOG.md))
- use Nextcloud color scheme
- bump max Nextcloud version to 20

### Fixed
- [#159](https://github.com/nextcloud/jsxc.nextcloud/issues/159) do not restore accounts on login page
- add chat login only to main login page
- fix roster space for files table
- fix correct use of ocp/util
- increase timeout to register managed server
- fix hosts with custom port
- fix fast repeating locks
- [jsxc/jsxc#916](https://github.com/jsxc/jsxc/issues/916) fix null xml attribute

### Misc
- run tests with master and php 7.4
- update ci to bionic
- replace deprecated annotations
- fix integration tests with random values
- add missing imports
- update composer dependencies

## 4.1.1 (2020-06-22)
### Misc
- update JSXC to v4.1.1 ([change log](https://github.com/jsxc/jsxc/blob/master/CHANGELOG.md))
- update dependencies

## 4.1.0 (2020-05-15)
### Added
- bump max nc version to 19
- ship with own version of jquery
- [#143](https://github.com/jsxc/jsxc/issues/143) handle xmpp links

### Fixed
- remove external service detection
- [#142](https://github.com/jsxc/jsxc/issues/142) php notices
- disable jsxc on totp page
- [#139](https://github.com/jsxc/jsxc/issues/139) follow personal start setting

### Misc
- update JSXC to v4.1.0 ([change log](https://github.com/jsxc/jsxc/blob/master/CHANGELOG.md))
- change dev structure
- use fully-qualifier class name
- move app file to lib
- add publish script
- beautify build output
- show webpack progress

## 4.0.0 - 2020-04-08
### Fixed
- default stun server

### Changed
- update JSXC to v4.0.0 ([change log](https://github.com/jsxc/jsxc/blob/master/CHANGELOG.md))
- bump min NC version to 16
- bump max NC version to 18
- use db migration
- show always login with and without chat buttons
- preserve local storage during logout
- adapt feature policy

## 3.4.4 - 2019-05-10
### Fixed
- [jsxc/jsxc#681](https://github.com/jsxc/jsxc/issues/681) sanitize port from internal jabber id
- [#122](https://github.com/nextcloud/jsxc.nextcloud/issues/122) force light theme
- [jsxc/jsxc#761](https://github.com/jsxc/jsxc/issues/761) fix overlap with pdf viewer
- drop support for Nextcloud 13
- make compatible with Nextcloud 16

## 3.4.3 - 2018-12-05
### Fixed
- make compatible with Nextcloud 15
- fix support for long usernames (internal backend)
- fix JSXC on login flow page
- [jsxc/jsxc#726](https://github.com/jsxc/jsxc/issues/726) fix undefined index
- [jsxc/jsxc#740](https://github.com/jsxc/jsxc/issues/740) ignore not iterable options
- [#111](https://github.com/nextcloud/jsxc.nextcloud/issues/111) fix missing chat icon in combination with the search app
- [#80](https://github.com/nextcloud/jsxc.nextcloud/issues/80) invert chat icon on light theme

### Changed
- update JSXC to v3.4.3 ([change log](https://github.com/jsxc/jsxc/blob/master/CHANGELOG.md))
- use server sharing settings if enabled by admin to limit chatting scope (internal backend)

## 3.4.2 - 2018-08-05
### Fixed
- [jsxc/jsxc#686](https://github.com/jsxc/jsxc/issues/686) fix countable error
- [#84](https://github.com/nextcloud/jsxc.nextcloud/issues/84) hide some buttons if internal backend is used
- adapt style for NC 14
- fix info.xml scheme

### Changed
- update JSXC to v3.4.2 ([change log](https://github.com/jsxc/jsxc/blob/master/CHANGELOG.md))
- show XMPP domain of used managed XMPP server
- allow to change XMPP connection options in personal settings
- remove deprecated code from settings section

## 3.4.1 - 2018-07-05
### Fixed
- [#104](https://github.com/nextcloud/jsxc.nextcloud/issues/104) fix call to undefined method

## 3.4.0 - 2018-05-23
### Added
- option to show only group members in the roster (internal)
- [jsxc/jsxc#477](https://github.com/jsxc/jsxc/issues/477) add admin setting to change log in behavior

### Fixed
- fix undefined index error
- fix sanitization of uid to jid (internal)
- [jsxc/jsxc#659](https://github.com/jsxc/jsxc/issues/659) layout adjustments for mail app and control bar
- [#91](https://github.com/nextcloud/jsxc.nextcloud/issues/91) enable user hooks only for internal backend

### Changed
- [jsxc/jsxc#678](https://github.com/jsxc/jsxc/issues/678) use first letter of displayname for all avatars
- set internal backend as default
- bump min NC version to 12
- beautify 'log in without chat' button

## 3.3.2 - 2017-11-29
### Fixed
- [jsxc/jsxc#640](https://github.com/jsxc/jsxc/issues/640) fix type error related to internal backend

### Changed
- update JSXC to v3.3.2 ([change log](https://github.com/jsxc/jsxc/blob/master/CHANGELOG.md))

## 3.3.1 - 2017-10-20
### Fixed
- fix shared roster retrieval in NC<12
- fix position of chat icon
- [#72](https://github.com/nextcloud/jsxc.nextcloud/issues/72) fix password confirmation
- allow `@` in usernames (internal)
- fix roster push (internal)
- omit disabled users from roster (internal)
- escape usernames (internal)
- fix relogin (internal)

### Changed
- update JSXC to v3.3.1 ([change log](https://github.com/jsxc/jsxc/blob/master/CHANGELOG.md))

## 3.3.0 - 2017-08-25
### Added
- [#28](https://github.com/nextcloud/jsxc.nextcloud/issues/28) add admin section icon to NC 12
- [#23](https://github.com/nextcloud/jsxc.nextcloud/issues/23) support user@domain Nextcloud usernames for XMPP Cloud Auth
- [#21](https://github.com/nextcloud/jsxc.nextcloud/issues/21) extend NC contact menu
- add logging of stanzas (internal)
- update roster when user is created, deleted or changed (internal)
- add command to refresh roster of all users (internal)
- [#36](https://github.com/nextcloud/jsxc.nextcloud/issues/36) add sharedroster operation to external api
- [#37](https://github.com/nextcloud/jsxc.nextcloud/issues/37) add registration form for managed server service (beta)
- [#38](https://github.com/nextcloud/jsxc.nextcloud/issues/38) allow authentication with app passwords via external api
- add pre-commit hook template
- roster install repair step (internal)
- [#50](https://github.com/nextcloud/jsxc.nextcloud/issues/50) add icon to personal settings
- [#50](https://github.com/nextcloud/jsxc.nextcloud/issues/50) add personal section

### Fixed
- [#24](https://github.com/nextcloud/jsxc.nextcloud/issues/24) fix prefer mail address to login
- [#19](https://github.com/nextcloud/jsxc.nextcloud/issues/19) fix inaccurate presence (internal)
- fix presence when muliple users go offline (internal)
- fix loading of avatars (internal)
- clean up chat data after an user got removed (internal)
- [#35](https://github.com/nextcloud/jsxc.nextcloud/issues/35) fix message exchange (internal)
- fix multiple php warnings
- use system value to determine jsxc environment
- refresh-roster command (internal)
- [#55](https://github.com/nextcloud/jsxc.nextcloud/issues/55) fix query for predefined core services

### Changed
- update jsxc to v3.3.0 ([change log](https://github.com/jsxc/jsxc/blob/master/CHANGELOG.md))
- drop support for NC 9
- set internal server as default for new installations
- move api secret generation to app.php
- [#39](https://github.com/nextcloud/jsxc.nextcloud/issues/39) transfer all ajax endpoints to controller
- update phpunit to 5.7
- update npm dependencies
- use codecov
- [#46](https://github.com/nextcloud/jsxc.nextcloud/issues/46) use php-cs-fixer to be PSR-2 compliant
- [#54](https://github.com/nextcloud/jsxc.nextcloud/pull/54) allow plain usernames without domain for sharedRoster
- [jsxc/jsxc#519](https://github.com/jsxc/jsxc/issues/519) consider preferMail option in contact menu
- abort if a NC dependency is missing
- use minified js in production environment
- reduce number of login attempts for external api
- support multiple ice urls

## 3.2.1 - 2017-06-01
### Fixed
- don't include Sabre if already loaded
- prevent js strict warning
- fix DbLock (internal backend)
- fix presence (internal backend)
- fix undefined constant error

### Changed
- upgrade jsxc to v3.2.1 ([change log](https://github.com/jsxc/jsxc/blob/master/CHANGELOG.md))
- only show chat icon if a backend is enabled
- update app icon

## 3.2.0 - 2017-05-17
### Added
- add external API for [XMPP server authentication](https://github.com/jsxc/xmpp-cloud-auth)
- [jsxc/jsxc#476](https://github.com/jsxc/jsxc/issues/476) add personal settings
- add chat icon to contact menu

### Fixed
- [jsxc/jsxc#455](https://github.com/jsxc/jsxc/issues/455) fix login with different credentials
- [jsxc/jsxc#516](https://github.com/jsxc/jsxc/issues/516) fix log in after connection failure
- fix first roster initialisation

### Changed
- upgrade jsxc to v3.2.0 ([change log](https://github.com/jsxc/jsxc/blob/master/CHANGELOG.md))
- support nc12
- use css to manipulate nc ui
- [jsxc/jsxc#436](https://github.com/jsxc/jsxc/issues/436) hide roster on login screen
- change chat icon position for nc12
- [jsxc/jsxc#382](https://github.com/jsxc/jsxc/issues/382) make internal backend ncs
- own section in admin settings for ojsxc
- change design of bosh test result

## 3.1.1 - 2017-02-14
- upgrade jsxc to v3.1.1

## 3.1.0 - 2017-02-14
### Added
- add option to use user email as jid
- add option to add external services
- add option to add external upload service automatically to csp

### Fixed
- fix untrimmed settings
- ignore empty webrtc configuration
- do not start jsxc inside a frame
- fix disabled chat on login
- fix user setting retrieval

### Changed
- upgrade jsxc to v3.1.0 ([change log](https://github.com/jsxc/jsxc/blob/master/CHANGELOG.md))
- change category to social
- minor settings redesign
- use nc colors
- minor style change
- require admin password to save settings
- handle richdocuments ui

## 3.0.2 - 2016-12-23
### Changed
- rebrand from owncloud to nextcloud
- changed max version
- ignore empty turn config

### Fixed
- trim settings
  [jsxc/jsxc#384](https://github.com/jsxc/jsxc/issues/384)

## 3.0.1 - 2016-10-28
- upgrade jsxc to v3.0.1
- fix invalid argument
- use regex to match full id instead of only letters (internal chat server)
- allow port number in BOSH url for csp
- fix login without chat link
- force login form

## 3.0.0 - 2016-03-11
- upgrade jsxc to v3.0.0
- add experimental internal chat server
- add chat icon to oc header
- ignore empty bosh url on login
- do not use login overlay in oc >= 8.2
- refactore admin settings
- fix initial sidebar handling
- fix conflict with oc avatars
- modify csp (oc 9.0)
- set minimum required oc version to 8.0
- remove deprecated code and beautify
- add makefile
- fix turn credentials with secret

## 2.1.5 - 2015-11-17
- upgrade jsxc to v2.1.5
- adaptions for oc 8.2
- do not include images in stylesheet

## 2.1.4 - 2015-09-10
- upgrade jsxc to v2.1.4
- disable jsxc if core or dependencies threw an error

## 2.1.3 - 2015-09-08
- upgrade jsxc to v2.1.3

## 2.1.2 - 2015-08-12
- upgrade jsxc to v2.1.0
- update grunt-sass (fix invalid css)

## 2.1.1 - 2015-08-10
- handle escaped jids in loadAvatar
- fix TURN-REST-API credential generation

## 2.1.0 - 2015-07-31
- upgrade jsxc to v2.1.0
- stop attachment on login screen
- load settings async

## 2.0.1 - 2015-05-23
- upgrade jsxc to v2.0.1
- fix hidden scrollbar
- fix white bar in documents app

## 2.0.0 - 2015-05-08
- upgrade jsxc to v2.0.0
- add username autocomplete
- fix 'login without chat' style
- fix zindex window list

## 1.1.0 - 2015-02-16
- upgrade jsxc to v1.1.0
- add routes
- fix bosh test with csp
- prepare oc 8
- switch to sass
- supress php notice

## 1.0.0 - 2014-11-06
- upgrade jsxc to v1.0.0
- add application name
- add spot to contacts
- fix badcode issue
- fix 'invalid argument foreach' warning
- fix TURN-REST-API
- fix documents support
- handle overwrite flag null as false
- use concatenated and minified version

## 0.8.2 - 2014-08-20
- fix issue with php < 5.4
- upgrade jsxc to v0.8.2

## 0.8.1 - 2014-08-12
- upgrade jsxc to v0.8.1
- update to oc7

## 0.8.0 - 2014-07-02
- upgrade jsxc to v0.8.0
- prepare for oc 7
- adjust jsxc root

## 0.7.2 - 2014-05-28
- ugrade jsxc to v0.7.2
- clean up oc specific stylesheet

## 0.7.1 - 2014-03-18
- upgrade jsxc to v0.7.1
- replace utf8 gear with svg gear
- add missing emoticons

## 0.7.0 - 2014-03-07
- upgrade jsxc to v0.7.0
- enable otr debugging
- add oc avatars

## 0.6.0 - 2014-02-28
- upgrade jsxc to v0.6.0
- add external auth script for ejabberd (on github)

## 0.5.2 - 2014-01-28
- upgrade jsxc to v0.5.2

## 0.5.1 - 2014-01-27
- downgrade required oc version
- upgrade jsxc to v0.5.1

## 0.5.0 - 2014-01-13
- add hide offline buddy function
- add about dialog
- add vCard avatars
- fix double entry
- fix text replacement
- fix dialog size
- fix translation
- fix display of long names
- fix bosh test
- fix rename bug

## 0.4.4 - 2013-12-20
- fix dialog height
- add BOSH connection test
- fix css id (cid) generation
- fix close button

## 0.4.3 - 2013-12-11
- fix mac-chrome-reload bug
- fix design issue
- fix OTR whitespaces

## 0.4.2 - 2013-12-11
- include colorbox (independent of firstrunwizard)

## 0.4.1 - 2013-12-11
- fix eof bug
- rebuild

## 0.4.0 - 2013-12-10
- display notification request only for incoming messages
- update ui if we lost the trust state
- display allow dialog
- fix chrome notification issue
- fix ringing dialog
- init grunt/jshint
- lot of code clean up and commenting
- set focus to password field
- display minimized roster if no connection is found
- porting to OC6 (replace fancybox with colorbox)
- add webrtc info dialog (ip, fingerprint)
- use git submodules for OTR and strophe.jingle
- update README

## 0.3.0 - 2013-10-28
- use lowercase jid
- add url detection
- add basic muc support (alpha)
- create DSA key in background
- fix notification with multiple tabs
- reorganize files
- add MIT license
- minor fixes
