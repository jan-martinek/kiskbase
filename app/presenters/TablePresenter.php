<?php

namespace App\Presenters;

use Nette\Application\BadRequestException;

class TablePresenter extends BasePresenter
{
    /** @var \Model\TableManager @inject */
    public $tableManager;

    private $restrictedTables = array('answer', 'entry', 'entry_tag', 'question', 'tag', 'user');

    public function renderDefault()
    {
        $tables = $this->tableManager->findAllTables();

        $this->template->tables = array();
        foreach ($tables as $table) {
            $this->template->tables[$table] = $this->tableManager->getInfo($table);
        }
    }

    public function renderTable($table)
    {
        if (in_array($table, $this->restrictedTables)) {
            throw new BadRequestException('Application tables are not publicly accessible.', 403);
        }

        $this->template->tableName = $table;
        $this->template->tableInfo = $this->tableManager->getInfo($table);
        $this->template->data = $this->tableManager->getData($table);
        $this->template->fieldOptions = $this->tableManager->getFieldOptions($table);
    }

    public function handleSaveData()
    {
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
