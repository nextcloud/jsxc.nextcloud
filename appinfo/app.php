<?php

use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Hooks;

\OCP\App::registerPersonal('ojsxc', 'settings/personal');

$isDevEnv = \OC::$server->getConfig()->getSystemValue('jsxc.environment') === 'dev';
$jsxc_root = ($isDevEnv)? 'jsxc/dev/' : 'jsxc/';
$jsProdSuffix = (!$isDevEnv)? '.min' : '';

OCP\Util::addScript ( 'ojsxc', $jsxc_root.'lib/jquery.slimscroll' );
OCP\Util::addScript ( 'ojsxc', $jsxc_root.'lib/jquery.fullscreen' );
OCP\Util::addScript ( 'ojsxc', $jsxc_root.'lib/jsxc.dep'.$jsProdSuffix );
OCP\Util::addScript ( 'ojsxc', $jsxc_root.'jsxc'.$jsProdSuffix );
OCP\Util::addScript ( 'ojsxc', 'ojsxc');

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

$config = \OC::$server->getConfig();
$apiSecret = $config->getAppValue('ojsxc', 'apiSecret');
if(!$apiSecret) {
   $apiSecret = \OC::$server->getSecureRandom()->generate(23);
   $config->setAppValue('ojsxc', 'apiSecret', $apiSecret);
}

if (Application::getServerType() === 'internal') {
	Hooks::register();
}

if (!class_exists("\\Sabre\\Xml\\Version")) {
    require_once __DIR__ . "/../vendor/autoload.php";
}

?>
