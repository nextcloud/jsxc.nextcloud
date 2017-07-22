<?php

namespace OCA\OJSXC\Controller;

use OCP\AppFramework\ApiController;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;

class ExternalApiController extends SignatureProtectedApiController
{
    private $userManager;

    private $groupManager;

    private $userSession;

    private $logger;

    public function __construct($appName,
   IRequest $request,
   IUserManager $userManager,
   IGroupManager $groupManager,
   ILogger $logger)
    {
        parent::__construct($appName, $request);

        $this->userManager = $userManager;
        $this->groupManager = $groupManager;

        $this->userSession = \OC::$server->getUserSession();

        $this->logger = $logger;
    }

   public function index($operation)
   {
      switch($operation) {
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
            throw new \Exception( 'Unsupported operation.');
      }
   }

   public function checkPassword($username = '', $password = '', $domain = '') {
      $currentUser = null;

      $this->logger->info('ExAPI: Check password for user: '.$username.'@'.$domain);

      if(!empty($password) && !empty($username)) {
         if(!empty($domain)) {
            $currentUser = $this->userManager->checkPassword($username . '@' . $domain, $password);
         }
         if($currentUser === null) {
            $currentUser = $this->userManager->checkPassword($username, $password);
         }
      }

      if (!$currentUser) {
          return array(
             'result' => 'noauth',
          );
      }

      $data = array();
      $data ['uid'] = $currentUser->getUID();

      return array(
         'result' => 'success',
         'data' => $data,
      );
   }

   public function isUser($username = '', $domain = '') {
      $this->logger->info('ExAPI: Check if "'.$username.'@'.$domain.'" exists');

      $isUser = false;

      if(!empty($username)) {
         if(!empty($domain)) {
            $isUser = $this->userManager->userExists($username . '@' . $domain);
         }
         if(!$isUser) {
            $isUser = $this->userManager->userExists($username);
         }
      }

      return array(
         'result' => 'success',
         'data' => array(
            'isUser' => $isUser
         )
      );
   }

   public function sharedRoster($username = '', $domain = '') {
      if(!empty($username)) {
         if(!empty($domain)) {
            $username .= '@' . $domain;
         }
      } else {
         throw new \Exception('No username provided');
      }

      $roster = [];

      $userGroups = $this->groupManager->getUserIdGroups($username);

      foreach($userGroups as $userGroup) {
         foreach($userGroup->getUsers() as $user) {
            $uid = $user->getUID();

            if(!$roster[$uid]) {
               $roster[$uid] = [
                  'name' => $user->getDisplayName(),
                  'groups' => []
               ];
            }

            $roster[$uid]['groups'][] = $userGroup->getDisplayName();
         }
      }

      return array(
         'result' => 'success',
         'data' => array(
            'sharedRoster' => $roster
         )
      );
   }
}
