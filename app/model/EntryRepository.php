<?php

namespace Model\Repository;

class EntryRepository extends Repository
{
    
    public function findAllAssocByNamespace() {
        $result = $this->createEntities($this->connection->query('SELECT [entry.*] FROM [entry]
            WHERE [removed] = 0')->fetchAll()
        ); 
        
        $namespacedResult = array();
            
        foreach ($result as $entry) {
            $namespacedResult[$entry->namespace ? $entry->namespace : '_none'][] = $entry;
        }
        
        ksort($namespacedResult);
        
        return $namespacedResult;
    }
    
    public function lookup($query)
    {
        $q = array(
            'answer.text%~like~' => $query,
            'question.text%~like~' => $query,
        );

        return $this->createEntities($this->connection->query('SELECT [entry.*] FROM [entry]
            JOIN [answer] ON [entry.answer_id] = [answer.id]
            JOIN [question] ON [entry.question_id] = [question.id]
            WHERE %or', $q)->fetchAll()
        );
    }

    public function lookupRelated($entry)
    {
        $rows = $this->connection->query(
            'SELECT [entry.*]
			FROM [entry_tag]
				JOIN [tag] ON ([tag.id] = [entry_tag.tag_id])
				JOIN [entry] ON [entry_tag.entry_id] = [entry.id]
			WHERE [tag_id] IN (SELECT [tag_id] FROM [entry_tag] WHERE [entry_tag.entry_id] = %i', $entry->id, ')
				AND [entry_tag.entry_id] != %i', $entry->id, '
			GROUP BY [entry_tag.entry_id]
			ORDER BY COUNT(*) DESC
			LIMIT 0, 10')->fetchAll();

        return $this->createEntities($rows);
    }
}

class EditorHistoryRepository extends Repository
{
   
}



