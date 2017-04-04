<?php

OCP\User::checkAdminUser();

OCP\Util::addScript('ojsxc', 'settings/admin');

$config = \OC::$server->getConfig();
$tmpl = new OCP\Template('ojsxc', 'settings/admin');

$tmpl->assign('serverType', $config->getAppValue('ojsxc', 'serverType'));
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

$apiSecret = $config->getAppValue('ojsxc', 'apiSecret');
if(!$apiSecret) {
   $apiSecret = \OC::$server->getSecureRandom()->generate(23);
   $config->setAppValue('ojsxc', 'apiSecret', $apiSecret);
}
$tmpl->assign('apiSecret', $apiSecret);
$tmpl->assign('timeLimitedToken', $config->getAppValue('ojsxc', 'timeLimitedToken'));

return $tmpl->fetchPage();
