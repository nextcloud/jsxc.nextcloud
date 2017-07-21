<?php

namespace OCA\OJSXC\Controller;

use OCP\AppFramework\Controller;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserManager;

class SettingsController extends Controller
{
    private $config;

    private $userManager;

    private $userSession;

    public function __construct($appName,
   IRequest $request,
   IConfig $config,
   IUserManager $userManager)
    {
        parent::__construct($appName, $request);

        $this->config = $config;
        $this->userManager = $userManager;

        $this->userSession = \OC::$server->getUserSession();
    }

   /**
   * @NoAdminRequired
   * @PublicPage
   */
   public function index()
   {
       $currentUser = $this->getCurrentUser();

       if (!$currentUser) {
           return array(
            'result' => 'noauth',
         );
       }

       $currentUID = $currentUser->getUID();

       $serverType = $this->getAppValue('serverType');

       $data = array(
         'serverType' => (!empty($serverType))? $serverType : 'internal',
         'loginForm' => array(
            'startMinimized' => $this->getBooleanAppValue('xmppStartMinimized')
            )
         );

       if ($data ['serverType'] === 'internal') {
           $data['adminSettings']['xmppDomain'] = $this->request->getServerHost();

           return array(
               'result' => 'success',
               'data' => $data,
            );
       }

       $data ['screenMediaExtension'] = array(
            'firefox' => trim($this->getAppValue('firefoxExtension')),
            'chrome' => trim($this->getAppValue('chromeExtension'))
         );

       $data ['xmpp'] = array(
            'url' => trim($this->getAppValue('boshUrl')),
            'domain' => trim($this->getAppValue('xmppDomain')),
            'resource' => trim($this->getAppValue('xmppResource')),
            'overwrite' => $this->getBooleanAppValue('xmppOverwrite'),
            'onlogin' => null
         );

       $data ['adminSettings'] = array(
            'xmppDomain' => trim($this->getAppValue('xmppDomain'))
         );

       if ($this->getBooleanAppValue('xmppPreferMail')) {
           $mail = $this->config->getUserValue($currentUID, 'settings', 'email');

           if ($mail !== null) {
               list($u, $d) = explode("@", $mail, 2);
               if ($d !== null && $d !== "") {
                   $data ['xmpp'] ['username'] = $u;
                   $data ['xmpp'] ['domain'] = $d;
               }
           }
       }

       if ($this->getBooleanAppValue('timeLimitedToken')) {
           if (!is_string($data['xmpp']['username'])) {
               $data['xmpp']['username'] = $currentUID;
           }

           $this->generateTimeLimitedToken($data['xmpp']['username'], $data['xmpp']['domain']);

           $data['xmpp']['password'] = $token;
       }

       $data = $this->overwriteByUserDefined($currentUID, $data);

       return array(
            'result' => 'success',
            'data' => $data,
         );
   }

   public function setAdmin() {
      if ($this->isPasswordConfirmationRequired()) {
         $l = \OC::$server->getL10N('core');

         return array(
            'status' => 'error',
            'data' => array(
               'message' => $l->t('Password confirmation is required')
            )
         );
      }

      $this->setAppValue('serverType', $_POST ['serverType']);
      $this->setAppValue('boshUrl', trim($_POST ['boshUrl']));
      $this->setAppValue('xmppDomain', trim($_POST ['xmppDomain']));
      $this->setAppValue('xmppResource', trim($_POST ['xmppResource']));
      $this->setAppValue('xmppOverwrite', $this->getCheckboxValue($_POST ['xmppOverwrite']));
      $this->setAppValue('xmppStartMinimized', $this->getCheckboxValue($_POST ['xmppStartMinimized']));
      $this->setAppValue('xmppPreferMail', $this->getCheckboxValue($_POST ['xmppPreferMail']));

      $this->setAppValue('iceUrl', trim($_POST ['iceUrl']));
      $this->setAppValue('iceUsername', trim($_POST ['iceUsername']));
      $this->setAppValue('iceCredential', $_POST ['iceCredential']);
      $this->setAppValue('iceSecret', $_POST ['iceSecret']);
      $this->setAppValue('iceTtl', $_POST ['iceTtl']);

      $this->setAppValue('timeLimitedToken', $this->getCheckboxValue($_POST ['timeLimitedToken']));

      $this->setAppValue('firefoxExtension', $_POST ['firefoxExtension']);
      $this->setAppValue('chromeExtension', $_POST ['chromeExtension']);

      $externalServices = array();
      foreach($_POST['externalServices'] as $es) {
         if (preg_match('/^(https:\/\/)?([\w\d*][\w\d-]*)(\.[\w\d-]+)+(:[\d]+)?$/', $es)) {
            $externalServices[] = $es;
         }
      }
      $this->setAppValue('externalServices', implode('|', $externalServices));

      return array(
         'status' => 'success'
      );
   }

   /**
   * @NoAdminRequired
   */
   public function setUser() {
      $uid = $this->userSession->getUser()->getUID();

      $options = $this->config->getUserValue($uid, 'ojsxc', 'options');
      $options = json_decode($options, true);

      foreach($_POST as $key => $val) {
         $options[$key] = $val;
      }

      $this->config->setUserValue($uid, 'ojsxc', 'options', json_encode($options));

      return array(
         'status' => 'success'
      );
   }

   /**
   * @NoAdminRequired
   */
   public function getIceServers() {
      $secret = $this->getAppValue('iceSecret');
      $uid = $this->userSession->getUser()->getUID();

      $ttl = $this->getAppValue('iceTtl',  3600 * 24); // one day (according to TURN-REST-API)
      $url = $this->getAppValue('iceUrl');
      $url = preg_match('/^(turn|stun):/', $url) || empty($url) ? $url : "turn:$url";

      $usernameTRA = $secret ? (time() + $ttl).':'.$uid : $uid;
      $username = $this->getAppValue('iceUsername', '');
      $username = (!empty($username)) ? $username : $usernameTRA;

      $credentialTRA = ($secret) ? base64_encode(hash_hmac('sha1', $username, $secret, true)) : '';
      $credential = $this->getAppValue('iceCredential', '');
      $credential = (!empty($credential)) ? $credential : $credentialTRA;

      if (!empty($url)) {
        $data = array(
           'ttl' => $ttl,
           'iceServers' => array(
              array(
                 'urls' => array($url),
                 'credential' => $credential,
                 'username' => $username,
              ),
           ),
        );
      } else {
        $data = array();
      }

      return $data;
   }

    private function getCurrentUser()
    {
        $currentUser = false;

        if (\OCP\User::isLoggedIn()) {
            $currentUser = $this->userSession->getUser();
        } elseif (!empty($_POST['password']) && !empty($_POST['username'])) {
            $currentUser = $this->userManager->checkPassword($_POST['username'], $_POST['password']);
        }

        return $currentUser;
    }

    private function generateTimeLimitedToken($node, $domain)
    {
        $jid =  $node. '@' . $domain;
        $expiry = time() + 60*60;
        $secret = $this->getAppValue('apiSecret');

        $version = hex2bin('00');
        $secretID = substr(hash('sha256', $secret, true), 0, 2);
        $header = $secretID.pack('N', $expiry);
        $challenge = $version.$header.$jid;
        $hmac = hash_hmac('sha256', $challenge, $secret, true);
        $token = $version.substr($hmac, 0, 16).$header;

         // format as "user-friendly" base64
         $token = str_replace('=', '', strtr(base64_encode($token),
         'OIl', '-$%'));

        return $token;
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
                            if ($v !== '') {
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

    private function setAppValue($key, $value) {
        return $this->config->setAppValue($this->appName, $key, $value);
    }

    private function validateBoolean($val)
    {
        return $val === true || $val === 'true';
    }

    private function isPasswordConfirmationRequired() {
      $version = \OCP\Util::getVersion();
      preg_match('/^([0-9]+)\.', $version, $versionMatches);
      $majorVersion = intval($versionMatches[1]);

      // copied from owncloud/settings/ajax/installapp.php
      $lastConfirm = (int) \OC::$server->getSession()->get('last-password-confirm');

      return $majorVersion >= 11 && $lastConfirm < (time() - 30 * 60 + 15);
   }

   private function getCheckboxValue($var) {
      return (isset($var)) ? $var : 'false';
   }
}
