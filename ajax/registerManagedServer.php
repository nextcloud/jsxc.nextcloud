<?php
header('Content-Type: application/json; charset=utf-8');

OCP\User::checkAdminUser();
OCP\JSON::callCheck();

const REGISTRATION_URL = 'http://localhost/register.php';

function abort($msg)
{
    http_response_code(500);

    \OCP\Util::writeLog('ojsxc', 'RMS: Abort with message: '.$msg, \OCP\Util::WARN);

    die(json_encode(array(
      'result' => 'error',
      'data' => array(
         'msg' => $msg
      ),
   )));
}

function parseHeaders($headers)
{
    $head = array();
    foreach ($headers as $k=>$v) {
        $t = explode(':', $v, 2);
        if (isset($t[1])) {
            $head[ trim($t[0]) ] = trim($t[1]);
        } else {
            $head[] = $v;
            if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out)) {
                $head['reponse_code'] = intval($out[1]);
            }
        }
    }
    return $head;
}

$promotionCode = $_POST['promotionCode'];
$promotionCode = (preg_match('/^[0-9a-z]+$/i', $promotionCode)) ? $promotionCode : null;

$apiUrl = \OC::$server->getURLGenerator()->linkTo('ojsxc', 'ajax/externalApi.php');
$apiUrl = \OC::$server->getURLGenerator()->getAbsoluteURL($apiUrl);

$config = \OC::$server->getConfig();
$apiSecret = $config->getAppValue('ojsxc', 'apiSecret');

$userId = \OC::$server->getUserSession()->getUser()->getUID();

$context  = stream_context_create(array('http' =>
    array(
        'method'  => 'POST',
        'ignore_errors' => '1',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => http_build_query(
            array(
                'apiUrl' => $apiUrl,
                'apiSecret' => $apiSecret,
                'userId' => $userId,
                'promotionCode' => $promotionCode
            )
        )
    )
));

$result = file_get_contents(REGISTRATION_URL, false, $context);

if ($result === false) {
    abort('Couldn\'t reach the registration server');
}

$headers = parseHeaders($http_response_header);

$responseJSON = json_decode($result);

if ($responseJSON === null) {
    abort('Couldn\'t parse the response. Response code: '.$headers['reponse_code']);
}

if ($headers['reponse_code'] !== 200) {
    \OCP\Util::writeLog('ojsxc', 'RMS: Response code: '.$headers['reponse_code'], \OCP\Util::INFO);

    abort($responseJSON->message);
}

$config->setAppValue('ojsxc', 'serverType', 'managed');
$config->setAppValue('ojsxc', 'boshUrl', $responseJSON->boshUrl);
$config->setAppValue('ojsxc', 'xmppDomain', $responseJSON->domain);
$config->setAppValue('ojsxc', 'timeLimitedToken', 'true');
$config->setAppValue('ojsxc', 'managedServer', 'registered');

//@TODO add bosh url to content-security-policy

echo json_encode(array(
   'result' => 'success',
   'data' => array()
));
?>
