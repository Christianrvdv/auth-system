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
    #[Route('/register', name: 'app_register', methods: ['GET'])]
    public function showRegister(): Response
    {
        // Si el usuario ya está autenticado y no es admin, redirigir a home
        if ($this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }

        // Redirigir a la página de login con el parámetro para mostrar registro
        return $this->redirectToRoute('app_login', ['show' => 'register']);
    }

    #[Route('/register', name: 'app_register_post', methods: ['POST'])]
    public function register(
        Request $request,
        UserRegistrationService $registrationService
    ): Response {
        // Si el usuario ya está autenticado y no es admin, redirigir a home
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
            // PARA USUARIOS NORMALES: Siempre usar ROLE_USER
            // Solo los administradores pueden asignar otros roles
            $role = 'ROLE_USER';

            // Convertir form data a DTO
            $data = [
                'email' => $form->get('email')->getData(),
                'password' => $form->get('plainPassword')->getData(),
                'firstName' => $form->get('firstName')->getData(),
                'lastName' => $form->get('lastName')->getData(),
                'role' => $role, // Siempre ROLE_USER para usuarios normales
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

        // Si hay errores en el formulario, mostramos la página de auth con el formulario de registro
        return $this->render('security/auth.html.twig', [
            'registrationForm' => $form->createView(),
            'is_register_page' => true,
            'last_username' => '',
            'error' => null
        ]);
    }
}
