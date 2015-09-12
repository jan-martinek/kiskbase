<?php

namespace Model;

use Nette;

class TableManager extends Nette\Object
{
    /** @var \DibiConnection @inject */
    public $connection;

    private $restrictedTables = array('answer', 'entry', 'entry_tag', 'editorhistory', 'question', 'tag', 'user', 'checklist');

    public function __construct(\DibiConnection $connection)
    {
        $this->connection = $connection;
    }

    public function isRestricted($table) 
    {
        return in_array($table, $this->restrictedTables);
    }

    public function findAllTables()
    {
        $result = $this->connection->query('SHOW TABLES');

        $tables = array();
        while ($table = $result->fetchSingle()) {
            if (!in_array($table, $this->restrictedTables)) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    public function getData($table, $assoc = null)
    {
        $query = $this->connection->query("SELECT * FROM [$table]");
        if ($assoc) {
            return $query->fetchAssoc($assoc);
        } else {
            return $query->fetchAll();
        }
    }

    public function getInfo($table)
    {
        if (!in_array($table, $this->restrictedTables)) {
            return $this->connection->query("SHOW FULL COLUMNS FROM [$table]")->fetchAll();
        } else {
            throw new Exception("No table named $table is available.");
        }
    }

    public function getEnumValues($fieldType) {
        if (preg_match('/^enum\((.+)\)$/', $fieldType, $matches)) {
            return $matches[1];
        } else {
            return false;
        }
    }

    public function getFieldOptions($table)
    {
        //enum
        $tableDescription = $this->connection->query("DESCRIBE [$table]")->fetchAssoc('Field');
        $fieldOptions = array();
        foreach ($tableDescription as $column => $field) {
            if ($values = $this->getEnumValues($field->Type)) {
                $fieldOptions[$column] = $values;
            }
        }

        //foreign keys
        $tableDescription = $this->connection->query("SELECT COLUMN_NAME as [column], REFERENCED_TABLE_NAME as [table] FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE TABLE_NAME = '$table' AND COLUMN_NAME LIKE '%_id' AND REFERENCED_COLUMN_NAME = 'id'")->fetchPairs('column', 'table');
        foreach ($tableDescription as $column => $table) {
            if (count($this->connection->query("SHOW COLUMNS FROM [$table] LIKE 'name';"))) {
                $fieldOptions[$column] = ':'.$table;
            }
        }

        return $fieldOptions;
    }
    
    public function createRow($table, $values) {
        if ($this->connection->query("INSERT INTO [$table]", $values)) {
            return $this->connection->insertId;
        } else {
            return false;
        }
    }
    
    public function saveValue($table, $column, $id, $data) {
        $this->connection->query("SET sql_mode = 'STRICT_ALL_TABLES';");
        $this->connection->query("UPDATE [$table] SET [$column] = %s", $data, 'WHERE [id] = %i', $id);
    }
    
}
