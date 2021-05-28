<?php

namespace OCA\OJSXC\StanzaHandlers;

use OCA\OJSXC\AppInfo\Application;
use OCA\OJSXC\Db\MessageMapper;
use OCA\OJSXC\IUserProvider;
use OCP\ILogger;
use OCA\OJSXC\Db\Message as MessageEntity;

/**
 * Class Message
 *
 * @package OCA\OJSXC\StanzaHandlers
 */
class Message extends StanzaHandler
{

	/**
	 * @var MessageMapper $messageMapper
	 */
	private $messageMapper;

	/**
	 * @var IUserProvider $userProvider
	 */
	private $userProvider;

	/**
	 * @var string $type
	 */
	private $type;

	/**
	 * @var  array $values
	 */
	private $values;

	/**
	 * @var ILogger $logger
	 */
	private $logger;

	/**
	 * Message constructor.
	 *
	 * @param string $userId
	 * @param MessageMapper $messageMapper
	 * @param IUserProvider $userProvider
	 */
	public function __construct($userId, MessageMapper $messageMapper, IUserProvider $userProvider, ILogger $logger)
	{
		parent::__construct($userId);
		$this->messageMapper = $messageMapper;
		$this->userProvider = $userProvider;
		$this->logger = $logger;
	}

	/**
	 * @param array $stanza
	 */
	public function handle(array $stanza)
	{
		// Parse the username from the XML stanza to a NC userid
		$to = $this->getAttribute($stanza, 'to');
		$pos = strrpos($to, '@');
		$this->to = substr($to, 0, $pos);
		$this->to = Application::convertToRealUID(Application::deSanitize($this->to));

		if (!$this->userProvider->hasUserByUID($this->to)) {
			$this->logger->warning('User ' . $this->userId . ' is trying to send a message to ' . $this->to . ' but this isn\'t allowed');
			return;
		}

		foreach ($stanza['value'] as $keyRaw => $value) {
			// remove namespace from key as it is unneeded and cause problems
			$key = substr($keyRaw, strpos($keyRaw, '}') + 1, strlen($keyRaw));
			// fetch namespace from key to read it
			$ns = substr($keyRaw, 1, strpos($keyRaw, '}') - 1);

			$this->values[] = [
				"name" => $key,
				"value" => (string)$value,
				"attributes" => ["xmlns" => $ns]
			];
		}
		$this->type = $this->getAttribute($stanza, 'type');

		$message = new MessageEntity();
		$message->setTo($this->to);
		$message->setFrom($this->userId);
		$message->setValue($this->values);
		$message->setType($this->type);
		$this->messageMapper->insert($message);
		$this->values = [];
	}
}
