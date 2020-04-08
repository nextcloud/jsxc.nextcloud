<?php

namespace OCA\OJSXC\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCA\OJSXC\Config;
use OCA\OJSXC\TimeLimitedToken;
use OCA\OJSXC\AppInfo\Application;

const SUCCESS = 'success';

class SettingsController extends Controller
{
	private $config;

	private $userManager;

	private $userSession;

	public function __construct(
		$appName,
		IRequest $request,
		Config $config,
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
		$config = $this->config;

		$serverType = $config->getAppValue(Config::XMPP_SERVER_TYPE, Application::INTERNAL);

		$data = [
			'disabled' => !$config->getAppValue(Config::XMPP_START_ON_LOGIN, true),
		];

		if ($serverType === Application::INTERNAL) {
			$serverHost = $this->request->getServerHost();

			$data['xmpp'] = [
				'defaultDomain' => $serverHost,
				'url' => \OC::$server->getURLGenerator()->linkToRouteAbsolute('ojsxc.http_bind.index'),
				'node' => $currentUID,
				'domain' => $serverHost,
				'resource' => 'internal'
			];

			return [
			   'result' => 'success',
			   'data' => $data,
			];
		}

		$data ['xmpp'] = [
			'url' => $config->getAppValue(Config::XMPP_URL),
			'domain' => $config->getAppValue(Config::XMPP_DOMAIN),
			'resource' => $config->getAppValue(Config::XMPP_RESOURCE),
			'defaultDomain' => $config->getAppValue(Config::XMPP_DOMAIN),
		 ];

		if ($config->getBooleanAppValue(Config::XMPP_PREFER_MAIL) && !$config->getBooleanAppValue(Config::XMPP_USE_TIME_LIMITED_TOKEN)) {
			$mail = $config->getUserValue($currentUID, 'settings', 'email');

			if ($mail !== null) {
				list($u, $d) = explode("@", $mail, 2);
				if ($d !== null && $d !== "") {
					$data ['xmpp'] ['node'] = strtolower($u);
					$data ['xmpp'] ['domain'] = strtolower($d);
				}
			}
		}

		if ($config->getBooleanAppValue(Config::XMPP_USE_TIME_LIMITED_TOKEN)) {
			$data['xmpp']['node'] = strtolower($currentUID);

			$token = $this->generateTimeLimitedToken($data['xmpp']['node'], $data['xmpp']['domain']);

			$data['xmpp']['password'] = $token;
		}

		$data = $this->overwriteByUserDefined($currentUID, $data);

		return [
			'result' => SUCCESS,
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

		$textParameters = [
			Config::XMPP_SERVER_TYPE,
			Config::XMPP_URL,
			Config::XMPP_DOMAIN,
			Config::XMPP_RESOURCE,
			Config::ICE_URL,
			Config::ICE_USERNAME,
			Config::ICE_CREDENTIAL,
			Config::ICE_SECRET,
			Config::ICE_TTL,
		];

		$checkboxParameters = [
			Config::XMPP_ALLOW_OVERWRITE,
			Config::XMPP_ALLOW_OVERWRITE,
			Config::XMPP_START_MINIMIZED,
			Config::XMPP_START_ON_LOGIN,
			Config::XMPP_PREFER_MAIL,
			Config::XMPP_USE_TIME_LIMITED_TOKEN,
		];

		$params = [];

		foreach ($textParameters as $param) {
			$params[$param] = $this->getTrimParam($param);
			$this->config->setAppValue($param, $this->getTrimParam($param));
		}

		foreach ($checkboxParameters as $param) {
			$params[$param] = $this->getCheckboxParam($param);
			$this->config->setAppValue($param, $this->getCheckboxParam($param));
		}

		$externalServices = [];
		foreach ($this->getParam(Config::EXTERNAL_SERVICES) as $es) {
			if (preg_match('/^(https:\/\/)?([\w\d*][\w\d-]*)(\.[\w\d-]+)+(:[\d]+)?$/', $es)) {
				$externalServices[] = $es;
			}
		}
		$this->config->setAppValue(Config::EXTERNAL_SERVICES, implode('|', $externalServices));

		return [
		 'status' => SUCCESS,
		 'params' => $params
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
			if ($val === 'true') {
				$val = true;
			} elseif ($val === 'false') {
				$val = false;
			}

			$options[$key] = $val;
		}

		$this->config->setUserValue($uid, 'ojsxc', 'options', json_encode($options));

		return [
		 'status' => SUCCESS
	  ];
	}

	/**
	* @NoAdminRequired
	*/
	public function getIceServers()
	{
		$secret = $this->config->getAppValue(Config::ICE_SECRET);
		$ttl = $this->config->getAppValue(Config::ICE_TTL, 3600 * 24); // one day (according to TURN-REST-API)
		$urlString = $this->config->getAppValue(Config::ICE_URL);
		$username = $this->config->getAppValue(Config::ICE_USERNAME, '');
		$credential = $this->config->getAppValue(Config::ICE_CREDENTIAL, '');

		$urls = [];
		foreach (preg_split('/[\s,]+/', $urlString) as $url) {
			if (preg_match('/^(turn|stun)s?:/', $url)) {
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
			$data = [
				'ttl' => $ttl,
				'iceServers' => [
					[
						'urls' => ['stun:stun.stunprotocol.org']
					]
				]
			];
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

		$preferMail = $this->config->getBooleanAppValue(Config::XMPP_PREFER_MAIL);

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
		return ["serverType" => $this->config->getAppValue(Config::XMPP_SERVER_TYPE, Application::INTERNAL)];
	}

	private function getCurrentUser()
	{
		$currentUser = false;

		if ($this->userSession->isLoggedIn()) {
			$currentUser = $this->userSession->getUser();
		} elseif (!empty($this->getParam('username')) && !empty($this->getParam('password'))) {
			$currentUser = $this->userManager->checkPassword($this->getParam('username'), $this->getParam('password'));
		}

		return $currentUser;
	}

	private function generateTimeLimitedToken($node, $domain)
	{
		$secret = $this->config->getAppValue(Config::API_SECRET);

		return TimeLimitedToken::generateUser($node, $domain, $secret);
	}

	private function overwriteByUserDefined($currentUID, $data)
	{
		$options = $this->config->getUserValue($currentUID, 'ojsxc', 'options');

		if ($options === null) {
			return $data;
		}

		$options = (array) json_decode($options, true);

		//@TODO only for debugging
		$data['user'] = $options;

		if (!is_array($options)) {
			return $data;
		}

		$allowToOverwriteXMPPOptions = $this->config->getAppValue(Config::XMPP_ALLOW_OVERWRITE, false);

		foreach ($options as $prop => $value) {
			if ($prop !== 'xmpp' || $allowToOverwriteXMPPOptions) {
				foreach ($value as $key => $v) {
					if (!empty($v) && $key !== 'url') {
						$data [$prop] [$key] = ($v === 'false' || $v === 'true') ? $this->validateBoolean($v) : $v;
					}
				}
			}
		}

		return $data;
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
		return (isset($var) && ($var === 'true' || $var === true || $var === '1' || $var === 1)) ? 1 : 0;
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
