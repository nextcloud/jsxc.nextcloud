<?php

OCP\User::checkAdminUser();

OCP\Util::addScript('ojsxc', 'settings/admin');

$config = \OC::$server->getConfig();
$tmpl = new OCP\Template('ojsxc', 'settings/admin');

$apiUrl = \OC::$server->getURLGenerator()->linkTo('ojsxc', 'ajax/externalApi.php');
$apiUrl = \OC::$server->getURLGenerator()->getAbsoluteURL($apiUrl);

$serverType = $config->getAppValue('ojsxc', 'serverType');

$tmpl->assign('serverType', (!empty($serverType))? $serverType : 'internal');
$tmpl->assign('boshUrl', $config->getAppValue('ojsxc', 'boshUrl'));
$tmpl->assign('xmppDomain', $config->getAppValue('ojsxc', 'xmppDomain'));
$tmpl->assign('xmppPreferMail', $config->getAppValue('ojsxc', 'xmppPreferMail'));
$tmpl->assign('xmppResource', $config->getAppValue('ojsxc', 'xmppResource'));
$tmpl->assign('xmppOverwrite', $config->getAppValue('ojsxc', 'xmppOverwrite'));
$tmpl->assign('xmppStartMinimized', $config->getAppValue('ojsxc', 'xmppStartMinimized'));
$tmpl->assign('iceUrl', $config->getAppValue('ojsxc', 'iceUrl'));
$tmpl->assign('iceUsername', $config->getAppValue('ojsxc', 'iceUsername'));
$tmpl->assign('iceCredential', $config->getAppValue('ojsxc', 'iceCredential'));
$tmpl->assign('iceSecret', $config->getAppValue('ojsxc', 'iceSecret'));
$tmpl->assign('iceTtl', $config->getAppValue('ojsxc', 'iceTtl'));
$tmpl->assign('firefoxExtension', $config->getAppValue('ojsxc', 'firefoxExtension'));
$tmpl->assign('chromeExtension', $config->getAppValue('ojsxc', 'chromeExtension'));

$externalServices = $config->getAppValue('ojsxc', 'externalServices');
$externalServices = explode("|", $externalServices);
$tmpl->assign('externalServices', $externalServices);

$tmpl->assign('apiUrl', $apiUrl);
$tmpl->assign('apiSecret', $config->getAppValue('ojsxc', 'apiSecret'));
$tmpl->assign('timeLimitedToken', $config->getAppValue('ojsxc', 'timeLimitedToken'));
$tmpl->assign('userId', \OC::$server->getUserSession()->getUser()->getUID());
$tmpl->assign('managedServer', $this->config->getAppValue('ojsxc', 'managedServer'));

return $tmpl->fetchPage();
