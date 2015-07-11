<?php

namespace Model\Repository;

abstract class Repository extends \LeanMapper\Repository
{

	public function find($id)
	{
		$row = $this->connection->select('*')
			->from($this->getTable())
			->where('id = %i', $id)
			->fetch();

		if ($row === false) {
			throw new \Exception('Entity was not found.');
		}
		return $this->createEntity($row);
	}

	public function findAll()
	{
		return $this->createEntities(
			$this->connection->select('*')
				->from($this->getTable())
				->fetchAll()
		);
	}

}

class AnswerRepository extends Repository
{

}

class QuestionRepository extends Repository
{

}

/**
	* @table entry_tag
	*/
class TagInfoRepository extends Repository
{
	
}

