<?php

use OCA\OJSXC\AppInfo\Application;

$app = new Application();

/** @var Symfony\Component\Console\Application $application */
$application->add($app->getContainer()->query('RefreshRosterCommand'));
$application->add($app->getContainer()->query('ServerSharingCommand'));
