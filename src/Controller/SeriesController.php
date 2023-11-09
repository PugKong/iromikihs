<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class SeriesController extends AbstractController
{
    #[Route('/series/incomplete', name: 'app_series_incomplete')]
    public function incomplete(): Response
    {
        return $this->render('series/incomplete.html.twig');
    }

    #[Route('/series/complete', name: 'app_series_complete')]
    public function complete(): Response
    {
        return $this->render('series/complete.html.twig');
    }
}
