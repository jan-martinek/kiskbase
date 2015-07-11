<?php

namespace App\Presenters;

use Nette\Application\Responses\JsonResponse;
use Nette\Application\BadRequestException;


class TablePresenter extends BasePresenter {

	/** @var \Model\TableManager @inject */
	public $tableManager;

	private $restrictedTables = array('answer', 'entry', 'entry_tag', 'question', 'tag', 'user');

	public function renderDefault() {
		$tables = $this->tableManager->findAllTables();
		
		$this->template->tables = array();
		foreach ($tables as $table) {
			$this->template->tables[$table] = $this->tableManager->getTableInfo($table);
		}
	}

	public function renderTable($table) {
		if (in_array($table, $this->restrictedTables)) {
			throw new BadRequestException('Application tables are not publicly accessible.', 403);	
		}
		
		$this->template->tableName = $table;
		$this->template->data = $this->db->query("SELECT * FROM [$table]")->fetchAll();
		
		
		//enum
		$tableDescription = $this->db->query("DESCRIBE [$table]")->fetchAssoc('Field');
		$fieldOptions = array();
		foreach ($tableDescription as $column => $field) {
			if (preg_match('/^enum\((.+)\)$/', $field->Type, $matches)) {
				$fieldOptions[$column] = $matches[1];
			}
		}
		
		//foreign keys
		$tableDescription = $this->db->query("SELECT COLUMN_NAME as [column], REFERENCED_TABLE_NAME as [table] FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE TABLE_NAME = '$table' AND COLUMN_NAME LIKE '%_id' AND REFERENCED_COLUMN_NAME = 'id'")->fetchPairs('column', 'table');
		foreach ($tableDescription as $column => $table) {
			if (count($this->db->query("SHOW COLUMNS FROM [$table] LIKE 'name';"))) {
				$fieldOptions[$column] = ':' . $table;
			}
		}
		
		$this->template->fieldOptions = $fieldOptions;
	}
	
	public function handleSaveData() {
		$httpRequest = $this->context->getByType('Nette\Http\Request');		
		$column = trim($httpRequest->getPost('column'));
		$data = trim($httpRequest->getPost('data'));
		$table = trim($httpRequest->getPost('table'));
		$id = trim($httpRequest->getPost('id'));
		
		$httpResponse = $this->context->getService('httpResponse');
		$this->db->query("SET sql_mode = 'STRICT_ALL_TABLES';");
		try {
			if ($id === 'new') {
				$this->db->query("INSERT INTO [$table]", array($column => $data));		
			} else {
				$this->db->query("UPDATE [$table] SET [$column] = %s", $data, 'WHERE [id] = %i', $id);	
			}
		} catch (Exception $e) {
			$httpResponse->setCode(\Nette\Http\Response::S403_FORBIDDEN);
			$this->terminate();
		}
		$httpResponse->setCode(\Nette\Http\Response::S200_OK);
		$this->terminate();
	}
}
