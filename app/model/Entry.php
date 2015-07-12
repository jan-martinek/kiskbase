<?php

namespace Model\Entity;

/**
 * @property int $id
 * @property User $editor m:hasOne(guarantor_id)
 * @property Tag[] $tags m:hasMany
 * @property Question|NULL $question m:hasOne
 * @property Answer|NULL $answer m:hasOne
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
