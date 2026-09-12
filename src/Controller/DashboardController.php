<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/** @psalm-api The routes are discovered by Symfony's Route attribute. */
final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'], priority: 10)]
    public function dashboard(): Response
    {
        return $this->render('dashboard/index.html.twig');
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'], priority: 10)]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'], priority: 10)]
    public function logout(): never
    {
        throw new \LogicException('This method is intercepted by the logout firewall.');
    }
}
