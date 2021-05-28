<?php

use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Command\RefreshRoster;
use OCA\OJSXC\Command\ServerSharing;

$app = new Application();

/** @var Symfony\Component\Console\Application $application */
$application->add($app->getContainer()->query(RefreshRoster::class));
$application->add($app->getContainer()->query(ServerSharing::class));
