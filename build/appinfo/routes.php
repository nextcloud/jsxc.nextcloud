<?php

use \OCA\OJSXC\AppInfo\Application;

$this->create('ojsxc_ajax_registerManagedServer', 'ajax/registerManagedServer.php')
	->actionInclude('ojsxc/ajax/registerManagedServer.php');

$application = new Application();
$application->registerRoutes($this, array(
	'routes' => array(
		array('name' => 'http_bind#index', 'url' => '/http-bind', 'verb' => 'POST'),

		array('name' => 'settings#index', 'url' => '/settings', 'verb' => 'POST'),
		array('name' => 'settings#setAdmin', 'url' => '/settings/admin', 'verb' => 'POST'),
		array('name' => 'settings#setUser', 'url' => '/settings/user', 'verb' => 'POST'),
		array('name' => 'settings#getIceServers', 'url' => '/settings/iceServers', 'verb' => 'GET'),
		array('name' => 'settings#getUsers', 'url' => '/settings/users', 'verb' => 'GET'),
		array('name' => 'settings#getServerType', 'url' => '/settings/servertype', 'verb' => 'GET'),

		array('name' => 'externalApi#index', 'url' => '/ajax/externalApi.php', 'verb' => 'POST'),
		// array('name' => 'externalApi#check_password', 'url' => '/api/v2/checkPassword', 'verb' => 'POST'),
		// array('name' => 'externalApi#is_user', 'url' => '/api/v2/isUser', 'verb' => 'POST'),
		// array('name' => 'externalApi#shared_roster', 'url' => '/api/v2/sharedRoster', 'verb' => 'POST'),

		array('name' => 'managedServer#register', 'url' => '/managedServer/register', 'verb' => 'POST')
	)
));
?>
