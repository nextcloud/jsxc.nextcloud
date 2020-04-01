<?php

namespace OCA\OJSXC;

use OCP\IConfig;

class Config
{
	const XMPP_SERVER_TYPE = 'xmpp_serverType';
	const XMPP_URL = 'xmpp_url';
	const XMPP_DOMAIN = 'xmpp_domain';
	const XMPP_RESOURCE = 'xmpp_resource';
	const XMPP_ALLOW_OVERWRITE = 'xmpp_allowOverwrite';
	const XMPP_START_MINIMIZED = 'xmpp_startMinimized';
	const XMPP_START_ON_LOGIN = 'xmpp_startOnLogin';
	const XMPP_PREFER_MAIL = 'xmpp_preferMail';
	const XMPP_USE_TIME_LIMITED_TOKEN = 'xmpp_useTimeLimitedToken';
	const ICE_URL = 'ice_url';
	const ICE_USERNAME = 'ice_username';
	const ICE_CREDENTIAL = 'ice_credential';
	const ICE_SECRET = 'ice_secret';
	const ICE_TTL = 'ice_ttl';
	const EXTERNAL_SERVICES = 'externalServices';
	const API_SECRET = 'apiSecret';
	const MANAGED_SERVER_STATUS = 'managedServerStatus';

	private $appName;
	private $config;

	public function __construct(
		$appName,
		IConfig $config
	) {
		$this->appName = $appName;
		$this->config = $config;
	}

	public function getUserValue($uid, $app, $key)
	{
		return $this->config->getUserValue($uid, $app, $key);
	}

	public function setUserValue($uid, $app, $key, $value)
	{
		$this->config->setUserValue($uid, $app, $key, $value);
	}

	public function getBooleanAppValue($key, $default = null)
	{
		return $this->validateBoolean($this->getAppValue($key, $default));
	}

	public function getAppValue($key, $default = null)
	{
		$value = $this->config->getAppValue($this->appName, $key, $default);

		return (empty($value)) ? $default : trim($value);
	}

	public function setAppValue($key, $value)
	{
		return $this->config->setAppValue($this->appName, $key, $value);
	}

	public function deleteAppValue($key)
	{
		return $this->config->deleteAppValue($this->appName, $key);
	}

	private function validateBoolean($val)
	{
		return $val === true || $val === 'true' || $val === 1 || $val === '1';
	}
}
