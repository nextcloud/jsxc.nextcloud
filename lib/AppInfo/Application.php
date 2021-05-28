<?php

namespace OCA\OJSXC\AppInfo;

use OCA\OJSXC\Controller\ManagedServerController;
use OCA\OJSXC\Controller\SettingsController;
use OCA\OJSXC\Controller\ExternalApiController;
use OCA\OJSXC\Controller\JavascriptController;
use OCA\OJSXC\Middleware\ExternalApiMiddleware;
use OCA\OJSXC\Command\RefreshRoster;
use OCA\OJSXC\Command\ServerSharing;
use OCA\OJSXC\Controller\HttpBindController;
use OCA\OJSXC\Db\IQRosterPushMapper;
use OCA\OJSXC\Db\MessageMapper;
use OCA\OJSXC\Db\PresenceMapper;
use OCA\OJSXC\Db\StanzaMapper;
use OCA\OJSXC\Migration\RefreshRoster as RefreshRosterMigration;
use OCA\OJSXC\NewContentContainer;
use OCA\OJSXC\RosterPush;
use OCA\OJSXC\StanzaHandlers\IQ;
use OCA\OJSXC\StanzaHandlers\Message;
use OCA\OJSXC\StanzaHandlers\Presence;
use OCA\OJSXC\StanzaLogger;
use OCA\OJSXC\RawRequest;
use OCA\OJSXC\DataRetriever;
use OCA\OJSXC\ILock;
use OCA\OJSXC\DbLock;
use OCA\OJSXC\MemLock;
use OCA\OJSXC\Hooks;
use OCA\OJSXC\UserManagerUserProvider;
use OCA\OJSXC\ContactsStoreUserProvider;
use OCA\OJSXC\Config;
use OCA\OJSXC\IUserProvider;
use OCP\IContainer;
use OCP\IRequest;
use OCP\IUserBackend;
use OCA\OJSXC\Migration\MigrateConfig;
use OCP\AppFramework\App;

class Application extends App
{
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

		self::$config['polling'] = $configManager->getSystemValue(
			'ojsxc.polling',
			['sleep_time' => 1, 'max_cycles' => 10]
		);

		self::$config['polling']['timeout'] = self::$config['polling']['sleep_time'] * self::$config['polling']['max_cycles'] + 5;

		self::$config['use_memcache'] = $configManager->getSystemValue(
			'ojsxc.use_memcache',
			['locking' => false]
		);


		/**
		 * Controllers
		 */
		$container->registerService('HttpBindController', function (IContainer $c) {
			return new HttpBindController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserId'),
				$c->query(StanzaMapper::class),
				$c->query(IQ::class),
				$c->query(Message::class),
				$this->getLock(),
				$c->query(Presence::class),
				$c->query(PresenceMapper::class),
				file_get_contents("php://input"),
				self::$config['polling']['sleep_time'],
				self::$config['polling']['max_cycles'],
				$c->query(NewContentContainer::class),
				$c->query(StanzaLogger::class)
			);
		});

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

		/**
		 * Database Layer
		 */
		$container->registerService(MessageMapper::class, function (IContainer $c) use ($container) {
			return new MessageMapper(
				$container->getServer()->getDatabaseConnection(),
				$c->query('Host'),
				$c->query(StanzaLogger::class)
			);
		});

		$container->registerService(IQRosterPushMapper::class, function (IContainer $c) use ($container) {
			return new IQRosterPushMapper(
				$container->getServer()->getDatabaseConnection(),
				$c->query('Host'),
				$c->query(StanzaLogger::class)
			);
		});

		$container->registerService(StanzaMapper::class, function (IContainer $c) use ($container) {
			return new StanzaMapper(
				$container->getServer()->getDatabaseConnection(),
				$c->query('Host'),
				$c->query(StanzaLogger::class)
			);
		});

		$container->registerService(PresenceMapper::class, function (IContainer $c) use ($container) {
			return new PresenceMapper(
				$container->getServer()->getDatabaseConnection(),
				$c->query('Host'),
				$c->query('UserId'),
				$c->query(MessageMapper::class),
				$c->query(NewContentContainer::class),
				self::$config['polling']['timeout'],
				$c->query(IUserProvider::class)
			);
		});


		/**
		 * XMPP Stanza Handlers
		 */
		$container->registerService(IQ::class, function (IContainer $c) {
			return new IQ(
				$c->query('UserId'),
				$c->query('Host'),
				$c->query(\OCP\IUserManager::class),
				$c->query(\OCP\IConfig::class),
				$c->query(IUserProvider::class)
			);
		});

		/**
		 * Config values
		 */
		$container->registerService('Host', function (IContainer $c) {
			/** @var IRequest $request */
			$request = $c->query('Request');
			return preg_replace('/:\d+$/', '', $request->getServerHost());
		});

		/**
		 * Helpers
		 */

		$container->registerService(IUserProvider::class, function (IContainer $c) {
			if (self::contactsStoreApiSupported()) {
				return new ContactsStoreUserProvider(
					$c->query(\OCP\Contacts\ContactsMenu\IContactsStore::class),
					$c->query('ServerContainer')->getUserSession(),
					$c->query('ServerContainer')->getUserManager(),
					$c->query(\OCP\IGroupManager::class),
					$c->query(\OCP\IConfig::class)
				);
			} else {
				return new UserManagerUserProvider(
					$c->query('ServerContainer')->getUserManager()
				);
			}
		});
	}

	/**
	 * @return ILock
	 */
	private function getLock()
	{
		$c = $this->getContainer();
		if (self::$config['use_memcache']['locking'] === true) {
			$cache = $c->getServer()->getMemCacheFactory();
			$version = \OC::$server->getSession()->get('OC_Version');
			if ($version[0] === 8 && $version[1] === 0) {
				$c->getServer()->getLogger()->warning('OJSXC is configured to use memcache as backend for locking, but ownCloud version 8  doesn\'t suppor this.');
			} elseif ($cache->isAvailable()) {
				$memcache = $cache->create('ojsxc');
				return new MemLock(
					$c->query('UserId'),
					$memcache
				);
			} else {
				$c->getServer()->getLogger()->warning('OJSXC is configured to use memcache as backend for locking, but no memcache is available.');
			}
		}

		// default
		return new DbLock(
			$c->query('UserId'),
			$c->query(\OCP\IConfig::class),
			$c->getServer()->getDatabaseConnection()
		);
	}


	public static function sanitizeUserId($providedUid)
	{
		return str_replace(
			[" ", "'", "@"],
			["_ojsxc_esc_space_", "_ojsxc_squote_space_", "_ojsxc_esc_at_"],
			$providedUid
		);
	}

	public static function deSanitize($providedUid)
	{
		return str_replace(
			["_ojsxc_esc_space_", "_ojsxc_squote_space_", "_ojsxc_esc_at_"],
			[" ", "'", "@"],
			$providedUid
		);
	}


	public static function convertToRealUID($providedUid)
	{
		$user = \OC::$server->getUserManager()->get($providedUid);
		if (is_null($user)) {
			return $providedUid;
		}

		$backends = \OC::$server->getUserManager()->getBackends();
		foreach ($backends as $backend) {
			if ($backend instanceof IUserBackend) {
				$backendName = $backend->getBackendName();
			} else {
				$backendName = get_class($backend);
			}
			if ($backendName === $user->getBackendClassName()) {
				if (method_exists($backend, 'loginName2UserName')) {
					$uid = $backend->loginName2UserName($providedUid);
					if ($uid !== false) {
						return $uid;
					}
				}
			}
		}

		return $providedUid;
	}

	/**
	 * @return bool whether the ContactsStore API is enabled
	 */
	public static function contactsStoreApiSupported()
	{
		$version = \OCP\Util::getVersion();
		if ($version[0] >= 13 && \OC::$server->getConfig()->getAppValue('ojsxc', 'use_server_sharing_settings', 'no') === 'yes') {
			// ContactsStore API is supported and feature is enabled
			return true;
		}
		return false;
	}

	public static function getServerType()
	{
		return \OC::$server->getConfig()->getAppValue('ojsxc', Config::XMPP_SERVER_TYPE, self::INTERNAL);
	}
}
