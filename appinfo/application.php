<?php

namespace OCA\OJSXC\AppInfo;

use OCA\OJSXC\Controller\ManagedServerController;
use OCA\OJSXC\Controller\SettingsController;
use OCA\OJSXC\Controller\ExternalApiController;
use OCA\OJSXC\Middleware\ExternalApiMiddleware;
use OCA\OJSXC\Command\RefreshRoster;
use OCA\OJSXC\Controller\HttpBindController;
use OCA\OJSXC\Db\IQRosterPushMapper;
use OCA\OJSXC\Db\MessageMapper;
use OCA\OJSXC\Db\PresenceMapper;
use OCA\OJSXC\Db\Stanza;
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
use OCP\AppFramework\App;
use OCP\IContainer;
use OCP\IRequest;
use OCP\IUserBackend;

class Application extends App {

	public const INTERNAL = 'internal';
	public const EXTERNAL = 'external';
	public const MANAGED = 'managed';

	private static $config = [];

	public function __construct(array $urlParams=array()){
		parent::__construct('ojsxc', $urlParams);
		$container = $this->getContainer();

		/** @var $config \OCP\IConfig */
		$configManager = $container->query('OCP\IConfig');

		self::$config['polling'] = $configManager->getSystemValue('ojsxc.polling',
			['sleep_time' => 1, 'max_cycles' => 10]);

		self::$config['polling']['timeout'] = self::$config['polling']['sleep_time'] * self::$config['polling']['max_cycles'] + 5;

		self::$config['use_memcache'] = $configManager->getSystemValue('ojsxc.use_memcache',
			['locking' => false]);


		/**
		 * Controllers
		 */
		$container->registerService('HttpBindController', function(IContainer $c) {
			return new HttpBindController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('OJSXC_UserId'),
				$c->query('StanzaMapper'),
				$c->query('IQHandler'),
				$c->query('MessageHandler'),
				$c->query('Host'),
				$this->getLock(),
				$c->query('OCP\ILogger'),
				$c->query('PresenceHandler'),
				$c->query('PresenceMapper'),
				file_get_contents("php://input"),
				self::$config['polling']['sleep_time'],
				self::$config['polling']['max_cycles'],
				$c->query('NewContentContainer'),
				$c->query('StanzaLogger')
			);
		});

		$container->registerService('SettingsController', function(IContainer $c) {
			return new SettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('OCP\IConfig'),
				$c->query('OCP\IUserManager'),
				\OC::$server->getUserSession()
			);
		});

		$container->registerService('ExternalApiController', function(IContainer $c) {
			return new ExternalApiController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('OCP\IUserManager'),
				$c->query('OCP\IUserSession'),
				$c->query('OCP\IGroupManager'),
				$c->query('OCP\ILogger')
			);
		});

		$container->registerService('ManagedServerController', function(IContainer $c) {
			return new ManagedServerController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('OCP\IURLGenerator'),
				$c->query('OCP\IConfig'),
				$c->query('OCP\IUserSession'),
				$c->query('OCP\ILogger'),
				$c->query('DataRetriever'),
				$c->query('OCP\Security\ISecureRandom'),
				'https://xmpp.jsxc.ch/registration'
			);
		});

		/**
		 * Middleware
		 */

		$container->registerService('ExternalApiMiddleware', function(IContainer $c) {
			return new ExternalApiMiddleware(
				$c->query('Request'),
				$c->query('OCP\IConfig'),
				$c->query('RawRequest')
			);
		});
		$container->registerMiddleware('ExternalApiMiddleware');

		/**
		 * Database Layer
		 */
		$container->registerService('MessageMapper', function(IContainer $c) use ($container) {
			return new MessageMapper(
				$container->getServer()->getDatabaseConnection(),
				$c->query('Host'),
				$c->query('StanzaLogger')
			);
		});

		$container->registerService('IQRosterPushMapper', function(IContainer $c) use ($container) {
			return new IQRosterPushMapper(
				$container->getServer()->getDatabaseConnection(),
				$c->query('Host'),
				$c->query('StanzaLogger')
			);
		});

		$container->registerService('StanzaMapper', function(IContainer $c) use ($container) {
			return new StanzaMapper(
				$container->getServer()->getDatabaseConnection(),
				$c->query('Host'),
				$c->query('StanzaLogger')
			);
		});

		$container->registerService('PresenceMapper', function(IContainer $c) use ($container) {
			return new PresenceMapper(
				$container->getServer()->getDatabaseConnection(),
				$c->query('Host'),
				$c->query('OJSXC_UserId'),
				$c->query('MessageMapper'),
				$c->query('NewContentContainer'),
				self::$config['polling']['timeout'],
				$c->query('UserProvider')
			);
		});


		/**
		 * XMPP Stanza Handlers
		 */
		$container->registerService('IQHandler', function(IContainer $c) {
			return new IQ(
				$c->query('OJSXC_UserId'),
				$c->query('Host'),
				$c->query('OCP\IUserManager'),
				$c->query('OCP\IConfig'),
				$c->query('UserProvider')
			);
		});

		$container->registerService('PresenceHandler', function(IContainer $c) {
			return new Presence(
				$c->query('OJSXC_UserId'),
				$c->query('Host'),
				$c->query('PresenceMapper'),
				$c->query('MessageMapper')
			);
		});

		$container->registerService('MessageHandler', function(IContainer $c) {
			return new Message(
				$c->query('OJSXC_UserId'),
				$c->query('Host'),
				$c->query('MessageMapper'),
				$c->query('UserProvider'),
				$c->query('OCP\ILogger')
			);
		});

		/**
		 * Config values
		 */
		$container->registerService('Host', function(IContainer $c) {
			/** @var IRequest $request */
			$request = $c->query('Request');
			return $request->getServerHost();
		});

		/**
		 * Helpers
		 */
		$container->registerService('NewContentContainer', function() {
			return new NewContentContainer();
		});

		$container->registerService('StanzaLogger', function(IContainer $c) {
			return new StanzaLogger(
				$c->query('\OCP\ILogger'),
				$c->query('UserId')
			);
		});


		$container->registerService('RosterPush', function($c) {
			return new RosterPush(
				$c->query('ServerContainer')->getUserManager(),
				$c->query('ServerContainer')->getUserSession(),
				$c->query('Host'),
				$c->query('IQRosterPushMapper'),
				$c->query('ServerContainer')->getDatabaseConnection(),
				$c->query('UserProvider')
			);
		});

		$container->registerService('UserHooks', function($c) {
			return new Hooks(
				$c->query('ServerContainer')->getUserManager(),
				$c->query('ServerContainer')->getUserSession(),
				$c->query('RosterPush'),
				$c->query('PresenceMapper'),
				$c->query('StanzaMapper'),
				$c->query('ServerContainer')->query('GroupManager')
			);
		});

		$container->registerService('UserProvider', function(IContainer $c) {
			if (self::contactsStoreApiSupported()) {
				return new ContactsStoreUserProvider(
					$c->query('OCP\Contacts\ContactsMenu\IContactsStore'),
					$c->query('ServerContainer')->getUserSession(),
					$c->query('ServerContainer')->getUserManager(),
					$c->query('OCP\IGroupManager'),
					$c->query('OCP\IConfig')
				);
			} else {
				return new UserManagerUserProvider(
					$c->query('ServerContainer')->getUserManager()
				);
			}
		});


		/**
		 * Commands
		 */
		$container->registerService('RefreshRosterCommand', function($c) {
			return new RefreshRoster(
				$c->query('ServerContainer')->getUserManager(),
				$c->query('RosterPush'),
				$c->query('PresenceMapper')
			);
		});

		/**
		 * A modified userID for use in OJSXC.
		 * This is automatically made lowercase.
		 */
		$container->registerParameter('OJSXC_UserId',
			 self::sanitizeUserId(self::convertToRealUID($container->query('UserId')))
		);

		/**
		 * Raw request body
		 */
		$container->registerService('RawRequest', function($c) {
			return new RawRequest();
		});

		/**
		 * Data retriever
		 */
		$container->registerService('DataRetriever', function($c) {
			return new DataRetriever();
		});


		/**
		 * Migrations
		 */
		$container->registerService('OCA\OJSXC\Migration\RefreshRoster', function(IContainer $c) {
			return new RefreshRosterMigration(
				$c->query('RosterPush'),
				$c->query('OCP\IConfig'),
				$c->query('OCP\ILogger')
			);
		});

	}

	/**
	 * @return ILock
	 */
	private function getLock() {
		$c = $this->getContainer();
		if (self::$config['use_memcache']['locking'] === true) {
			$cache = $c->getServer()->getMemCacheFactory();
			$version = \OC::$server->getSession()->get('OC_Version');
			if ($version[0] === 8 && $version[1] === 0){
				$c->getServer()->getLogger()->warning('OJSXC is configured to use memcache as backend for locking, but ownCloud version 8  doesn\'t suppor this.');
			} else if ($cache->isAvailable()) {
				$memcache = $cache->create('ojsxc');
				return new MemLock(
					$c->query('OJSXC_UserId'),
					$memcache
				);
			} else {
				$c->getServer()->getLogger()->warning('OJSXC is configured to use memcache as backend for locking, but no memcache is available.');
			}
		}

		// default
		return new DbLock(
			$c->query('OJSXC_UserId'),
			$c->query('OCP\IConfig'),
			$c->getServer()->getDatabaseConnection()
		);
	}


	public static function sanitizeUserId($providedUid) {
		return str_replace([" ", "'", "@"], ["_ojsxc_esc_space_", "_ojsxc_squote_space_", "_ojsxc_esc_at_"],
			$providedUid
		);
	}

	public static function deSanitize($providedUid) {
		return str_replace(["_ojsxc_esc_space_", "_ojsxc_squote_space_", "_ojsxc_esc_at_"], [" ", "'", "@"],
			$providedUid
		);
	}


	public static function convertToRealUID($providedUid) {
		$user = \OC::$server->getUserManager()->get($providedUid);
		if (is_null($user)) {
			return $providedUid;
		}

		$backends = \OC::$server->getUserManager()->getBackends();
		foreach ($backends as $backend) {
			if ($backend->getBackendName() === $user->getBackendClassName()) {
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
	public static function contactsStoreApiSupported() {
		$version = \OCP\Util::getVersion();
		return $version[0] >= 13;
	}

	public static function getServerType() {
		return \OC::$server->getConfig()->getAppValue('ojsxc', 'serverType', self::INTERNAL);
	}



}
