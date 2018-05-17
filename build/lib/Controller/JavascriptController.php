<?php

namespace OCA\OJSXC\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\IConfig;
use OCP\IRequest;

class JavascriptController extends Controller
{
	private $config;

	public function __construct($appName, IRequest $request, IConfig $config)
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
		$loginFormEnable = $this->config->getAppValue('ojsxc', 'loginFormEnable', true);

		$settings = [
			'defaultLoginFormEnable' => $loginFormEnable === 'true' || $loginFormEnable === true,
		];

		$code = 'var OJSXC_CONFIG = {}; try{OJSXC_CONFIG = JSON.parse(\''.json_encode($settings).'\');}catch(err){}';

		return new DataDownloadResponse($code, 'script', 'text/javascript');
	}
}
