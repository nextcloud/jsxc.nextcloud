<?php

namespace OCA\OJSXC\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\IConfig;

class Personal implements ISettings
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
		$parameters = [];

		$currentUID = \OC::$server->getUserSession()->getUser()->getUID();
		$options = $this->config->getUserValue($currentUID, 'ojsxc', 'options');

		if ($options !== null) {
			$options = (array) json_decode($options, true);

			if (is_array($options)) {
				$loginFormEnable = null;
				if (is_array($options['loginForm']) && isset($options['loginForm']['enable'])) {
					$loginFormEnable = $options['loginForm']['enable'];
				}

				if ($loginFormEnable === true || $loginFormEnable === 'true') {
					$parameters['loginForm'] = 'enable';
				} elseif ($loginFormEnable === false || $loginFormEnable === 'false') {
					$parameters['loginForm'] = 'disable';
				} else {
					$parameters['loginForm'] = 'default';
				}
			}
		}

		return new TemplateResponse('ojsxc', 'settings/personal', $parameters);
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
}
