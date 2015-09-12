<?php

namespace Model\Entity;

use DateTime;

/**
 * @property int $id
 * @property User $editor m:hasOne(editor_id)
 * @property Tag[] $tags m:hasMany
 * @property Question|NULL $question m:hasOne
 * @property Answer|NULL $answer m:hasOne
 * @property string|NULL $namespace
 * @property string $access
 * @property bool $removed
 */
class Entry extends \LeanMapper\Entity
{
    public function getTagIds()
    {
        $tagIds = array();
        foreach ($this->tags as $tag) {
            $tagIds[] = $tag->id;
        }

        return $tagIds;
    }
}


/**
 * @property int $id
 * @property Entry $entry m:hasOne(entry_id)
 * @property User $editor m:hasOne(editor_id)
 * @property User $assignedBy m:hasOne(assigned_by_id)
 * @property DateTime $date
 */
class EditorHistory extends \LeanMapper\Entity
{

}
