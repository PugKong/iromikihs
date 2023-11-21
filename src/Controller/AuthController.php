<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AuthController extends Controller
{
    #[Route('/login', name: 'app_login')]
    public function login(#[CurrentUser] ?User $user, AuthenticationUtils $utils): Response
    {
        if (null !== $user) {
            return $this->redirect('/');
        }

        $lastUsername = $utils->getLastUsername();
        $error = $utils->getLastAuthenticationError();

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }
}
