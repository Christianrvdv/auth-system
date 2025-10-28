<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/users', name: 'app_users')]
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepository->findAll();

        $stats = [
            'total_users' => count($users),
            'oauth_users' => $userRepository->count(['isOAuth' => true]),
            'regular_users' => $userRepository->count(['isOAuth' => false]),
            'admin_users' => array_reduce($users, function($count, $user) {
                return $count + (in_array('ROLE_ADMIN', $user->getRoles()) ? 1 : 0);
            }, 0)
        ];

        return $this->render('user/index.html.twig', [
            'users' => $users,
            ...$stats
        ]);
    }

    #[Route('/users/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(User $user, EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->getUser()->getId() === $user->getId()) {
            $this->addFlash('error', 'No puedes eliminar tu propio usuario.');
            return $this->redirectToRoute('app_users');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Usuario eliminado correctamente.');
        } else {
            $this->addFlash('error', 'Token CSRF inválido.');
        }

        return $this->redirectToRoute('app_users');
    }
}
