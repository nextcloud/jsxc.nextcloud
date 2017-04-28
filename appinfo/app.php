<?php

/**
 * ownCloud - JavaScript XMPP Chat
 *
 * Copyright (c) 2014-2015 Klaus Herberth <klaus@jsxc.org> <br>
 * Released under the MIT license
 * @author Klaus Herberth <klaus@jsxc.org>
 */
if (!interface_exists('\OCP\Settings\ISettings')) {
	\OCP\App::registerAdmin ( 'ojsxc', 'settings/admin' );
}

\OCP\App::registerPersonal('ojsxc', 'settings/personal');

$jsxc_root = (defined('JSXC_ENV') && JSXC_ENV === 'dev')? 'jsxc/dev/' : 'jsxc/';

OCP\Util::addScript ( 'ojsxc', $jsxc_root.'lib/jquery.slimscroll' );
OCP\Util::addScript ( 'ojsxc', $jsxc_root.'lib/jquery.fullscreen' );
OCP\Util::addScript ( 'ojsxc', $jsxc_root.'lib/jsxc.dep' );
OCP\Util::addScript ( 'ojsxc', $jsxc_root.'jsxc' );
OCP\Util::addScript('ojsxc', 'ojsxc');

// ############# CSS #############
OCP\Util::addStyle ( 'ojsxc', 'jsxc.oc' );

if(class_exists('\\OCP\\AppFramework\\Http\\EmptyContentSecurityPolicy')) {
	$manager = \OC::$server->getContentSecurityPolicyManager();
	$policy = new \OCP\AppFramework\Http\EmptyContentSecurityPolicy();

	$policy->addAllowedStyleDomain('\'self\'');
	$policy->addAllowedStyleDomain('\'unsafe-inline\'');

	$policy->addAllowedScriptDomain('\'self\'');

	$policy->addAllowedImageDomain('\'self\'');
	$policy->addAllowedImageDomain('data:');
	$policy->addAllowedImageDomain('blob:');

	$policy->addAllowedMediaDomain('\'self\'');
	$policy->addAllowedMediaDomain('blob:');

	$policy->addAllowedChildSrcDomain('\'self\'');

	$policy->addAllowedConnectDomain('\'self\'');

	$boshUrl = \OC::$server->getConfig()->getAppValue('ojsxc', 'boshUrl');

	if(preg_match('#^(https?:)?//([a-z0-9][a-z0-9\-.]*[a-z0-9](:[0-9]+)?)/#i', $boshUrl, $matches)) {
		$boshDomain = $matches[2];

		$policy->addAllowedConnectDomain($boshDomain);
	}

	$externalServices = \OC::$server->getConfig()->getAppValue('ojsxc', 'externalServices');
	$externalServices = explode("|", $externalServices);

	foreach($externalServices as $es) {
		$policy->addAllowedConnectDomain($es);
	}

	$manager->addDefaultPolicy($policy);
}

require_once __DIR__ ."/../vendor/autoload.php";

?>
