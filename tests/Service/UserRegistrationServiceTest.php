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

        $result = $this->service->register($dto);

        $this->assertTrue($result['success']);
        $this->assertEquals('test@example.com', $result['user']['email']);
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
}
