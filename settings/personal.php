<?php

\OC_Util::checkLoggedIn();

OCP\Util::addScript('ojsxc', 'settings/personal');

$config = \OC::$server->getConfig();
$tmpl = new \OCP\Template('ojsxc', 'settings/personal');

$currentUID = \OC::$server->getUserSession()->getUser()->getUID();
$options = $config->getUserValue($currentUID, 'ojsxc', 'options');

if ($options !== null) {
    $options = (array) json_decode($options, true);

    if (is_array($options)) {
      $loginFormEnable = true;
      if (is_array($options['loginForm']) && isset($options['loginForm']['enable'])) {
         $loginFormEnable = $options['loginForm']['enable'];
      }
      $tmpl->assign('loginFormEnable', $loginFormEnable);
    }
}

return $tmpl->fetchPage();
