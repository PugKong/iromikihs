<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Message\LinkAccount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        return $this->render('profile/profile.html.twig');
    }

    #[Route('/profile/link', name: 'app_profile_link')]
    public function linkAccount(
        #[CurrentUser]
        User $user,
        #[MapQueryParameter]
        string $code,
        MessageBusInterface $bus,
    ): Response {
        $bus->dispatch(new LinkAccount($user->getId(), $code));
        $this->addFlash('notice', 'Your account will be linked soon.');

        return $this->redirectToRoute('app_profile');
    }
}
