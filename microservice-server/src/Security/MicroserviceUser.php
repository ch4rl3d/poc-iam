<?php

namespace App\Security;
use Symfony\Component\Security\Core\User\UserInterface;


class MicroserviceUser implements UserInterface
{
    public function __construct(
        private string $clientId,
    ) {
    }

    public function getRoles(): array
    {
        return [];
    }

    public function getUserIdentifier(): string
    {
        return $this->clientId;
    }

    public function eraseCredentials(): void
    {
        return;
    }
}