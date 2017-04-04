<?php

OCP\User::checkAdminUser();
OCP\JSON::callCheck();

$version = \OCP\Util::getVersion();
preg_match('/^([0-9]+)\.', $version, $versionMatches);
$majorVersion = intval($versionMatches[1]);

// copied from owncloud/settings/ajax/installapp.php
$lastConfirm = (int) \OC::$server->getSession()->get('last-password-confirm');
if ($majorVersion >= 11 && $lastConfirm < (time() - 30 * 60 + 15)) {
	$l = \OC::$server->getL10N('core');
	OC_JSON::error(array( 'data' => array( 'message' => $l->t('Password confirmation is required'))));
	exit();
}

$config = \OC::$server->getConfig();

$config->setAppValue('ojsxc', 'serverType', $_POST ['serverType']);
$config->setAppValue('ojsxc', 'boshUrl', trim($_POST ['boshUrl']));
$config->setAppValue('ojsxc', 'xmppDomain', trim($_POST ['xmppDomain']));
$config->setAppValue('ojsxc', 'xmppResource', trim($_POST ['xmppResource']));
$config->setAppValue('ojsxc', 'xmppOverwrite', getCheckboxValue($_POST ['xmppOverwrite']));
$config->setAppValue('ojsxc', 'xmppStartMinimized', getCheckboxValue($_POST ['xmppStartMinimized']));
$config->setAppValue('ojsxc', 'xmppPreferMail', getCheckboxValue($_POST ['xmppPreferMail']));

$config->setAppValue('ojsxc', 'iceUrl', trim($_POST ['iceUrl']));
$config->setAppValue('ojsxc', 'iceUsername', trim($_POST ['iceUsername']));
$config->setAppValue('ojsxc', 'iceCredential', $_POST ['iceCredential']);
$config->setAppValue('ojsxc', 'iceSecret', $_POST ['iceSecret']);
$config->setAppValue('ojsxc', 'iceTtl', $_POST ['iceTtl']);

$config->setAppValue('ojsxc', 'timeLimitedToken', getCheckboxValue($_POST ['timeLimitedToken']));

$config->setAppValue('ojsxc', 'firefoxExtension', $_POST ['firefoxExtension']);
$config->setAppValue('ojsxc', 'chromeExtension', $_POST ['chromeExtension']);

$externalServices = array();
foreach($_POST['externalServices'] as $es) {
   if (preg_match('/^(https:\/\/)?([\w\d*][\w\d-]*)(\.[\w\d-]+)+(:[\d]+)?$/', $es)) {
      $externalServices[] = $es;
   }
}
$config->setAppValue('ojsxc', 'externalServices', implode('|', $externalServices));

echo 'true';

function getCheckboxValue($var) {
	return (isset($var)) ? $var : 'false';
}
