<?php

namespace OCA\OJSXC\AppInfo;

use OCA\OJSXC\Controller\ManagedServerController;
use OCA\OJSXC\Middleware\ExternalApiMiddleware;
use OCA\OJSXC\Config;
use OCA\OJSXC\Listener\AddFeaturePolicyListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IConfig;
use OCP\IServerContainer;
use OCP\IURLGenerator;
use OCP\Security\FeaturePolicy\AddFeaturePolicyEvent;
use OCP\Security\IContentSecurityPolicyManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap
{
	const NOT_CONFIGURED = 'not-configured';
	const INTERNAL = 'internal';
	const EXTERNAL = 'external';
	const MANAGED = 'managed';

	public function __construct(array $urlParams = [])
	{
		parent::__construct('ojsxc', $urlParams);
	}

	public function register(IRegistrationContext $context): void
	{
		$context->registerService('ManagedServerController', function (ContainerInterface $c) {
			return new ManagedServerController(
				$c->get('AppName'),
				$c->get('Request'),
				$c->get(\OCP\IURLGenerator::class),
				$c->get(Config::class),
				$c->get(\OCP\IUserSession::class),
				$c->get(LoggerInterface::class),
				$c->get('DataRetriever'),
				$c->get(\OCP\Security\ISecureRandom::class),
				$c->get(\OCP\App\IAppManager::class),
				'https://xmpp.jsxc.ch/registration'
			);
		});

		$context->registerService('ExternalApiMiddleware', function (ContainerInterface $c) {
			return new ExternalApiMiddleware(
				$c->get('Request'),
				$c->get(\OCP\IConfig::class),
				$c->get('RawRequest')
			);
		});

		$context->registerMiddleware(ExternalApiMiddleware::class);

		$context->registerEventListener(AddFeaturePolicyEvent::class, AddFeaturePolicyListener::class);
	}

	public function boot(IBootContext $context): void
	{
		$this->adjustContentSecurityPolicy($context->getServerContainer());
		$this->injectFiles($context->getServerContainer());
	}

	private function adjustContentSecurityPolicy(IServerContainer $container)
	{
		/** @var IConfig */
		$config = $container->get(IConfig::class);

		/** @var IContentSecurityPolicyManager */
		$manager = $container->get(IContentSecurityPolicyManager::class);

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

		$policy->addAllowedWorkerSrcDomain('\'self\'');

		$policy->addAllowedConnectDomain('\'self\'');

		$boshUrl = $config->getAppValue('ojsxc', Config::XMPP_URL);

		if (preg_match('#^(https?:)?//([a-z0-9][a-z0-9\-.]*[a-z0-9](:[0-9]+)?)/#i', $boshUrl, $matches)) {
			$boshDomain = $matches[2];

			$policy->addAllowedConnectDomain($boshDomain);
		}

		$externalServices = \OC::$server->getConfig()->getAppValue('ojsxc', Config::EXTERNAL_SERVICES);
		$externalServices = explode("|", $externalServices);

		foreach ($externalServices as $es) {
			$policy->addAllowedConnectDomain($es);
		}

		$manager->addDefaultPolicy($policy);
	}

	private function injectFiles(IServerContainer $container)
	{
		/** @var IURLGenerator */
		$urlGenerator = $container->get(IURLGenerator::class);

		/** @var IConfig */
		$config = $container->get(IConfig::class);

		$versionHashSuffix = '';

		if (!$config->getSystemValue('debug', false)) {
			$appVersion = $config->getAppValue('ojsxc', 'installed_version');
			$versionHashSuffix = '?v=' . substr(md5($appVersion), 0, 8);
		}

		$this->addScript($urlGenerator->linkToRoute('ojsxc.javascript.generalConfig'));
		$this->addScript($urlGenerator->linkTo('ojsxc', 'js/libsignal/libsignal-protocol.js') . $versionHashSuffix);
		$this->addScript($urlGenerator->linkTo('ojsxc', 'js/jsxc/jsxc.bundle.js') . $versionHashSuffix);
		$this->addScript($urlGenerator->linkTo('ojsxc', 'js/bundle.js') . $versionHashSuffix);

		// workaround to overwrite localStorage.clear
		\OCP\Util::addScript('ojsxc', 'overwriteClearStorage', true);

		\OCP\Util::addStyle('ojsxc', '../js/jsxc/styles/jsxc.bundle');
		\OCP\Util::addStyle('ojsxc', 'bundle');
	}

	private function addScript(string $src)
	{
		// use addHeader instead of addScript, because addScript adds js suffix to every src
		\OCP\Util::addHeader(
			'script',
			[
				'src' => $src,
				'nonce' => \OC::$server->getContentSecurityPolicyNonceManager()->getNonce(),
			],
			''
		);
	}

	public static function getServerType()
	{
		return \OC::$server->getConfig()->getAppValue('ojsxc', Config::XMPP_SERVER_TYPE, self::NOT_CONFIGURED);
	}
}
