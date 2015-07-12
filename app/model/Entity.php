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
