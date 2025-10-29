<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\InventoryItem;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InventoryVoter extends Voter
{
    const VIEW = 'VIEW_INVENTORY';
    const EDIT = 'EDIT_INVENTORY';
    const DELETE = 'DELETE_INVENTORY';
    const CREATE = 'CREATE_INVENTORY';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::CREATE])) {
            return false;
        }

        if ($subject instanceof InventoryItem || $subject === null) {
            return true;
        }

        return false;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($user);
            case self::EDIT:
                return $this->canEdit($user, $subject);
            case self::DELETE:
                return $this->canDelete($user);
            case self::CREATE:
                return $this->canCreate($user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView(User $user): bool
    {
        // ROLE_USER puede ver inventario
        return $this->security->isGranted('ROLE_USER');
    }

    private function canEdit(User $user, ?InventoryItem $item): bool
    {
        // Solo managers y admin pueden editar
        return $this->security->isGranted('ROLE_INVENTORY_MANAGER');
    }

    private function canDelete(User $user): bool
    {
        // Solo admin puede eliminar
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canCreate(User $user): bool
    {
        // Inventory managers y superiores pueden crear
        return $this->security->isGranted('ROLE_INVENTORY_MANAGER');
    }
}
