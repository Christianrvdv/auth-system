<?php
// tests/Service/UserRegistrationServiceTest.php

namespace App\Tests\Service;

use App\DTO\RegisterUserDTO;
use App\Entity\User;
use App\Service\UserRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserRegistrationServiceTest extends TestCase
{
    private $em;
    private $passwordHasher;
    private $validator;
    private $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->service = new UserRegistrationService(
            $this->em,
            $this->passwordHasher,
            $this->validator
        );
    }

    public function testSuccessfulRegistration(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'role' => 'ROLE_USER'
        ];

        $dto = new RegisterUserDTO($data);

        // Mock validation
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());

        // Mock repository
        $repository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $this->em->method('getRepository')->willReturn($repository);
        $this->passwordHasher->method('hashPassword')->willReturn('hashed_password');

        // Mock persist and flush
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->register($dto);

        $this->assertTrue($result['success']);
        $this->assertEquals('test@example.com', $result['user']['email']);
        $this->assertEquals('John', $result['user']['firstName']);
        $this->assertEquals('Doe', $result['user']['lastName']);
        $this->assertEquals(['ROLE_USER'], $result['user']['roles']);
    }

    public function testRegistrationWithExistingEmail(): void
    {
        $data = [
            'email' => 'existing@example.com',
            'password' => 'password123',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'role' => 'ROLE_USER'
        ];

        $dto = new RegisterUserDTO($data);
        $existingUser = new User();

        $this->validator->method('validate')->willReturn(new ConstraintViolationList());

        $repository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repository->method('findOneBy')->willReturn($existingUser);

        $this->em->method('getRepository')->willReturn($repository);

        $result = $this->service->register($dto);

        $this->assertFalse($result['success']);
        $this->assertContains('El usuario con este email ya existe', $result['errors']);
    }

    // NUEVOS TESTS DE VALIDACIÓN
    public function testRegistrationWithInvalidEmail(): void
    {
        $data = [
            'email' => 'invalid-email',
            'password' => 'password123',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'role' => 'ROLE_USER'
        ];

        $dto = new RegisterUserDTO($data);

        // Mock validation errors
        $violations = new ConstraintViolationList([
            new ConstraintViolation('El formato del email no es válido', null, [], '', 'email', 'invalid-email'),
        ]);

        $this->validator->method('validate')->willReturn($violations);

        $result = $this->service->register($dto);

        $this->assertFalse($result['success']);
        $this->assertContains('El formato del email no es válido', $result['errors']);
    }

    public function testRegistrationWithShortPassword(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => '123',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'role' => 'ROLE_USER'
        ];

        $dto = new RegisterUserDTO($data);

        // Mock validation errors
        $violations = new ConstraintViolationList([
            new ConstraintViolation('La contraseña debe tener al menos 6 caracteres', null, [], '', 'password', '123'),
        ]);

        $this->validator->method('validate')->willReturn($violations);

        $result = $this->service->register($dto);

        $this->assertFalse($result['success']);
        $this->assertContains('La contraseña debe tener al menos 6 caracteres', $result['errors']);
    }

    public function testRegistrationWithBlankFirstName(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'firstName' => '',
            'lastName' => 'Doe',
            'role' => 'ROLE_USER'
        ];

        $dto = new RegisterUserDTO($data);

        // Mock validation errors
        $violations = new ConstraintViolationList([
            new ConstraintViolation('El nombre es obligatorio', null, [], '', 'firstName', ''),
        ]);

        $this->validator->method('validate')->willReturn($violations);

        $result = $this->service->register($dto);

        $this->assertFalse($result['success']);
        $this->assertContains('El nombre es obligatorio', $result['errors']);
    }

    public function testRegistrationWithInvalidRole(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'role' => 'INVALID_ROLE'
        ];

        $dto = new RegisterUserDTO($data);

        // Mock validation errors
        $violations = new ConstraintViolationList([
            new ConstraintViolation('El rol debe ser válido', null, [], '', 'role', 'INVALID_ROLE'),
        ]);

        $this->validator->method('validate')->willReturn($violations);

        $result = $this->service->register($dto);

        $this->assertFalse($result['success']);
        $this->assertContains('El rol debe ser válido', $result['errors']);
    }

    public function testRegistrationWithMultipleValidationErrors(): void
    {
        $data = [
            'email' => 'invalid',
            'password' => '123',
            'firstName' => '',
            'lastName' => '',
            'role' => 'INVALID_ROLE'
        ];

        $dto = new RegisterUserDTO($data);

        // Mock multiple validation errors
        $violations = new ConstraintViolationList([
            new ConstraintViolation('El formato del email no es válido', null, [], '', 'email', 'invalid'),
            new ConstraintViolation('La contraseña debe tener al menos 6 caracteres', null, [], '', 'password', '123'),
            new ConstraintViolation('El nombre es obligatorio', null, [], '', 'firstName', ''),
            new ConstraintViolation('El apellido es obligatorio', null, [], '', 'lastName', ''),
            new ConstraintViolation('El rol debe ser válido', null, [], '', 'role', 'INVALID_ROLE'),
        ]);

        $this->validator->method('validate')->willReturn($violations);

        $result = $this->service->register($dto);

        $this->assertFalse($result['success']);
        $this->assertCount(5, $result['errors']);
    }
}
