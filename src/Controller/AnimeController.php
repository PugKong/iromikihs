<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\AnimeRateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class AnimeController extends AbstractController
{
    #[Route('/', name: 'app_anime_index')]
    public function index(#[CurrentUser] User $user, AnimeRateRepository $rates): Response
    {
        $userRates = $rates->findByUserWithAnime($user);

        return $this->render('anime/index.html.twig', ['userRates' => $userRates]);
    }
}
