<?php
// tests/Security/InventoryVoterTest.php

namespace App\Tests\Security;

use App\Entity\User;
use App\Entity\InventoryItem;
use App\Security\InventoryVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class InventoryVoterTest extends TestCase
{
    private $security;
    private $voter;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->voter = new InventoryVoter($this->security);
    }

    // TESTS DE DELETE
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

    public function testManagerCannotDelete(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_MANAGER']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->security->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $result = $this->voter->vote($token, null, [InventoryVoter::DELETE]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }

    // TESTS DE EDIT
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

    public function testAdminCanEdit(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $item = new InventoryItem();

        $this->security->method('isGranted')
            ->with('ROLE_INVENTORY_MANAGER')
            ->willReturn(true); // Admin también tiene permisos de manager

        $result = $this->voter->vote($token, $item, [InventoryVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testUserCannotEdit(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $item = new InventoryItem();

        $this->security->method('isGranted')
            ->with('ROLE_INVENTORY_MANAGER')
            ->willReturn(false);

        $result = $this->voter->vote($token, $item, [InventoryVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }

    // TESTS DE VIEW
    public function testUserCanView(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->security->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true);

        $result = $this->voter->vote($token, null, [InventoryVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testInventoryManagerCanView(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_INVENTORY_MANAGER']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->security->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true);

        $result = $this->voter->vote($token, null, [InventoryVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testAdminCanView(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->security->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true);

        $result = $this->voter->vote($token, null, [InventoryVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    // TESTS DE CREATE
    public function testInventoryManagerCanCreate(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_INVENTORY_MANAGER']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->security->method('isGranted')
            ->with('ROLE_INVENTORY_MANAGER')
            ->willReturn(true);

        $result = $this->voter->vote($token, null, [InventoryVoter::CREATE]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testAdminCanCreate(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->security->method('isGranted')
            ->with('ROLE_INVENTORY_MANAGER')
            ->willReturn(true);

        $result = $this->voter->vote($token, null, [InventoryVoter::CREATE]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }

    public function testUserCannotCreate(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->security->method('isGranted')
            ->with('ROLE_INVENTORY_MANAGER')
            ->willReturn(false);

        $result = $this->voter->vote($token, null, [InventoryVoter::CREATE]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }

    public function testManagerCannotCreate(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_MANAGER']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->security->method('isGranted')
            ->with('ROLE_INVENTORY_MANAGER')
            ->willReturn(false);

        $result = $this->voter->vote($token, null, [InventoryVoter::CREATE]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }

    // TESTS USUARIO NO AUTENTICADO
    public function testUnauthenticatedUserCannotAccess(): void
    {
        // Crear un token mock para usuario no autenticado
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null); // Usuario no autenticado retorna null

        $result = $this->voter->vote($token, null, [InventoryVoter::VIEW]);

        $this->assertEquals(Voter::ACCESS_DENIED, $result);
    }

    // TESTS ATRIBUTOS NO SOPORTADOS
    public function testUnsupportedAttribute(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $result = $this->voter->vote($token, null, ['UNSUPPORTED_ATTRIBUTE']);

        // El votador debe abstenerse (ACCESS_ABSTAIN) cuando no soporta el atributo
        $this->assertEquals(Voter::ACCESS_ABSTAIN, $result);
    }

    // TESTS SUBJECT NO SOPORTADO
    public function testUnsupportedSubject(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $result = $this->voter->vote($token, new \stdClass(), [InventoryVoter::VIEW]);

        // El votador debe abstenerse (ACCESS_ABSTAIN) cuando no soporta el subject
        $this->assertEquals(Voter::ACCESS_ABSTAIN, $result);
    }

    // NUEVO TEST SIMPLIFICADO: Verificar que el votador soporta los atributos correctos
    public function testVoterSupportsInventoryAttributes(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $item = new InventoryItem();

        // Configurar el mock para diferentes llamadas
        $this->security->expects($this->exactly(4))
            ->method('isGranted')
            ->willReturnCallback(function($role) {
                return in_array($role, ['ROLE_USER', 'ROLE_INVENTORY_MANAGER']);
            });

        // Verificar que no se abstiene para los atributos soportados
        $result1 = $this->voter->vote($token, $item, [InventoryVoter::VIEW]);
        $this->assertNotEquals(Voter::ACCESS_ABSTAIN, $result1);

        $result2 = $this->voter->vote($token, null, [InventoryVoter::CREATE]);
        $this->assertNotEquals(Voter::ACCESS_ABSTAIN, $result2);

        $result3 = $this->voter->vote($token, $item, [InventoryVoter::EDIT]);
        $this->assertNotEquals(Voter::ACCESS_ABSTAIN, $result3);

        $result4 = $this->voter->vote($token, null, [InventoryVoter::DELETE]);
        $this->assertNotEquals(Voter::ACCESS_ABSTAIN, $result4);
    }

    // TEST ADICIONAL: Verificar que el votador funciona con subject null para EDIT
    public function testEditWithNullSubject(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_INVENTORY_MANAGER']);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->security->method('isGranted')
            ->with('ROLE_INVENTORY_MANAGER')
            ->willReturn(true);

        $result = $this->voter->vote($token, null, [InventoryVoter::EDIT]);

        $this->assertEquals(Voter::ACCESS_GRANTED, $result);
    }
}
