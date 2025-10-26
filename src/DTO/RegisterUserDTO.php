<?php

// src/DTO/RegisterUserDTO.php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserDTO
{
    // PROPIEDADES PÚBLICAS (en DTOs es aceptable)
    #[Assert\NotBlank(message: "El email es obligatorio")]
    #[Assert\Email(message: "El formato del email no es válido")]
    public string $email;

    #[Assert\NotBlank(message: "La contraseña es obligatoria")]
    #[Assert\Length(
        min: 6,
        minMessage: "La contraseña debe tener al menos {{ limit }} caracteres"
    )]
    public string $password;

    #[Assert\NotBlank(message: "El nombre es obligatorio")]
    #[Assert\Length(min: 2, max: 100)]
    public string $firstName;

    #[Assert\NotBlank(message: "El apellido es obligatorio")]
    #[Assert\Length(min: 2, max: 100)]
    public string $lastName;

    #[Assert\Choice(
        choices: ['ROLE_USER', 'ROLE_INVENTORY_MANAGER', 'ROLE_MANAGER', 'ROLE_ADMIN'],
        message: "El rol debe ser válido"
    )]
    public string $role = 'ROLE_USER'; // Valor por defecto

    // CONSTRUCTOR - Transforma array en objeto
    public function __construct(array $data)
    {
        $this->email = $data['email'] ?? '';
        $this->password = $data['password'] ?? '';
        $this->firstName = $data['firstName'] ?? '';
        $this->lastName = $data['lastName'] ?? '';
        $this->role = $data['role'] ?? 'ROLE_USER';
    }
}
