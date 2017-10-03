<?php
namespace OCA\OJSXC\Http;

use OCA\OJSXC\StanzaLogger;
use OCP\AppFramework\Http\Response;
use Sabre\Xml\Writer;
use OCA\OJSXC\Db\Stanza;

/**
 * Class XMPPResponse
 *
 * @package OCA\OJSXC\Http
 */
class XMPPResponse extends Response
{

	/**
	 * @var Writer $writer
	 */
	private $writer;

	/**
	 * @var StanzaLogger
	 */
	private $stanzaLogger;

	/**
	 * XMPPResponse constructor.
	 *
	 * @param Stanza|null $stanza
	 * @param StanzaLogger $stanzaLogger
	 */
	public function __construct(StanzaLogger $stanzaLogger, Stanza $stanza = null)
	{
		$this->addHeader('Content-Type', 'text/xml');
		$this->writer = new Writer();
		$this->writer->openMemory();
		$this->writer->startElement('body');
		$this->writer->writeAttribute('xmlns', 'http://jabber.org/protocol/httpbind');
		if (!is_null($stanza)) {
			$this->writer->write($stanza);
		}
		$this->stanzaLogger = $stanzaLogger;
	}

	/**
	 * @param Stanza $input
	 */
	public function write(Stanza $input)
	{
		$this->stanzaLogger->log($input, StanzaLogger::SENDING);
		$this->writer->write($input);
	}

	/**
	 * @return string
	 */
	public function render()
	{
		$this->writer->endElement();
		return $this->writer->outputMemory();
	}

	/**
	 * Terminates the Chat connection with the `x-nc-not_allowed_to_chat` condition.
	 */
	public function terminate()
	{
		$this->writer = new Writer();
		$this->writer->openMemory();
		$this->writer->startElement('body');
		$this->writer->writeAttribute('xmlns', 'http://jabber.org/protocol/httpbind');
		$this->writer->writeAttribute('type', 'terminate');
		$this->writer->writeAttribute('condition', 'x-nc-not_allowed_to_chat');
	}
}
