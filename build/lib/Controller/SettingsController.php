<?php

namespace OCA\OJSXC\Controller;

use OCP\AppFramework\Controller;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCA\OJSXC\TimeLimitedToken;

class SettingsController extends Controller
{
	private $config;

	private $userManager;

	private $userSession;

	public function __construct(
		$appName,
   IRequest $request,
   IConfig $config,
   IUserManager $userManager,
   IUserSession $userSession
	) {
		parent::__construct($appName, $request);

		$this->config = $config;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
	}

	/**
	* @NoAdminRequired
	* @PublicPage
	*/
	public function index()
	{
		$currentUser = $this->getCurrentUser();

		if (!$currentUser) {
			return [
			'result' => 'noauth',
		 ];
		}

		$currentUID = $currentUser->getUID();

		$serverType = $this->getAppValue('serverType');

		$data = [
		 'serverType' => (!empty($serverType))? $serverType : 'internal',
		 'loginForm' => [
			'startMinimized' => $this->getBooleanAppValue('xmppStartMinimized')
			]
		 ];

		if ($data ['serverType'] === 'internal') {
			$data['adminSettings']['xmppDomain'] = $this->request->getServerHost();

			return [
			   'result' => 'success',
			   'data' => $data,
			];
		}

		$data ['screenMediaExtension'] = [
			'firefox' => trim($this->getAppValue('firefoxExtension')),
			'chrome' => trim($this->getAppValue('chromeExtension'))
		 ];

		$data ['xmpp'] = [
			'url' => trim($this->getAppValue('boshUrl')),
			'domain' => trim($this->getAppValue('xmppDomain')),
			'resource' => trim($this->getAppValue('xmppResource')),
			'overwrite' => $this->getBooleanAppValue('xmppOverwrite'),
			'onlogin' => null
		 ];

		$data ['adminSettings'] = [
			'xmppDomain' => trim($this->getAppValue('xmppDomain'))
		 ];

		if ($this->getBooleanAppValue('xmppPreferMail') && !$this->getBooleanAppValue('timeLimitedToken')) {
			$mail = $this->config->getUserValue($currentUID, 'settings', 'email');

			if ($mail !== null) {
				list($u, $d) = explode("@", $mail, 2);
				if ($d !== null && $d !== "") {
					$data ['xmpp'] ['username'] = strtolower($u);
					$data ['xmpp'] ['domain'] = strtolower($d);
				}
			}
		}

		if ($this->getBooleanAppValue('timeLimitedToken')) {
			$data['xmpp']['username'] = strtolower($currentUID);

			$token = $this->generateTimeLimitedToken($data['xmpp']['username'], $data['xmpp']['domain']);

			$data['xmpp']['password'] = $token;
		}

		$data = $this->overwriteByUserDefined($currentUID, $data);

		return [
			'result' => 'success',
			'data' => $data,
		 ];
	}

	public function setAdmin()
	{
		if ($this->isPasswordConfirmationRequired()) {
			$l = \OC::$server->getL10N('core');

			return [
			'status' => 'error',
			'data' => [
			   'message' => $l->t('Password confirmation is required')
			]
		 ];
		}

		$this->setAppValue('serverType', $this->getParam('serverType'));
		$this->setAppValue('boshUrl', $this->getTrimParam('boshUrl'));
		$this->setAppValue('xmppDomain', $this->getTrimParam('xmppDomain'));
		$this->setAppValue('xmppResource', $this->getTrimParam('xmppResource'));
		$this->setAppValue('xmppOverwrite', $this->getCheckboxParam('xmppOverwrite'));
		$this->setAppValue('xmppStartMinimized', $this->getCheckboxParam('xmppStartMinimized'));
		$this->setAppValue('xmppPreferMail', $this->getCheckboxParam('xmppPreferMail'));

		$this->setAppValue('iceUrl', $this->getTrimParam('iceUrl'));
		$this->setAppValue('iceUsername', $this->getTrimParam('iceUsername'));
		$this->setAppValue('iceCredential', $this->getParam('iceCredential'));
		$this->setAppValue('iceSecret', $this->getParam('iceSecret'));
		$this->setAppValue('iceTtl', $this->getParam('iceTtl'));

		$this->setAppValue('timeLimitedToken', $this->getCheckboxParam('timeLimitedToken'));

		$this->setAppValue('firefoxExtension', $this->getParam('firefoxExtension'));
		$this->setAppValue('chromeExtension', $this->getParam('chromeExtension'));

		$externalServices = [];
		foreach ($this->getParam('externalServices') as $es) {
			if (preg_match('/^(https:\/\/)?([\w\d*][\w\d-]*)(\.[\w\d-]+)+(:[\d]+)?$/', $es)) {
				$externalServices[] = $es;
			}
		}
		$this->setAppValue('externalServices', implode('|', $externalServices));

		return [
		 'status' => 'success'
	  ];
	}

	/**
	* @NoAdminRequired
	*/
	public function setUser()
	{
		$uid = $this->userSession->getUser()->getUID();

		$options = $this->config->getUserValue($uid, 'ojsxc', 'options');
		$options = json_decode($options, true);

		foreach ($_POST as $key => $val) {
			$options[$key] = $val;
		}

		$this->config->setUserValue($uid, 'ojsxc', 'options', json_encode($options));

		return [
		 'status' => 'success'
	  ];
	}

	/**
	* @NoAdminRequired
	*/
	public function getIceServers()
	{
		$secret = $this->getAppValue('iceSecret');
		$ttl = $this->getAppValue('iceTtl', 3600 * 24); // one day (according to TURN-REST-API)
		$urlString = $this->getAppValue('iceUrl');
		$username = $this->getAppValue('iceUsername', '');
		$credential = $this->getAppValue('iceCredential', '');

		$urls = [];
		foreach (preg_split('/[\s,]+/', $urlString) as $url) {
			if (preg_match('/^(turn|stun):/', $url)) {
				$urls[] = $url;
			} elseif (!empty($url)) {
				$urls[] = 'turn:'.$url;
			}
		}

		if (!empty($secret) && empty($credential)) {
			$username = (!empty($username)) ? $username : $this->userSession->getUser()->getUID();

			$accessData = TimeLimitedToken::generateTURN($username, $secret, $ttl);

			$username = $accessData[0];
			$credential = $accessData[1];
		}

		if (!empty($urls)) {
			$data = [
		   'ttl' => $ttl,
		   'iceServers' => [
			  [
				 'urls' => $urls,
				 'credential' => $credential,
				 'username' => $username,
			  ],
		   ],
		];
		} else {
			$data = [];
		}

		return $data;
	}

	/**
	* @NoAdminRequired
	*/
	public function getUsers($search = '')
	{
		$limit = 10;
		$offset = 0;

		$preferMail = $this->getBooleanAppValue('xmppPreferMail');

		$users = $this->userManager->searchDisplayName($search, $limit, $offset);
		$response = [];

		foreach ($users as $user) {
			$uid = $user->getUID();
			$index = $uid;

			if ($preferMail) {
				$mail = $this->config->getUserValue($uid, 'settings', 'email');

				if (!empty($mail)) {
					$index = $mail;
				}
			}

			$response[$index] = $user->getDisplayName();
		}

		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function getServerType()
	{
		return ["serverType" => $this->getAppValue('serverType', 'internal')];
	}

	private function getCurrentUser()
	{
		$currentUser = false;

		if (\OCP\User::isLoggedIn()) {
			$currentUser = $this->userSession->getUser();
		} elseif (!empty($this->getParam('username')) && !empty($this->getParam('password'))) {
			$currentUser = $this->userManager->checkPassword($this->getParam('username'), $this->getParam('password'));
		}

		return $currentUser;
	}

	private function generateTimeLimitedToken($node, $domain)
	{
		$secret = $this->getAppValue('apiSecret');

		return TimeLimitedToken::generateUser($node, $domain, $secret);
	}

	private function overwriteByUserDefined($currentUID, $data)
	{
		$options = $this->config->getUserValue($currentUID, 'ojsxc', 'options');

		if ($options !== null) {
			$options = (array) json_decode($options, true);

			if (is_array($options)) {
				foreach ($options as $prop => $value) {
					if ($prop !== 'xmpp' || $data ['xmpp'] ['overwrite']) {
						foreach ($value as $key => $v) {
							if ($v !== '' && $key !== 'url') {
								$data [$prop] [$key] = ($v === 'false' || $v === 'true') ? $this->validateBoolean($v) : $v;
							}
						}
					}
				}
			}
		}

		return $data;
	}

	private function getBooleanAppValue($key)
	{
		return $this->validateBoolean($this->getAppValue($key));
	}

	private function getAppValue($key, $default = null)
	{
		$value = $this->config->getAppValue($this->appName, $key, $default);

		return (empty($value)) ? $default : $value;
	}

	private function setAppValue($key, $value)
	{
		return $this->config->setAppValue($this->appName, $key, $value);
	}

	private function validateBoolean($val)
	{
		return $val === true || $val === 'true';
	}

	private function isPasswordConfirmationRequired()
	{
		$version = \OCP\Util::getVersion();
		$majorVersion = intval($version[0]);

		// copied from owncloud/settings/ajax/installapp.php
		$lastConfirm = (int) \OC::$server->getSession()->get('last-password-confirm');

		return $majorVersion >= 11 && $lastConfirm < (time() - 30 * 60 + 15);
	}

	private function getCheckboxValue($var)
	{
		return (isset($var)) ? $var : 'false';
	}

	private function getParam($key)
	{
		return $this->request->getParam($key);
	}

	private function getCheckboxParam($key)
	{
		return $this->getCheckboxValue($this->request->getParam($key));
	}

	private function getTrimParam($key)
	{
		return trim($this->request->getParam($key));
	}
}
