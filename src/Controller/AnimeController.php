<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Message\SyncList;
use App\Repository\AnimeRateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class AnimeController extends AbstractController
{
    #[Route('/', name: 'app_anime_index')]
    public function index(#[CurrentUser] User $user, AnimeRateRepository $rates): Response
    {
        $syncForm = $this->syncForm();
        $userRates = $rates->findByUserWithAnime($user);

        return $this->render('anime/index.html.twig', ['syncForm' => $syncForm, 'userRates' => $userRates]);
    }

    #[Route('/sync', name: 'app_anime_sync')]
    public function sync(#[CurrentUser] User $user, Request $request, MessageBusInterface $bus): Response
    {
        $form = $this->syncForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $bus->dispatch(new SyncList($user->getId()));
            $this->addFlash('notice', 'Your list will be synced soon.');

            return $this->redirectToRoute('app_anime_index');
        }

        throw new BadRequestHttpException('Oh no, you did something wrong');
    }

    private function syncForm(): FormInterface
    {
        return $this
            ->createFormBuilder()
            ->setAction($this->generateUrl('app_anime_sync'))
            ->add('submit', SubmitType::class, ['label' => 'Sync list', 'attr' => ['class' => 'btn btn-primary']])
            ->getForm()
        ;
    }
}
