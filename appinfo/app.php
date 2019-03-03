<?php

use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Hooks;
use OCA\OJSXC\Config;

\OCP\App::registerPersonal('ojsxc', 'settings/personal');

OCP\Util::addScript ( 'ojsxc', 'config' );
OCP\Util::addScript ( 'ojsxc', 'libsignal/libsignal-protocol' );
OCP\Util::addScript ( 'ojsxc', 'jsxc/jsxc.bundle' );
OCP\Util::addScript ( 'ojsxc', 'bundle');

OCP\Util::addStyle ( 'ojsxc', '../js/jsxc/styles/jsxc.bundle' );
OCP\Util::addStyle ( 'ojsxc', 'bundle' );

if(class_exists('\\OCP\\AppFramework\\Http\\EmptyContentSecurityPolicy')) {
	$manager = \OC::$server->getContentSecurityPolicyManager();
	$policy = new \OCP\AppFramework\Http\EmptyContentSecurityPolicy();

	$policy->addAllowedStyleDomain('\'self\'');
	$policy->addAllowedStyleDomain('\'unsafe-inline\'');

	$policy->addAllowedScriptDomain('\'self\' \'unsafe-eval\'');

	$policy->addAllowedImageDomain('\'self\'');
	$policy->addAllowedImageDomain('data:');
	$policy->addAllowedImageDomain('blob:');

	$policy->addAllowedMediaDomain('\'self\'');
	$policy->addAllowedMediaDomain('blob:');

	$policy->addAllowedChildSrcDomain('\'self\'');

	$policy->addAllowedConnectDomain('\'self\'');

	$boshUrl = \OC::$server->getConfig()->getAppValue('ojsxc', Config::XMPP_URL);

	if(preg_match('#^(https?:)?//([a-z0-9][a-z0-9\-.]*[a-z0-9](:[0-9]+)?)/#i', $boshUrl, $matches)) {
		$boshDomain = $matches[2];

		$policy->addAllowedConnectDomain($boshDomain);
	}

	$externalServices = \OC::$server->getConfig()->getAppValue('ojsxc', Config::EXTERNAL_SERVICES);
	$externalServices = explode("|", $externalServices);

	foreach($externalServices as $es) {
		$policy->addAllowedConnectDomain($es);
	}

	$manager->addDefaultPolicy($policy);
}

$config = \OC::$server->getConfig();
$apiSecret = $config->getAppValue('ojsxc', Config::API_SECRET);
if(!$apiSecret) {
   $apiSecret = \OC::$server->getSecureRandom()->generate(23);
   $config->setAppValue('ojsxc', Config::API_SECRET, $apiSecret);
}

if (Application::getServerType() === 'internal') {
	Hooks::register();
}

if (!class_exists("\\Sabre\\Xml\\Version")) {
    require_once __DIR__ . "/../vendor/autoload.php";
}

?>
