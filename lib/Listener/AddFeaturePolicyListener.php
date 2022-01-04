<?php

namespace OCA\OJSXC\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\FeaturePolicy\AddFeaturePolicyEvent;

class AddFeaturePolicyListener implements IEventListener
{
	public function handle(Event $event): void
	{
		if (!($event instanceof AddFeaturePolicyEvent)) {
			return;
		}

		$policy = new \OCP\AppFramework\Http\EmptyFeaturePolicy();

		$policy->addAllowedGeoLocationDomain('\'self\'');
		$policy->addAllowedCameraDomain('\'self\'');
		$policy->addAllowedFullScreenDomain('\'self\'');
		$policy->addAllowedMicrophoneDomain('\'self\'');

		$event->addPolicy($policy);
	}
}
