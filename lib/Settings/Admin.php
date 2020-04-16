<?php

namespace OCA\OJSXC\Settings;

use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Config;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\IConfig;

class Admin implements ISettings
{
	/** @var IConfig */
	private $config;

	public function __construct(IConfig $config)
	{
		$this->config = $config;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm()
	{
		$externalServices = $this->config->getAppValue('ojsxc', CONFIG::EXTERNAL_SERVICES);
		$externalServices = explode("|", $externalServices);

		$serverType = Application::getServerType();

		$apiUrl = \OC::$server->getURLGenerator()->linkToRouteAbsolute('ojsxc.externalApi.index');

		$parameters = [
		   'serverType' => [
			   'name' => CONFIG::XMPP_SERVER_TYPE,
			   'value' => $serverType
		   ],
		   'boshUrl' => $this->getParam(CONFIG::XMPP_URL),
		   'xmppDomain' => $this->getParam(CONFIG::XMPP_DOMAIN),
		   'xmppPreferMail' => $this->getParam(CONFIG::XMPP_PREFER_MAIL),
		   'xmppResource' => $this->getParam(CONFIG::XMPP_RESOURCE),
		   'xmppOverwrite' => $this->getParam(CONFIG::XMPP_ALLOW_OVERWRITE),
		   'xmppStartMinimized' => $this->getParam(CONFIG::XMPP_START_MINIMIZED),
		   'loginFormEnable' => $this->getParam(CONFIG::XMPP_START_ON_LOGIN, true),
		   'iceUrl' => $this->getParam(CONFIG::ICE_URL),
		   'iceUsername' => $this->getParam(CONFIG::ICE_USERNAME),
		   'iceCredential' => $this->getParam(CONFIG::ICE_CREDENTIAL),
		   'iceSecret' => $this->getParam(CONFIG::ICE_SECRET),
		   'iceTtl' => $this->getParam(CONFIG::ICE_TTL),
		   'timeLimitedToken' => $this->getParam(CONFIG::XMPP_USE_TIME_LIMITED_TOKEN),
		   'externalServices' => [
			   'name' => CONFIG::EXTERNAL_SERVICES . '[]',
			   'value' => $externalServices
		   ],
		   'apiUrl' => $apiUrl,
		   'apiSecret' => $this->getParam(CONFIG::API_SECRET),
		   'userId' => \OC::$server->getUserSession()->getUser()->getUID(),
		   'managedServer' => $this->getParam(CONFIG::MANAGED_SERVER_STATUS)
		];

		return new TemplateResponse('ojsxc', 'settings/admin', $parameters);
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection()
	{
		return 'ojsxc';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority()
	{
		return 50;
	}

	private function getParam($key, $default = null)
	{
		return [
			'name' => $key,
			'value' => $this->config->getAppValue('ojsxc', $key, $default)
		];
	}
}
