<?php

namespace OCA\OJSXC;

use OCA\OJSXC\Db\Stanza;
use OCP\ILogger;
use Sabre\Xml\Writer;

class StanzaLogger
{

	/**
	 * @var ILogger
	 */
	private $logger;

	/**
	 * When a stanza is received by the server.
	 */
	const RECEIVING = "Receiving";

	/**
	 * When the server directly sends a stanza to a client.
	 */
	const SENDING = "Sending";

	/**
	 * When the server stores a message to send it using the longpoll table.
	 */
	const STORING = "Storing";
	private $userId;


	public function __construct(ILogger $logger, $userId)
	{
		$this->logger = $logger;
		$this->userId = $userId;
	}

	public function log(Stanza $stanza, $action)
	{
		if (\OC::$server->getConfig()->getSystemValue('loglevel') === \OCP\Util::DEBUG) {
			// only serialize when needed
			$writer = new Writer();
			$writer->openMemory();
			$writer->write($stanza);
			$this->logger->debug($action . " {" . $this->userId . "} : " . $writer->outputMemory(), ["app" => "ojsxc"]);
		}
	}

	public function logRaw($stanza, $action)
	{
		$this->logger->debug($action . " {" . $this->userId . "} : " . $stanza, ["app" => "ojsxc"]);
	}
}
