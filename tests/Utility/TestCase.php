<?php

namespace OCA\OJSXC\Tests\Utility;

use OCA\OJSXC\AppInfo\Application;
use OCP\AppFramework\Db\Entity;
use Sabre\Xml\Service;
use PHPUnit\Framework\TestCase as CoreTestCase;

class TestCase extends CoreTestCase
{
	public static function assertSabreXmlEqualsXml($expected, $actual)
	{
		$service = new Service();

		$parsedExpected = $service->parse("<?xml version=\"1.0\" encoding=\"utf-8\"?><unit-wrapper>" . $expected . "</unit-wrapper>");
		$parsedActual = $service->parse("<?xml version=\"1.0\" encoding=\"utf-8\"?><unit-wrapper>" . $actual . "</unit-wrapper>");

		self::assertEquals($parsedExpected, $parsedActual, 'Failed asserting that two XML strings are equal.');
	}

	/**
	 * @param Entity[] $expected
	 * @param Entity[] $actual
	 * @param array $fields Use camelCase for this instead of snake_case!
	 */
	public static function assertObjectDbResultsEqual($expected, $actual, array $fields)
	{
		$expectedArray = [];
		$actualArray = [];

		foreach ($expected as $exp) {
			$expectedArray[] = (array) $exp;
		}

		foreach ($actual as $act) {
			$actualArray[] = (array) $act;
		}

		self::assertArrayDbResultsEqual($expectedArray, $actualArray, $fields);
	}

	public static function assertArrayDbResultsEqual(array $expected, array $actual, array $fields)
	{
		$expectedFiltered = [];
		$actualFiltered = [];

		foreach ($expected as $exp) {
			$r = [];
			foreach ($fields as $field) {
				$r[$field] = $exp[$field];
			}
			$expectedFiltered[] = $r;
		}

		foreach ($actual as $exp) {
			$r = [];
			foreach ($fields as $field) {
				$r[$field] = $exp[$field];
			}
			$actualFiltered[] = $r;
		}

		sort($actualFiltered);
		sort($expectedFiltered);

		self::assertCount(count($expected), $actual);
		self::assertEquals($expectedFiltered, $actualFiltered);
	}

	public function overwriteApplicationService(Application $app, $name, $newService)
	{
		$app->getContainer()->registerService($name, function () use ($newService) {
			return $newService;
		});
	}

	/**
	 * @brief this function is needed to reset some private static propertires
	 * which ar used for e.g. caches.
	 * @param $name
	 * @param $newValue
	 */
	public function setValueOfPrivateProperty($obj, $name, $newValue)
	{
		$refl = new \ReflectionObject($obj);
		$p = $refl->getProperty($name);
		$p->setAccessible(true);
		$p->setValue($obj, $newValue);
		$p->setAccessible(false);
	}
}
