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
                                $data [$prop] [$key] = ($v === 'false' || $v === 'true') ? validateBoolean($v) : $v;
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

    private function getAppValue($key)
    {
        return $this->config->getAppValue($this->appName, $key);
    }

    private function validateBoolean($val)
    {
        return $val === true || $val === 'true';
    }
}
