<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\UserRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserRegistrationService $registrationService,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();

        // Determina si el usuario actual es admin
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // Pasa la opción is_admin al formulario
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'is_admin' => $isAdmin
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Obtiene el rol seleccionado o usa ROLE_USER por defecto
            $role = 'ROLE_USER';
            if ($isAdmin && $form->has('roles')) {
                $role = $form->get('roles')->getData() ?? 'ROLE_USER';
            }

            // Convertir form data a DTO
            $data = [
                'email' => $form->get('email')->getData(),
                'password' => $form->get('plainPassword')->getData(),
                'firstName' => $form->get('firstName')->getData(),
                'lastName' => $form->get('lastName')->getData(),
                'role' => $role,
            ];

            $result = $registrationService->register(new \App\DTO\RegisterUserDTO($data));

            if ($result['success']) {
                $this->addFlash('success', 'Usuario registrado exitosamente!');

                if ($this->isGranted('ROLE_ADMIN')) {
                    return $this->redirectToRoute('app_users');
                }

                return $this->redirectToRoute('app_login');
            } else {
                foreach ($result['errors'] as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
