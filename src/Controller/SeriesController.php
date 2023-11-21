<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SeriesRate;
use App\Entity\SeriesState;
use App\Entity\User;
use App\Exception\UserHasSyncInProgressException;
use App\Service\Anime\GetUserSeriesList\GetUserSeriesList;
use App\Service\Series\Drop;
use App\Service\Series\Restore;
use App\Twig\Component\SimpleForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\UX\Turbo\TurboBundle;

#[IsGranted('ROLE_USER')]
final class SeriesController extends Controller
{
    #[Route('/series/incomplete', name: 'app_series_incomplete')]
    public function incomplete(#[CurrentUser] User $user, GetUserSeriesList $getUserSeriesList, Stopwatch $stopwatch): Response
    {
        $stopwatch->start($watchName = 'series list load');
        $series = ($getUserSeriesList)($user, SeriesState::INCOMPLETE);
        $stopwatch->stop($watchName);

        return $this->render('series/incomplete.html.twig', ['series' => $series]);
    }

    #[Route('/series/complete', name: 'app_series_complete')]
    public function complete(#[CurrentUser] User $user, GetUserSeriesList $getUserSeriesList, Stopwatch $stopwatch): Response
    {
        $stopwatch->start($watchName = 'series list load');
        $series = ($getUserSeriesList)($user, SeriesState::COMPLETE);
        $stopwatch->stop($watchName);

        return $this->render('series/complete.html.twig', ['series' => $series]);
    }

    #[Route('/series/dropped', name: 'app_series_dropped')]
    public function dropped(#[CurrentUser] User $user, GetUserSeriesList $getUserSeriesList, Stopwatch $stopwatch): Response
    {
        $stopwatch->start($watchName = 'series list load');
        $series = ($getUserSeriesList)($user, SeriesState::DROPPED);
        $stopwatch->stop($watchName);

        return $this->render('series/dropped.html.twig', ['series' => $series]);
    }

    #[Route('/series/rates/{seriesRate}/drop', name: 'app_series_drop', methods: [Request::METHOD_POST])]
    public function drop(
        #[CurrentUser]
        User $user,
        SeriesRate $seriesRate,
        Request $request,
        Drop $drop,
    ): Response {
        $this->validateSeriesManipulationRequest($user, $seriesRate, $request);

        try {
            ($drop)($user, $seriesRate);

            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->render('series/series.stream.html.twig', [
                    'referer' => $this->getRefererPath(),
                    'series' => $seriesRate->getSeries(),
                ]);
            }
        } catch (UserHasSyncInProgressException) {
            $this->addFlash('error', 'Can not drop series while syncing data');
        }

        return $this->redirect($request->headers->get('referer') ?? '/');
    }

    #[Route('/series/rates/{seriesRate}/restore', name: 'app_series_restore', methods: [Request::METHOD_POST])]
    public function restore(
        #[CurrentUser]
        User $user,
        SeriesRate $seriesRate,
        Request $request,
        Restore $restore,
    ): Response {
        $this->validateSeriesManipulationRequest($user, $seriesRate, $request);

        try {
            ($restore)($user, $seriesRate);

            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->render('series/series.stream.html.twig', [
                    'referer' => $this->getRefererPath(),
                    'series' => $seriesRate->getSeries(),
                ]);
            }
        } catch (UserHasSyncInProgressException) {
            $this->addFlash('error', 'Can not restore series while syncing data');
        }

        return $this->redirect($request->headers->get('referer') ?? '/');
    }

    private function validateSeriesManipulationRequest(User $user, SeriesRate $seriesRate, Request $request): void
    {
        if (!$user->getId()->equals($seriesRate->getUser()->getId())) {
            throw new AccessDeniedHttpException('Oh no, you can not');
        }

        $csrfToken = (string) $request->request->get(SimpleForm::CSRF_TOKEN_FIELD);
        if (!$this->isCsrfTokenValid(SimpleForm::CSRF_TOKEN_ID, $csrfToken)) {
            throw new UnprocessableEntityHttpException('Invalid csrf token');
        }
    }
}
