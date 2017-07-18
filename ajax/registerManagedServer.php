<?php
header('Content-Type: application/json; charset=utf-8');

OCP\User::checkAdminUser();
OCP\JSON::callCheck();

const REGISTRATION_URL = 'http://localhost/register.php';

function abort($msg) {
   http_response_code(500);

   \OCP\Util::writeLog('ojsxc', 'RMS: Abort with message: '.$msg, \OCP\Util::WARN );

   die(json_encode(array(
      'result' => 'error',
      'data' => array(
         'msg' => $msg
      ),
   )));
}

$apiUrl = \OC::$server->getURLGenerator()->linkTo('ojsxc', 'ajax/externalApi.php');
$apiUrl = \OC::$server->getURLGenerator()->getAbsoluteURL($apiUrl);

$config = \OC::$server->getConfig();
$apiSecret = $config->getAppValue('ojsxc', 'apiSecret');

$userId = \OC::$server->getUserSession()->getUser()->getUID();

$context  = stream_context_create(array('http' =>
    array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => http_build_query(
            array(
                'apiUrl' => $apiUrl,
                'apiSecret' => $apiSecret,
                'userId' => $userId
            )
        )
    )
));

$result = file_get_contents(REGISTRATION_URL, false, $context);

if ($result === false) {
   abort('Couldn\'t reach the registration server');
}

$responseJSON = json_decode($result);

if ($responseJSON === null) {
   abort('Couldn\'t parse the response');
}

if ($responseJSON->result !== 'success') {
   abort($responseJSON->data->msg);
}

$config->setAppValue('ojsxc', 'serverType', 'managed');
$config->setAppValue('ojsxc', 'boshUrl', $responseJSON->data->boshUrl);
$config->setAppValue('ojsxc', 'xmppDomain', $responseJSON->data->domain);
$config->setAppValue('ojsxc', 'timeLimitedToken', 'true');
$config->setAppValue('ojsxc', 'managedServer', 'registered');

//@TODO add bosh url to content-security-policy

echo json_encode(array(
   'result' => 'success',
   'data' => array()
));
?>
