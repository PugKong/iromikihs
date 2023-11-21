<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Anime;
use App\Entity\User;
use App\Exception\AnimeHasNoSeriesException;
use App\Exception\UserAnimeSeriesIsNotRatedException;
use App\Exception\UserCantObserveAnimeException;
use App\Exception\UserCantSkipAnimeException;
use App\Exception\UserHasSyncInProgressException;
use App\Repository\AnimeRateRepository;
use App\Repository\SeriesRateRepository;
use App\Service\Anime\GetUserSeriesList\GetUserSeriesList;
use App\Service\Anime\Observe;
use App\Service\Anime\Skip;
use App\Twig\Component\SimpleForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\UX\Turbo\TurboBundle;

#[IsGranted('ROLE_USER')]
final class AnimeController extends Controller
{
    #[Route('/', name: 'app_anime_index')]
    public function index(#[CurrentUser] User $user, AnimeRateRepository $rates, Stopwatch $stopwatch): Response
    {
        $stopwatch->start($watchName = 'user rates load');
        $userRatedAnimes = $rates->findUserRatedAnime($user);
        $stopwatch->stop($watchName);

        return $this->render('anime/index.html.twig', ['userRatedAnimes' => $userRatedAnimes]);
    }

    #[Route('/animes/{anime}/skip', name: 'app_anime_skip', methods: [Request::METHOD_POST])]
    public function skip(
        #[CurrentUser]
        User $user,
        Anime $anime,
        Request $request,
        SeriesRateRepository $seriesRates,
        Skip $skip,
        GetUserSeriesList $getUserSeriesList,
    ): Response {
        $this->validateCsrfToken($request);

        try {
            $series = $anime->getSeriesOrFail();
            $seriesRate = $seriesRates->findOneBy(['user' => $user, 'series' => $series]);
            if (null === $seriesRate) {
                throw UserAnimeSeriesIsNotRatedException::create($user, $series);
            }

            $prevSeriesState = $seriesRate->getState();
            ($skip)($user, $seriesRate, $anime);

            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                $seriesResults = ($getUserSeriesList)($user, $prevSeriesState, $series);

                return $this->render('series/series.stream.html.twig', [
                    'referer' => $this->getRefererPath(),
                    'series' => $series,
                    'seriesResult' => $seriesResults[0] ?? null,
                ]);
            }
        } catch (UserHasSyncInProgressException) {
            $this->addFlash('error', 'Can not skip anime while syncing data');
        } catch (UserCantSkipAnimeException) {
            $this->addFlash('error', 'Can not skip rated anime');
        } catch (AnimeHasNoSeriesException) {
            $this->addFlash('error', 'Oh no, anime has no series');
        } catch (UserAnimeSeriesIsNotRatedException) {
            $this->addFlash('error', 'Can not skip anime in not rated series');
        }

        return $this->redirect($request->headers->get('referer') ?? '/');
    }

    #[Route('/animes/{anime}/observe', name: 'app_anime_observe', methods: [Request::METHOD_POST])]
    public function observe(
        #[CurrentUser]
        User $user,
        Anime $anime,
        Request $request,
        Observe $observe,
        SeriesRateRepository $seriesRates,
        GetUserSeriesList $getUserSeriesList,
    ): Response {
        $this->validateCsrfToken($request);

        try {
            $series = $anime->getSeriesOrFail();
            $seriesRate = $seriesRates->findOneBy(['user' => $user, 'series' => $series]);
            if (null === $seriesRate) {
                throw UserAnimeSeriesIsNotRatedException::create($user, $series);
            }

            $prevSeriesState = $seriesRate->getState();
            ($observe)($user, $seriesRate, $anime);

            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                $seriesResults = ($getUserSeriesList)($user, $prevSeriesState, $series);

                return $this->render('series/series.stream.html.twig', [
                    'referer' => $this->getRefererPath(),
                    'series' => $series,
                    'seriesResult' => $seriesResults[0] ?? null,
                ]);
            }
        } catch (UserHasSyncInProgressException) {
            $this->addFlash('error', 'Can not observe anime while syncing data');
        } catch (UserCantObserveAnimeException) {
            $this->addFlash('error', 'Anime was not skipped');
        } catch (AnimeHasNoSeriesException) {
            $this->addFlash('error', 'Oh no, anime has no series');
        } catch (UserAnimeSeriesIsNotRatedException) {
            $this->addFlash('error', 'Can not observe anime in not rated series');
        }

        return $this->redirect($request->headers->get('referer') ?? '/');
    }

    private function validateCsrfToken(Request $request): void
    {
        $csrfToken = (string) $request->request->get(SimpleForm::CSRF_TOKEN_FIELD);
        if (!$this->isCsrfTokenValid(SimpleForm::CSRF_TOKEN_ID, $csrfToken)) {
            throw new UnprocessableEntityHttpException('Invalid csrf token');
        }
    }
}
