<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument()
 */
class Guest
{
    public const ROLE_GUEST = 'GUEST';
    public const ROLE_ADMIN = 'ADMIN';

    /**
     * @MongoDB\Field(type="string")
     */
    private string $username;

    /**
     * @MongoDB\Field(type="string")
     */
    private string $role = self::ROLE_GUEST;

    /**
     * @var string JWT token to perform action on a room
     */
    private string $token;

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }
}
