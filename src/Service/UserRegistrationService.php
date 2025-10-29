<?php

namespace App\Service;

use App\DTO\RegisterUserDTO;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserRegistrationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {}

    public function register(RegisterUserDTO $data): array
    {
        // PASO 1: VALIDAR EL DTO
        $errors = $this->validator->validate($data);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return [
                'success' => false,
                'errors' => $errorMessages
            ];
        }

        $existingUser = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $data->email]);

        if ($existingUser) {
            return [
                'success' => false,
                'errors' => ['El usuario con este email ya existe']
            ];
        }

        $user = new User();
        $user->setEmail($data->email);
        $user->setFirstName($data->firstName);
        $user->setLastName($data->lastName);
        $user->setRoles([$data->role]);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data->password);
        $user->setPassword($hashedPassword);

        $user->setUpdatedAt(new \DateTime());

        $this->em->persist($user);
        $this->em->flush();


        return [
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles()
            ]
        ];
    }
}
