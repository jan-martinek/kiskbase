<?php

namespace Model\Repository;

/**
 * Users management.
 */
class GoogleUserRepository extends Repository
{
    private $userCreationAllowed = false;

    public function findByGoogleId($user)
    {
        if ($info = $this->connection->query('SELECT * FROM [user] WHERE [google_id] = %s', $user)->fetch()) {
            return (object) array(
                'id' => $info->id,
                'roles' => array(),
                'name' => $info->name,
                'surname' => $info->surname,
                'email' => $info->email,
                'googleId' => $info->google_id,
                'picture' => $info->picture,
            );
        } else {
            return false;
        }
    }

    public function findByEmail($email)
    {
        if ($info = $this->connection->query('SELECT * FROM [user] WHERE [email] = %s', $email)->fetch()) {
            return (object) array(
                'id' => $info->id,
                'roles' => array(),
                'name' => $info->name,
                'surname' => $info->surname,
                'email' => $info->email,
                'googleId' => $info->google_id,
                'picture' => $info->picture,
            );
        } else {
            return false;
        }
    }

    public function registerFromGoogle($user, $me)
    {
        $this->connection->query('INSERT INTO [user]', array(
            'name' => $me->name,
            'surname' => $me->familyName,
            'email' => $me->email,
            'google_id' => $me->id,
            'picture' => $me->picture,
        ));

        return $this->findByGoogleId($user);
    }

    public function updateGoogleAccessToken($user, $token)
    {
        return $this->connection->query('UPDATE [user] SET [google_access_token] = %s', $token, 'WHERE [google_id] = %s', $user);
    }

    public function allowUserCreation($bool)
    {
        $this->userCreationAllowed = $bool ? true : false;
    }

    public function isUserCreationAllowed()
    {
        return $this->userCreationAllowed;
    }
}
