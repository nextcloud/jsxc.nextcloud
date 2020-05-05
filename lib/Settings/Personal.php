<?php

namespace OCA\OJSXC\Settings;

use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Config;
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

		$domain = $this->config->getAppValue('ojsxc', Config::XMPP_DOMAIN);
		$node = $currentUID;

		if ($options !== null) {
			$options = (array) json_decode($options, true);

			if (is_array($options)) {
				$loginFormEnable = null;
				if (isset($options['disabled'])) {
					$loginFormEnable = $options['disabled'];
				}

				if ($loginFormEnable === true || $loginFormEnable === 'true') {
					$parameters['loginForm'] = 'disable';
				} elseif ($loginFormEnable === false || $loginFormEnable === 'false') {
					$parameters['loginForm'] = 'enable';
				} else {
					$parameters['loginForm'] = 'default';
				}

				if (array_key_exists('xmpp', $options) && is_array($options['xmpp'])) {
					if (!empty($options['xmpp']['node'])) {
						$node = $options['xmpp']['node'];
						$parameters['xmppNode'] = $options['xmpp']['node'];
					}

					if (!empty($options['xmpp']['domain'])) {
						$domain = $options['xmpp']['domain'];
						$parameters['xmppDomain'] = $options['xmpp']['domain'];
					}

					if (!empty($options['xmpp']['resource'])) {
						$parameters['xmppResource'] = $options['xmpp']['resource'];
					}
				}
			}
		}

		$xmppPreferMail = $this->config->getAppValue('ojsxc', Config::XMPP_PREFER_MAIL);
		if ($xmppPreferMail === true || $xmppPreferMail === 'true' || $xmppPreferMail === 1 || $xmppPreferMail === '1') {
			$mail = $this->config->getUserValue($currentUID, 'settings', 'email');
			if ($mail !== null) {
				list($u, $d) = explode("@", $mail, 2);
				if ($d !== null && $d !== "") {
					$node = strtolower($u);
					$domain = strtolower($d);
				}
			}
		}

		$xmppOverwrite = $this->config->getAppValue('ojsxc', Config::XMPP_ALLOW_OVERWRITE);

		$parameters['xmppUrl'] = $this->config->getAppValue('ojsxc', Config::XMPP_URL);
		$parameters['externalConnectable'] = Application::getServerType() !== Application::INTERNAL;
		$parameters['allowToOverwriteXMPPConfig'] = $xmppOverwrite === 'true' || $xmppOverwrite === true || $xmppOverwrite === 1 || $xmppOverwrite === '1';
		$parameters['jid'] = $node . '@' . $domain;

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
