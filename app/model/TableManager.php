<?php

namespace Model;

use Nette;

class TableManager extends Nette\Object
{
	/** @var \DibiConnection @inject */
	public $connection;

	private $restrictedTables = array('answer', 'entry', 'entry_tag', 'question', 'tag', 'user');

	public function __construct( \DibiConnection $connection) {	
		$this->connection = $connection;
	}
	
	public function findAllTables() {
		$result = $this->connection->query('SHOW TABLES');
		
		$tables = array();
		while ($table = $result->fetchSingle()) {
			if (!in_array($table, $this->restrictedTables)) {
				$tables[] = $table;
			}
		}
		
		return $tables;
	}
	
	public function getTableInfo($table) {
		if (!in_array($table, $this->restrictedTables)) {
			return $this->connection->query("SHOW FULL COLUMNS FROM [$table]")->fetchAll();
		} else {
			throw new Exception("No table named $table is available.");
		}
	}
	
	public function getFieldOptions($table) {
		//enum
		$tableDescription = $this->connection->query("DESCRIBE [$table]")->fetchAssoc('Field');
		$fieldOptions = array();
		foreach ($tableDescription as $column => $field) {
			if (preg_match('/^enum\((.+)\)$/', $field->Type, $matches)) {
				$fieldOptions[$column] = $matches[1];
			}
		}
		
		//foreign keys
		$tableDescription = $this->connection->query("SELECT COLUMN_NAME as [column], REFERENCED_TABLE_NAME as [table] FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE TABLE_NAME = '$table' AND COLUMN_NAME LIKE '%_id' AND REFERENCED_COLUMN_NAME = 'id'")->fetchPairs('column', 'table');
		foreach ($tableDescription as $column => $table) {
			if (count($this->connection->query("SHOW COLUMNS FROM [$table] LIKE 'name';"))) {
				$fieldOptions[$column] = ':' . $table;
			}
		}
		
		return $fieldOptions;
	}
	
}