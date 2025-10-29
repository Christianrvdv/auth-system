<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Nombre',
                'constraints' => [
                    new NotBlank(['message' => 'Por favor ingresa tu nombre']),
                    new Length(['min' => 2, 'max' => 100]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Apellido',
                'constraints' => [
                    new NotBlank(['message' => 'Por favor ingresa tu apellido']),
                    new Length(['min' => 2, 'max' => 100]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(['message' => 'Por favor ingresa tu email']),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Las contraseñas deben coincidir.',
                'first_options'  => ['label' => 'Contraseña'],
                'second_options' => ['label' => 'Repetir Contraseña'],
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Por favor ingresa una contraseña']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Tu contraseña debe tener al menos {{ limit }} caracteres',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'Acepto los términos y condiciones',
                'mapped' => false,
                'constraints' => [
                    new IsTrue(['message' => 'Debes aceptar los términos y condiciones.']),
                ],
            ])
        ;


        if ($options['is_admin']) {
            $builder->add('roles', ChoiceType::class, [
                'label' => 'Rol',
                'choices' => [
                    'Usuario' => 'ROLE_USER',
                    'Gestor de Inventario' => 'ROLE_INVENTORY_MANAGER',
                    'Manager' => 'ROLE_MANAGER',
                    'Administrador' => 'ROLE_ADMIN',
                ],
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'placeholder' => 'Selecciona un rol',
                'mapped' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_admin' => false,
        ]);
    }
}
