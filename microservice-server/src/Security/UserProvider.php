<?php


namespace App\Security;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{

    /*
     * @throws UserNotFoundException if the user is not found
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return new MicroserviceUser($identifier);
    }


    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof MicroserviceUser) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return new MicroserviceUser($user->getUserIdentifier());
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass(string $class): bool
    {
        return MicroserviceUser::class === $class || is_subclass_of($class, MicroserviceUser::class);
    }
}