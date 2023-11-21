<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\UserHasNoLinkedAccountException;
use App\Exception\UserHasSyncInProgressException;
use App\Service\Sync\DispatchUserDataSync;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class SyncController extends Controller
{
    #[Route('/sync', name: 'app_sync_start', methods: [Request::METHOD_POST])]
    public function start(
        #[CurrentUser]
        User $user,
        DispatchUserDataSync $dispatchDataSync,
    ): Response {
        $this->checkSimpleCsrfToken();

        try {
            ($dispatchDataSync)($user);
        } catch (UserHasNoLinkedAccountException) {
            $this->addFlashError('Link account to start syncing.');
        } catch (UserHasSyncInProgressException) {
            $this->addFlashError('Sync is already in process.');
        }

        return $this->redirect($this->getRefererPath('/'));
    }
}
