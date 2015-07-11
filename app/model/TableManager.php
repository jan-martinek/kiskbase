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
	
}