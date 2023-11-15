<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\Exception\UserHasLinkedAccountException;
use App\Service\Exception\UserHasSyncInProgressException;
use App\Service\Sync\DispatchLinkAccount;
use App\Shikimori\Client\Config;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    private const LINK_ACCOUNT_REDIRECT = 'link_account_redirect';

    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        return $this->render('profile/profile.html.twig');
    }

    #[Route('/profile/link/start', name: 'app_profile_link_start')]
    public function linkAccountStart(Request $request, SessionInterface $session, Config $config): Response
    {
        $session->set(self::LINK_ACCOUNT_REDIRECT, $request->headers->get('referer'));

        return $this->redirect($config->authUrl());
    }

    #[Route('/profile/link', name: 'app_profile_link')]
    public function linkAccount(
        #[CurrentUser]
        User $user,
        #[MapQueryParameter]
        string $code,
        DispatchLinkAccount $linkAccount,
        SessionInterface $session,
    ): Response {
        try {
            ($linkAccount)($user, $code);
        } catch (UserHasLinkedAccountException) {
            $this->addFlash('error', 'Account already linked.');
        } catch (UserHasSyncInProgressException) {
            $this->addFlash('error', 'Can not link account due to active sync.');
        }

        /** @var string|null $redirectUrl */
        $redirectUrl = $session->get(self::LINK_ACCOUNT_REDIRECT);

        return $this->redirect($redirectUrl ?? '/');
    }
}
