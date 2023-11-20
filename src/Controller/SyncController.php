<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\UserHasNoLinkedAccountException;
use App\Exception\UserHasSyncInProgressException;
use App\Service\Sync\DispatchUserDataSync;
use App\Twig\Component\SimpleForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class SyncController extends AbstractController
{
    #[Route('/sync', name: 'app_sync_start', methods: [Request::METHOD_POST])]
    public function start(
        #[CurrentUser]
        User $user,
        Request $request,
        DispatchUserDataSync $dispatchDataSync,
    ): Response {
        $csrfToken = (string) $request->request->get(SimpleForm::CSRF_TOKEN_FIELD);
        if (!$this->isCsrfTokenValid(SimpleForm::CSRF_TOKEN_ID, $csrfToken)) {
            throw new UnprocessableEntityHttpException('Invalid csrf token');
        }

        try {
            ($dispatchDataSync)($user);
        } catch (UserHasNoLinkedAccountException) {
            $this->addFlash('error', 'Link account to start syncing.');
        } catch (UserHasSyncInProgressException) {
            $this->addFlash('error', 'Sync is already in process.');
        }

        return $this->redirect($request->headers->get('referer') ?? '/');
    }
}
