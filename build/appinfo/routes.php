<?php
/**
 * ownCloud - JavaScript XMPP Chat
 *
 * Copyright (c) 2014 Klaus Herberth <klaus@jsxc.org> <br>
 * Released under the MIT license
 *
 * @author Klaus Herberth
 */

use \OCA\OJSXC\AppInfo\Application;

$this->create('ojsxc_ajax_getsettings', 'ajax/getSettings.php')
	->actionInclude('ojsxc/ajax/getSettings.php');

$this->create('ojsxc_ajax_getturncredentials', 'ajax/getTurnCredentials.php')
	->actionInclude('ojsxc/ajax/getTurnCredentials.php');

$this->create('ojsxc_ajax_setadminsettings', 'ajax/setAdminSettings.php')
	->actionInclude('ojsxc/ajax/setAdminSettings.php');

$this->create('ojsxc_ajax_setUserSettings', 'ajax/setUserSettings.php')
	->actionInclude('ojsxc/ajax/setUserSettings.php');

$this->create('ojsxc_ajax_getUsers', 'ajax/getUsers.php')
	->actionInclude('ojsxc/ajax/getUsers.php');

$this->create('ojsxc_ajax_externalApi', 'ajax/externalApi.php')
	->actionInclude('ojsxc/ajax/externalApi.php');

$application = new Application();
$application->registerRoutes($this, array(
	'routes' => array(
		array('name' => 'http_bind#index', 'url' => '/http-bind', 'verb' => 'POST'),
	)
));
?>
