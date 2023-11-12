<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\Sync\DispatchLinkAccount;
use App\Service\Sync\UserHasLinkedAccountException;
use App\Service\Sync\UserHasSyncInProgressException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
        DispatchLinkAccount $linkAccount,
    ): Response {
        try {
            ($linkAccount)($user, $code);
        } catch (UserHasLinkedAccountException) {
            $this->addFlash('error', 'Account already linked.');
        } catch (UserHasSyncInProgressException) {
            $this->addFlash('error', 'Can not link account due to active sync.');
        }

        return $this->redirectToRoute('app_anime_index');
    }
}
