<?php

/**
 * This is required until NC 13.
 */

OCP\User::checkLoggedIn();

OCP\Util::addScript('ojsxc', 'settings/personal');

$config = \OC::$server->getConfig();
$tmpl = new \OCP\Template('ojsxc', 'settings/personal');

$currentUID = \OC::$server->getUserSession()->getUser()->getUID();
$options = $config->getUserValue($currentUID, 'ojsxc', 'options');

if ($options !== null) {
	$options = (array) json_decode($options, true);

	if (is_array($options)) {
		$loginFormEnable = null;
		if (isset($options['loginForm']) && is_array($options['loginForm']) && isset($options['loginForm']['enable'])) {
			$loginFormEnable = $options['loginForm']['enable'];
		}

		if ($loginFormEnable === true || $loginFormEnable === 'true') {
			$tmpl->assign('loginForm', 'enable');
		} elseif ($loginFormEnable === false || $loginFormEnable === 'false') {
			$tmpl->assign('loginForm', 'disable');
		} else {
			$tmpl->assign('loginForm', 'default');
		}
	}
}

return $tmpl->fetchPage();
