<?php

namespace OCA\OJSXC\Controller;

use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCA\OJSXC\Exceptions\UnprocessableException;

class ExternalApiController extends SignatureProtectedApiController
{
	private $userManager;

	private $userSession;

	private $groupManager;

	private $logger;

	public function __construct(
		$appName,
   IRequest $request,
   IUserManager $userManager,
   IUserSession $userSession,
   IGroupManager $groupManager,
   ILogger $logger
	) {
		parent::__construct($appName, $request);

		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->logger = $logger;
	}

	public function index($operation)
	{
		switch ($operation) {
		 case 'auth':
			return checkPassword(
			   $this->request->getParam('username'),
			   $this->request->getParam('password'),
			   $this->request->getParam('domain')
			);
			break;
		 case 'isuser':
			return isUser(
			   $this->request->getParam('username'),
			   $this->request->getParam('domain')
			);
			break;
		 case 'sharedroster':
			return sharedRoster(
			   $this->request->getParam('username'),
			   $this->request->getParam('domain')
			);
			break;
		 default:
			throw new UnprocessableException('Unsupported operation.');
	  }
	}

	public function checkPassword($username = '', $password = '', $domain = '')
	{
		$currentUser = null;

		$this->logger->info('ExAPI: Check password for user: '.$username.'@'.$domain);

		if (!empty($password) && !empty($username)) {
			$loggedIn = false;
			if (!empty($domain)) {
				$loggedIn = $this->userSession->login($username . '@' . $domain, $password);
			}
			if (!$loggedIn) {
				$loggedIn = $this->userSession->login($username, $password);
			}

			if ($loggedIn === true) {
				$currentUser = $this->userSession->getUser();
			}
		}

		if (!$currentUser) {
			return [
			 'result' => 'noauth',
		  ];
		}

		$data = [];
		$data ['uid'] = $currentUser->getUID();

		return [
		 'result' => 'success',
		 'data' => $data,
	  ];
	}

	public function isUser($username = '', $domain = '')
	{
		$this->logger->info('ExAPI: Check if "'.$username.'@'.$domain.'" exists');

		$isUser = false;

		if (!empty($username)) {
			if (!empty($domain)) {
				$isUser = $this->userManager->userExists($username . '@' . $domain);
			}
			if (!$isUser) {
				$isUser = $this->userManager->userExists($username);
			}
		}

		return [
		 'result' => 'success',
		 'data' => [
			'isUser' => $isUser
		 ]
	  ];
	}

	public function sharedRoster($username = '', $domain = '')
	{
		if (!empty($username)) {
			if (!empty($domain)) {
				$username .= '@' . $domain;
			}
		} else {
			throw new UnprocessableException('No username provided');
		}

		$roster = [];
		$user = $this->userManager->get($username);

		$userGroups = $this->groupManager->getUserGroups($user);

		foreach ($userGroups as $userGroup) {
			foreach ($userGroup->getUsers() as $user) {
				$uid = $user->getUID();

				if (!array_key_exists($uid, $roster)) {
					$roster[$uid] = [
				  'name' => $user->getDisplayName(),
				  'groups' => []
			   ];
				}

				$roster[$uid]['groups'][] = $userGroup->getDisplayName();
			}
		}

		return [
		 'result' => 'success',
		 'data' => [
			'sharedRoster' => $roster
		 ]
	  ];
	}
}
