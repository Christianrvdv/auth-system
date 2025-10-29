<?php

namespace App\Controller;

use App\DTO\RegisterUserDTO;
use App\Service\UserRegistrationService;
use App\Service\AuditLoggerService;
use App\Form\RegistrationFormType;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private AuditLoggerService $auditLogger
    ) {}

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserRegistrationService $registrationService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'message' => 'JSON inválido',
                'error' => json_last_error_msg()
            ], 400);
        }

        $registerDTO = new RegisterUserDTO($data);
        $result = $registrationService->register($registerDTO);

        if (!$result['success']) {
            $this->auditLogger->logSecurityEvent(
                'REGISTRATION_FAILED',
                null,
                ['email' => $data['email'] ?? 'unknown', 'errors' => $result['errors']]
            );

            return $this->json([
                'message' => 'Error en el registro',
                'errors' => $result['errors']
            ], 400);
        }

        $this->auditLogger->logSecurityEvent(
            'USER_REGISTERED',
            null,
            ['email' => $data['email'], 'role' => $data['role'] ?? 'ROLE_USER']
        );

        return $this->json([
            'message' => 'Usuario registrado exitosamente',
            'user' => $result['user']
        ], 201);
    }
    #[Route('/web/register', name: 'auth_web_register', methods: ['POST'])]
    public function registerWeb(
        Request $request,
        UserRegistrationService $registrationService
    ): Response {
        $user = new User();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $form = $this->createForm(RegistrationFormType::class, $user, [
            'is_admin' => $isAdmin
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role = 'ROLE_USER';

            if ($isAdmin && $form->has('roles')) {
                $selectedRole = $form->get('roles')->getData();
                if ($selectedRole) {
                    $role = $selectedRole;
                }
            }

            $data = [
                'email' => $form->get('email')->getData(),
                'password' => $form->get('plainPassword')->getData(),
                'firstName' => $form->get('firstName')->getData(),
                'lastName' => $form->get('lastName')->getData(),
                'role' => $role,
            ];

            $result = $registrationService->register(new RegisterUserDTO($data));

            if ($result['success']) {
                $this->addFlash('success', 'Usuario registrado exitosamente!');

                if ($isAdmin) {
                    return $this->redirectToRoute('app_users');
                }

                return $this->redirectToRoute('app_login');
            } else {
                foreach ($result['errors'] as $error) {
                    $this->addFlash('error', $error);
                }

                // Regresar a la página de auth con errores
                return $this->render('security/auth.html.twig', [
                    'registrationForm' => $form->createView(),
                    'is_register_page' => true,
                    'last_username' => '',
                    'error' => null
                ]);
            }
        }

        return $this->render('security/auth.html.twig', [
            'registrationForm' => $form->createView(),
            'is_register_page' => true,
            'last_username' => '',
            'error' => null
        ]);
    }

    #[Route('/me', name: 'auth_me', methods: ['GET'])]
    public function getCurrentUser(TokenStorageInterface $tokenStorage): JsonResponse
    {
        $user = $tokenStorage->getToken()?->getUser();

        if (!$user) {
            throw new AuthenticationException('Usuario no autenticado');
        }

        $this->auditLogger->logUserAction($user, 'PROFILE_VIEWED');

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles()
            ]
        ]);
    }

    #[Route('/health', name: 'auth_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json([
            'status' => 'OK',
            'message' => 'Auth system running',
            'timestamp' => time()
        ]);
    }

    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AuthenticationException('Credenciales inválidas');
        }

        $this->auditLogger->logSecurityEvent('LOGIN_SUCCESS', $user);

        return $this->json([
            'token' => $jwtManager->create($user),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ]
        ]);
    }
}
