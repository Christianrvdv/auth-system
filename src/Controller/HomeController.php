<?php
// src/Controller/HomeController.php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $totalUsers = $entityManager->getRepository(User::class)->count([]);
        $oauthUsers = $entityManager->getRepository(User::class)->count(['isOAuth' => true]);

        return $this->render('home/index.html.twig', [
            'total_users' => $totalUsers,
            'oauth_users' => $oauthUsers,
        ]);
    }
}
