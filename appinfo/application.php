<?php

namespace OCA\OJSXC\AppInfo;

use OCA\OJSXC\Controller\HttpBindController;
use OCA\OJSXC\Db\MessageMapper;
use OCA\OJSXC\Db\PresenceMapper;
use OCA\OJSXC\Db\StanzaMapper;
use OCA\OJSXC\NewContentContainer;
use OCA\OJSXC\StanzaHandlers\IQ;
use OCA\OJSXC\StanzaHandlers\Message;
use OCA\OJSXC\StanzaHandlers\Presence;
use OCP\AppFramework\App;
use OCA\OJSXC\ILock;
use OCA\OJSXC\DbLock;
use OCA\OJSXC\MemLock;
use OCP\IContainer;
use OCP\IRequest;

class Application extends App {

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


		$container->registerService('HttpBindController', function(IContainer $c) {
			return new HttpBindController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserId'),
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
				$c->query('NewContentContainer')
			);
		});

		/**
		 * Database Layer
		 */
		$container->registerService('MessageMapper', function(IContainer $c) use ($container) {
			return new MessageMapper(
				$container->getServer()->getDatabaseConnection(),
				$c->query('Host')
			);
		});

		$container->registerService('StanzaMapper', function(IContainer $c) use ($container) {
			return new StanzaMapper(
				$container->getServer()->getDatabaseConnection(),
				$c->query('Host')
			);
		});

		$container->registerService('PresenceMapper', function(IContainer $c) use ($container) {
			return new PresenceMapper(
				$container->getServer()->getDatabaseConnection(),
				$c->query('Host'),
				$c->query('UserId'),
				$c->query('MessageMapper'),
				$c->query('NewContentContainer'),
				self::$config['polling']['timeout']
			);
		});


		/**
		 * XMPP Stanza Handlers
		 */
		$container->registerService('IQHandler', function(IContainer $c) {
			return new IQ(
				$c->query('UserId'),
				$c->query('Host'),
				$c->query('OCP\IUserManager')
			);
		});

		$container->registerService('PresenceHandler', function(IContainer $c) {
			return new Presence(
				$c->query('UserId'),
				$c->query('Host'),
				$c->query('PresenceMapper'),
				$c->query('MessageMapper')
			);
		});

		$container->registerService('MessageHandler', function(IContainer $c) {
			return new Message(
				$c->query('UserId'),
				$c->query('Host'),
				$c->query('MessageMapper')
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

		$container->registerService('NewContentContainer', function() {
			return new NewContentContainer();
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
			if ($version[0] === 8 && $version[1] == 0){
				$c->getServer()->getLogger()->warning('OJSXC is configured to use memcache as backend for locking, but ownCloud version 8  doesn\'t suppor this.');
			} else if ($cache->isAvailable()) {
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
			$c->query('OCP\IConfig')
		);

	}
}
