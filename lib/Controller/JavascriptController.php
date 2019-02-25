<?php

namespace OCA\OJSXC\Controller;

use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Config;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\IRequest;

class JavascriptController extends Controller
{
	private $config;

	public function __construct($appName, IRequest $request, Config $config)
	{
		parent::__construct($appName, $request);

		$this->config = $config;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function generalConfig()
	{
		$serverType = $this->config->getAppValue(Config::XMPP_SERVER_TYPE, Application::INTERNAL);
		$startMinimized = $this->config->getBooleanAppValue(Config::XMPP_START_MINIMIZED);
		$loginFormEnable = $this->config->getBooleanAppValue(Config::XMPP_START_ON_LOGIN, true);

		$settings = [
			'serverType' => $serverType,
			'startMinimized' => $startMinimized,
			'defaultLoginFormEnable' => $loginFormEnable,
		];

		$code = 'var OJSXC_CONFIG = {}; try{OJSXC_CONFIG = JSON.parse(\''.json_encode($settings).'\');}catch(err){}';

		return new DataDownloadResponse($code, 'script', 'text/javascript');
	}
}
