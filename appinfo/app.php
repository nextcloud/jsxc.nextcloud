<?php

use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Hooks;
use OCA\OJSXC\Config;

$config = \OC::$server->getConfig();
$versionHashSuffix = '';

if (!$config->getSystemValue('debug', false)) {
	$appVersion = $config->getAppValue('ojsxc', 'installed_version');
	$versionHashSuffix = '?v=' . substr(md5($appVersion), 0, 8);
}

function addScript($src) {
	// use addHeader instead of addScript, because addScript adds js suffix to every src
	\OCP\Util::addHeader( 'script', [
			'src' => $src,
			'nonce' => \OC::$server->getContentSecurityPolicyNonceManager()->getNonce(),
		], ''
	);
}

$urlGenerator = \OC::$server->getURLGenerator();

addScript($urlGenerator->linkToRoute('ojsxc.javascript.generalConfig'));
addScript($urlGenerator->linkTo('ojsxc', 'js/libsignal/libsignal-protocol.js') . $versionHashSuffix);
addScript($urlGenerator->linkTo('ojsxc', 'js/jsxc/jsxc.bundle.js') . $versionHashSuffix);
addScript($urlGenerator->linkTo('ojsxc', 'js/bundle.js') . $versionHashSuffix);

// workaround to overwrite localStorage.clear
\OCP\Util::addScript( 'ojsxc', 'overwriteClearStorage', true );

\OCP\Util::addStyle ( 'ojsxc', '../js/jsxc/styles/jsxc.bundle' );
\OCP\Util::addStyle ( 'ojsxc', 'bundle' );

$dispatcher = \OC::$server->getEventDispatcher();
$dispatcher->addListener(\OCP\Security\FeaturePolicy\AddFeaturePolicyEvent::class, function (\OCP\Security\FeaturePolicy\AddFeaturePolicyEvent $e) {
        $fp = new \OCP\AppFramework\Http\EmptyFeaturePolicy();

        $fp->addAllowedGeoLocationDomain('\'self\'');
        $fp->addAllowedCameraDomain('\'self\'');
        $fp->addAllowedFullScreenDomain('\'self\'');
        $fp->addAllowedMicrophoneDomain('\'self\'');

        $e->addPolicy($fp);
});

if(class_exists('\\OCP\\AppFramework\\Http\\EmptyContentSecurityPolicy')) {
	$manager = \OC::$server->getContentSecurityPolicyManager();
	$policy = new \OCP\AppFramework\Http\EmptyContentSecurityPolicy();

	$policy->addAllowedStyleDomain('\'self\'');
	$policy->addAllowedStyleDomain('\'unsafe-inline\'');

	$policy->addAllowedScriptDomain('\'self\'');

	if ($config->getSystemValue('jsxc.environment', 'production') === 'development') {
		// required for source maps
		$policy->addAllowedScriptDomain('\'unsafe-eval\'');
	}

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

$apiSecret = $config->getAppValue('ojsxc', Config::API_SECRET);
if(!$apiSecret) {
   $apiSecret = \OC::$server->getSecureRandom()->generate(23);
   $config->setAppValue('ojsxc', Config::API_SECRET, $apiSecret);
}

if (Application::getServerType() === Application::INTERNAL) {
	Hooks::register();
}

if (!class_exists("\\Sabre\\Xml\\Version")) {
    require_once __DIR__ . "/../vendor/autoload.php";
}

?>
