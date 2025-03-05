<?php
declare(strict_types=1);
namespace OCA\Contacts\Invitation;

class InvitationAcceptRequestDto{
    public string $recipientProvider;
    public string $invitationToken;
    public string $userID;
    public string $email;
    public string $name;
}