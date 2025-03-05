<?php

declare(strict_types=1);
namespace OCA\Contacts\Invitation;

class Invitation {
    public string $name; 
    public string $email;
    public \Datetime $createdAt; 
    public \Datetime $expiersAt;
    public \Datetime $acceptedAt;
    public bool $accepted;
    public string $token;
    public int $userId;
}
