<?php

namespace OCA\OJSXC\AppInfo;

use OCA\OJSXC\Controller\ManagedServerController;
use OCA\OJSXC\Middleware\ExternalApiMiddleware;
use OCA\OJSXC\Config;
use OCP\IContainer;
use OCP\IRequest;
use OCP\AppFramework\App;

class Application extends App
{
	const NOT_CONFIGURED = 'not-configured';
	const INTERNAL = 'internal';
	const EXTERNAL = 'external';
	const MANAGED = 'managed';

	private static $config = [];

	public function __construct(array $urlParams = [])
	{
		parent::__construct('ojsxc', $urlParams);
		$container = $this->getContainer();

		/** @var $config \OCP\IConfig */
		$configManager = $container->query(\OCP\IConfig::class);


		$container->registerService('ManagedServerController', function (IContainer $c) {
			return new ManagedServerController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query(\OCP\IURLGenerator::class),
				$c->query(Config::class),
				$c->query(\OCP\IUserSession::class),
				$c->query(\OCP\ILogger::class),
				$c->query('DataRetriever'),
				$c->query(\OCP\Security\ISecureRandom::class),
				$c->query(\OCP\App\IAppManager::class),
				'https://xmpp.jsxc.ch/registration'
			);
		});

		/**
		 * Middleware
		 */
		$container->registerService('ExternalApiMiddleware', function (IContainer $c) {
			return new ExternalApiMiddleware(
				$c->query('Request'),
				$c->query(\OCP\IConfig::class),
				$c->query('RawRequest')
			);
		});
		$container->registerMiddleware('ExternalApiMiddleware');
	}

	public static function getServerType()
	{
		return \OC::$server->getConfig()->getAppValue('ojsxc', Config::XMPP_SERVER_TYPE, self::NOT_CONFIGURED);
	}
}
