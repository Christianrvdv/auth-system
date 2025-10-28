<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WebControllerTest extends WebTestCase
{
    public function testHomepage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Bienvenido al Sistema');
    }

    public function testLoginPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Iniciar Sesión');
        $this->assertSelectorExists('input[name="email"]');
        $this->assertSelectorExists('input[name="password"]');
    }

    public function testRegisterPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Crear Cuenta');
        $this->assertSelectorExists('input[name="registration_form[firstName]"]');
        $this->assertSelectorExists('input[name="registration_form[email]"]');
    }

    public function testUsersPageRequiresAdminRole(): void
    {
        $client = static::createClient();

        // Intentar acceder sin autenticación
        $client->request('GET', '/users');
        $this->assertResponseRedirects('/login');

        // Crear usuario regular
        $user = new User();
        $user->setEmail('user@test.com');
        $user->setFirstName('Regular');
        $user->setLastName('User');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);

        $entityManager = $client->getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        // Autenticar como usuario regular
        $client->loginUser($user);

        // Intentar acceder a gestión de usuarios sin ser admin
        $client->request('GET', '/users');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUsersPageAsAdmin(): void
    {
        $client = static::createClient();

        // Crear usuario admin
        $user = new User();
        $user->setEmail('admin@test.com');
        $user->setFirstName('Admin');
        $user->setLastName('User');
        $user->setPassword('password');
        $user->setRoles(['ROLE_ADMIN']);

        $entityManager = $client->getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        // Autenticar como admin
        $client->loginUser($user);

        // Acceder a gestión de usuarios
        $crawler = $client->request('GET', '/users');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Gestión de Usuarios');
    }

    public function testUserDeletion(): void
    {
        $client = static::createClient();

        // Crear usuario admin
        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setPassword('password');
        $admin->setRoles(['ROLE_ADMIN']);

        // Crear usuario a eliminar
        $userToDelete = new User();
        $userToDelete->setEmail('delete@test.com');
        $userToDelete->setFirstName('Delete');
        $userToDelete->setLastName('User');
        $userToDelete->setPassword('password');
        $userToDelete->setRoles(['ROLE_USER']);

        $entityManager = $client->getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($admin);
        $entityManager->persist($userToDelete);
        $entityManager->flush();

        $userId = $userToDelete->getId();

        // Autenticar como admin
        $client->loginUser($admin);

        // Eliminar usuario
        $client->request('POST', "/users/{$userId}/delete");
        $this->assertResponseRedirects('/users');

        // Verificar que el usuario fue eliminado
        $deletedUser = $entityManager->getRepository(User::class)->find($userId);
        $this->assertNull($deletedUser);
    }

    public function testSelfDeletionPrevention(): void
    {
        $client = static::createClient();

        // Crear usuario admin
        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setPassword('password');
        $admin->setRoles(['ROLE_ADMIN']);

        $entityManager = $client->getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($admin);
        $entityManager->flush();

        $adminId = $admin->getId();

        // Autenticar como admin
        $client->loginUser($admin);

        // Intentar eliminarse a sí mismo
        $client->request('POST', "/users/{$adminId}/delete");
        $this->assertResponseRedirects('/users');

        // Verificar que el usuario NO fue eliminado
        $existingAdmin = $entityManager->getRepository(User::class)->find($adminId);
        $this->assertNotNull($existingAdmin);
    }
}
