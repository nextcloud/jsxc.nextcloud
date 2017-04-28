<?php

header('Content-Type: application/json; charset=utf-8');

function validateBoolean($val)
{
    return $val === true || $val === 'true';
}

OCP\JSON::callCheck();

$currentUser = false;

if (OCP\User::isLoggedIn()) {
   $currentUser = \OC::$server->getUserSession()->getUser();
} else if(!empty($_POST['password']) && !empty($_POST['username'])) {
   $currentUser = \OC::$server->getUserManager()->checkPassword($_POST['username'], $_POST['password']);
}

if (!$currentUser) {
    echo json_encode(array(
            'result' => 'noauth',
    ));
    exit();
}

$currentUID = $currentUser->getUID();

$config = \OC::$server->getConfig();

$data = array();
$data ['xmpp'] = array();
$data ['serverType'] = $config->getAppValue('ojsxc', 'serverType', 'external');
$data ['loginForm'] ['startMinimized'] = validateBoolean($config->getAppValue('ojsxc', 'xmppStartMinimized'));

if ($data ['serverType'] === 'internal') {
    echo json_encode(array(
            'result' => 'success',
            'data' => $data,
    ));

    exit;
}

$data ['screenMediaExtension']['firefox'] = trim($config->getAppValue('ojsxc', 'firefoxExtension'));
$data ['screenMediaExtension']['chrome'] = trim($config->getAppValue('ojsxc', 'chromeExtension'));

$data ['xmpp'] ['url'] = trim($config->getAppValue('ojsxc', 'boshUrl'));
$data ['xmpp'] ['domain'] = trim($config->getAppValue('ojsxc', 'xmppDomain'));
$data ['xmpp'] ['resource'] = trim($config->getAppValue('ojsxc', 'xmppResource'));
$data ['xmpp'] ['overwrite'] = validateBoolean($config->getAppValue('ojsxc', 'xmppOverwrite'));
$data ['xmpp'] ['onlogin'] = null;

$data ['adminSettings'] = [
   'xmppDomain' => trim($config->getAppValue('ojsxc', 'xmppDomain'))
];

if (validateBoolean($config->getAppValue('ojsxc', 'xmppPreferMail'))) {
   $mail = $config->getUserValue($currentUID,'settings','email');

   if ($mail !== null) {
      list($u, $d) = explode("@", $mail, 2);
      if ($d !== null && $d !== "") {
          $data ['xmpp'] ['username'] = $u;
          $data ['xmpp'] ['domain'] = $d;
      }
   }
}

if (validateBoolean($config->getAppValue('ojsxc', 'timeLimitedToken'))) {
   $data['xmpp']['username'] = $currentUID;
   $jid = $data['xmpp']['username'] . '@' . $data['xmpp']['domain'];
   $expiry = time() + 60*60;
   $secret = $config->getAppValue('ojsxc', 'apiSecret');

   $version = hex2bin('00');
   $secretID = substr(hash('sha256', $secret, true), 0, 2);
   $header = $secretID.pack('N', $expiry);
   $challenge = $version.$header.$jid;
   $hmac = hash_hmac('sha256', $challenge, $secret, true);
   $token = $version.substr($hmac, 0, 16).$header;

   // format as "user-friendly" base64
   $token = str_replace('=', '', strtr(base64_encode($token),
      'OIl', '-$%'));

   $data['xmpp']['password'] = $token;
}

$options = $config->getUserValue($currentUID, 'ojsxc', 'options');

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

echo json_encode(array(
        'result' => 'success',
        'data' => $data,
));
