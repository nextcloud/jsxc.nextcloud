<?php

header('Content-Type: application/json; charset=utf-8');

function validateBoolean($val)
{
    return $val === true || $val === 'true';
}

OCP\JSON::callCheck();

$username = $_POST ['username'];
$password = $_POST ['password'];

$ocUser = new OCP\User();

$auth = ($password !== null) ? $ocUser->checkPassword($username, $password) : OCP\User::isLoggedIn();

if (!$auth) {
    echo json_encode(array(
            'result' => 'noauth',
    ));
    exit();
}

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

$data ['xmpp'] ['url'] = trim($config->getAppValue('ojsxc', 'boshUrl'));
$data ['xmpp'] ['domain'] = trim($config->getAppValue('ojsxc', 'xmppDomain'));
$data ['xmpp'] ['resource'] = trim($config->getAppValue('ojsxc', 'xmppResource'));
$data ['xmpp'] ['overwrite'] = validateBoolean($config->getAppValue('ojsxc', 'xmppOverwrite'));
$data ['xmpp'] ['onlogin'] = true;

$options = $config->getUserValue($username, 'ojsxc', 'options');

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
