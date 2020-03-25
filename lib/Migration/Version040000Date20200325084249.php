<?php

declare(strict_types=1);

namespace OCA\OJSXC\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version040000Date20200325084249 extends SimpleMigrationStep
{

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options)
	{
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options)
	{
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('ojsxc_stanzas')) {
			$table = $schema->createTable('ojsxc_stanzas');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('from', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('to', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('stanza', 'string', [
				'notnull' => true,
				'length' => 200000,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('ojsxc_presence')) {
			$table = $schema->createTable('ojsxc_presence');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('userid', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('presence', 'string', [
				'notnull' => true,
				'length' => 200,
			]);
			$table->addColumn('last_active', 'bigint', [
				'notnull' => true,
				'length' => 8,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['userid'], 'userid_index');
		}
		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options)
	{
	}
}
