<?php

namespace Model\Repository;

class TagRepository extends Repository
{
	public function lookup($query) {
		return $this->connection->query('SELECT [tag.id], [tag.text], count(*) AS [count] 
			FROM [tag] 
			INNER JOIN entry_tag ON entry_tag.tag_id = tag.id 
			WHERE [text] LIKE %~like~', $query, 'GROUP BY tag.id')->fetchAll();
	}
	
	public function findByText($text) 
	{
		$row = $this->connection
			->select('*')->from($this->getTable())
			->where('text = %s', $text)
			->fetch();
		
		if ($row) {
			return $this->createEntity($row);
		} else {
			return false;
		}
	}
	
	public function purge() {
		$ids = $this->connection->query('SELECT group_concat(tag.id)
			FROM tag
        	LEFT JOIN entry_tag 
            ON tag.id = entry_tag.tag_id
			WHERE entry_tag.tag_id IS NULL')->fetchSingle();
		if ($ids) $this->connection->query('DELETE FROM tag WHERE id IN (' . $ids . ')');
	}
}