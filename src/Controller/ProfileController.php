<?php

namespace App\Controller;

use App\Entity\Advertisement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('/{id}', name: 'app_profile', defaults: ['id' => null])]
    public function index(?int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        if ($id === null) {
            $user = $this->getUser();
        } else {
            // Only admin can view other profiles
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            $user = $entityManager->getRepository(User::class)->find($id);

            if (!$user) {
                throw $this->createNotFoundException('User not found');
            }
        }

        $page = $request->query->getInt('page', 1);
        $limit = 5;

        $advertisementRepository = $entityManager->getRepository(Advertisement::class);
        $totalAds = $advertisementRepository->count(['user' => $user]);
        $totalPages = max(1, ceil($totalAds / $limit));

        if ($totalAds > 0 && $page > $totalPages) {
            return $this->redirectToRoute('app_profile', ['page' => 1]);
        }

        $advertisements = $advertisementRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            $limit,
            ($page - 1) * $limit
        );



        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'advertisements' => $advertisements,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'isOwnProfile' => $user === $this->getUser()
        ]);
    }
}