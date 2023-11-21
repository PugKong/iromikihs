<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Anime;
use App\Entity\AnimeRateStatus;
use App\Entity\User;
use App\Exception\AnimeHasNoSeriesException;
use App\Exception\UserAnimeSeriesIsNotRatedException;
use App\Exception\UserCantObserveAnimeException;
use App\Exception\UserCantSkipAnimeException;
use App\Exception\UserHasSyncInProgressException;
use App\Repository\AnimeRateRepository;
use App\Repository\SeriesRateRepository;
use App\Service\Anime\Observe;
use App\Service\Anime\Skip;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    ): Response {
        $this->checkSimpleCsrfToken();

        try {
            $series = $anime->getSeriesOrFail();
            $seriesRate = $seriesRates->findOneBy(['user' => $user, 'series' => $series]);
            if (null === $seriesRate) {
                throw UserAnimeSeriesIsNotRatedException::create($user, $series);
            }

            $prevSeriesState = $seriesRate->getState();
            ($skip)($user, $seriesRate, $anime);
            $seriesStateChanged = $prevSeriesState !== $seriesRate->getState();

            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                return $this->renderStream($anime, AnimeRateStatus::SKIPPED, $seriesStateChanged);
            }
        } catch (UserHasSyncInProgressException) {
            $this->addFlashError('Can not skip anime while syncing data');
        } catch (UserCantSkipAnimeException) {
            $this->addFlashError('Can not skip rated anime');
        } catch (AnimeHasNoSeriesException) {
            $this->addFlashError('Oh no, anime has no series');
        } catch (UserAnimeSeriesIsNotRatedException) {
            $this->addFlashError('Can not skip anime in not rated series');
        }

        return $this->redirect($this->getRefererPath('/'));
    }

    #[Route('/animes/{anime}/observe', name: 'app_anime_observe', methods: [Request::METHOD_POST])]
    public function observe(
        #[CurrentUser]
        User $user,
        Anime $anime,
        Request $request,
        SeriesRateRepository $seriesRates,
        Observe $observe,
    ): Response {
        $this->checkSimpleCsrfToken();

        try {
            $series = $anime->getSeriesOrFail();
            $seriesRate = $seriesRates->findOneBy(['user' => $user, 'series' => $series]);
            if (null === $seriesRate) {
                throw UserAnimeSeriesIsNotRatedException::create($user, $series);
            }

            $prevSeriesState = $seriesRate->getState();
            ($observe)($user, $seriesRate, $anime);
            $seriesStateChanged = $prevSeriesState !== $seriesRate->getState();

            if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
                return $this->renderStream($anime, null, $seriesStateChanged);
            }
        } catch (UserHasSyncInProgressException) {
            $this->addFlashError('Can not observe anime while syncing data');
        } catch (UserCantObserveAnimeException) {
            $this->addFlashError('Anime was not skipped');
        } catch (AnimeHasNoSeriesException) {
            $this->addFlashError('Oh no, anime has no series');
        } catch (UserAnimeSeriesIsNotRatedException) {
            $this->addFlashError('Can not observe anime in not rated series');
        }

        return $this->redirect($this->getRefererPath('/'));
    }

    private function renderStream(Anime $anime, ?AnimeRateStatus $status, bool $seriesStateChanged): Response
    {
        $this->container->get('request_stack')->getCurrentRequest()?->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return $this->render('anime/anime.stream.html.twig', [
            'referer' => $this->getRefererPath(),
            'anime' => $anime,
            'seriesStateChanged' => $seriesStateChanged,
            'status' => $status,
        ]);
    }
}
