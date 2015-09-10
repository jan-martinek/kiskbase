<?php

namespace Model\Entity;

use DateTime;

/**
 * @property int $id
 * @property Entry|NULL $entry m:hasOne
 * @property User $authoredBy m:hasOne(user_id)
 * @property DateTime $created_at
 * @property string $text
 * @property bool $current
 */
class Answer extends \LeanMapper\Entity
{
}

/**
 * @property int $id
 * @property Entry|NULL $entry m:hasOne
 * @property User $authoredBy m:hasOne(user_id)
 * @property DateTime $created_at
 * @property string $text
 * @property bool $current
 */
class Question extends \LeanMapper\Entity
{
    public function extractNamespace() {
        if (preg_match('/^([^\s\:]{1,30}):\s(.+)$/', $this->text, $matches)) {
            return $matches[1];
    } else {
            return '';
        }
    }
    
    public function extractTextWithoutNamespace() {
        if (preg_match('/^([^\s\:]{1,30}):\s(.+)$/', $this->text, $matches)) {
            return $matches[2];
    } else {
            return $this->text;
        }
    }
}

/**
 * @property int $id
 * @property string $text
 * @property Entry[] $entries m:hasMany(tag_id:entry_tag:entry_id)
 */
class Tag extends \LeanMapper\Entity
{
}

/**
 * @property int $id
 * @property Entry[] $editorOf m:belongsToMany(editor_id)
 * @property Checklist[] $checklists m:belongsToMany(user_id)
 * @property string $name
 * @property string $surname
 * @property string $email
 * @property string $google_id
 * @property string $google_access_token
 * @property string $picture
 */
class User extends \LeanMapper\Entity
{
}

/**
 * @property int $id
 * @property User $owner m:hasOne(user_id)
 * @property Entry $source m:hasOne(entry_id)
 * @property DateTime $created_at
 * @property DateTime $updated_at
 * @property string $name
 * @property string $text
 * @property string $state
 * @property string $public
 * @property bool $removed
 */
class Checklist extends \LeanMapper\Entity
{
}