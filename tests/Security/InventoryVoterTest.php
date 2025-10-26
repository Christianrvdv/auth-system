<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Entity\InventoryItem;
use App\Security\InventoryVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;

class InventoryVoterTest extends TestCase
{
    private $security;
    private $voter;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->voter = new InventoryVoter($this->security);
    }

    public function testAdminCanDelete(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->security->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $result = $this->voter->vote($token, null, [InventoryVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testUserCannotDelete(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->security->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $result = $this->voter->vote($token, null, [InventoryVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }

    public function testInventoryManagerCanEdit(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_INVENTORY_MANAGER']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $item = new InventoryItem();

        $this->security->method('isGranted')
            ->with('ROLE_INVENTORY_MANAGER')
            ->willReturn(true);

        $result = $this->voter->vote($token, $item, [InventoryVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }
}
