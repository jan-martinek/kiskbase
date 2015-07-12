<?php

namespace App\Presenters;

use Nette\Application\UI\Form;
use Nette\Application\BadRequestException;

class TablePresenter extends BasePresenter
{
    private $tableName;
    
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

    public function actionNewItem($table) 
    {
        $this->tableName = $table;
    }

    
    public function renderNewItem($table)
    {
        $this->template->tableName = $table;
    }


    /**
     * @return Nette\Application\UI\Form
     */
    protected function createComponentNewItemForm()
    {
        $tableName = $this->tableName;
        $tableInfo = $this->tableManager->getInfo($tableName);
        
        $form = new Form();
        foreach ($tableInfo as $column) {
            if ($column->Field === 'id') continue;
            
            $description = $column->Type;
            if ($column->Comment) {
                $description .= ', ' . $column->Comment;
            }
            
            if ($values = $this->tableManager->getEnumValues($column->Type)) {
                $values = str_getcsv($values, ",", '\'');
                $form->addSelect($column->Field, $column->Field, array_combine($values, $values));
            } else if (preg_match('/^varchar\(([0-9]+)\)$/', $column->Type, $matches)) {
                $form->addText($column->Field, $column->Field)
                    ->setMaxLength($matches[1])
                    ->setOption('description', $description);
            } else if (preg_match('/^(int)/', $column->Type)) {
                $form->addText($column->Field, $column->Field)
                    ->setOption('description', $description);
            } else {
                $form->addTextarea($column->Field, $column->Field)
                ->setOption('description', $description);        
            }
        }
        $form->addSubmit('save', $this->translator->translate('messages.table.createItem'));
        
        $form->onSuccess[] = function ($form) 
        {
            $values = (array) $form->getValues();
            $id = $this->tableManager->createRow($this->tableName, $values);
            $this->flashMessage($this->translator->translate('messages.table.newItemCreated'));
            $this->redirect('Table:table#rowId' . $id, $this->tableName);
        };

        return $form;
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
            $this->db->query("UPDATE [$table] SET [$column] = %s", $data, 'WHERE [id] = %i', $id);
        } catch (Exception $e) {
            $httpResponse->setCode(\Nette\Http\Response::S403_FORBIDDEN);
            $this->terminate();
        }
        $httpResponse->setCode(\Nette\Http\Response::S200_OK);
        $this->terminate();
    }
}
