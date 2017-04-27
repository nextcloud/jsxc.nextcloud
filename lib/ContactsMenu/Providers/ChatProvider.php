<?php

namespace OCA\OJSXC\ContactsMenu\Providers;

use OCP\Contacts\ContactsMenu\IActionFactory;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\Contacts\ContactsMenu\IProvider;
use OCP\IL10N;
use OCP\IURLGenerator;

class ChatProvider implements IProvider
{

    /** @var IActionFactory */
    private $actionFactory;

    /** @var IURLGenerator */
    private $urlGenerator;

    /** @var IL10N */
    private $l10n;

    /**
     * @param IActionFactory $actionFactory
     * @param IURLGenerator $urlGenerator
     * @param IL10N $l10n
     */
    public function __construct(IActionFactory $actionFactory, IURLGenerator $urlGenerator, IL10N $l10n)
    {
        $this->actionFactory = $actionFactory;
        $this->urlGenerator = $urlGenerator;
        $this->l10n = $l10n;
    }

    /**
     * @param IEntry $entry
     */
    public function process(IEntry $entry)
    {
        $uid = $entry->getProperty('UID');
        $iconUrl = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('ojsxc', 'actions/chat.svg'));
        $localIm = null;

        if (is_null($uid)) {
            // Nothing to do
            return;
        }

        if ($entry->getProperty('isLocalSystemBook') === true) {
            // internal user

            $config = \OC::$server->getConfig();
            $serverType =  $config->getAppValue('ojsxc', 'serverType', 'external');

            if ($serverType === 'internal') {
                $domain = \OC::$server->getRequest()->getServerHost();
            } else {
                $domain = trim($config->getAppValue('ojsxc', 'xmppDomain'));
            }

            $localIm = $uid.'@'.$domain;
            $chatUrl = 'xmpp:'.$localIm;

            $action = $this->actionFactory->newLinkAction($iconUrl, $localIm, $chatUrl);
            $entry->addAction($action);
        }

        $imProperties = $entry->getProperty('IMPP');

        foreach ($imProperties as $externalIm) {
            if (!preg_match("/^[a-z0-9\.\-_]+@[a-z0-9\.\-_]+$/i", $externalIm) || $externalIm === $localIm) {
                continue;
            }

            $chatUrl = 'xmpp:'.$externalIm;

            $action = $this->actionFactory->newLinkAction($iconUrl, $externalIm, $chatUrl);
            $entry->addAction($action);
        }
    }
}
