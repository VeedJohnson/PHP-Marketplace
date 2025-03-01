<?php

namespace App\Controller;

use App\Entity\Advertisement;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $userRepository = $entityManager->getRepository(User::class);
        $advertisementRepository = $entityManager->getRepository(Advertisement::class);
        $categoryRepository = $entityManager->getRepository(Category::class);

        // User statistics
        $totalUsers = $userRepository->count([]);
        $totalModerators = $userRepository->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', json_encode('ROLE_MODERATOR'))
            ->getQuery()
            ->getSingleScalarResult();

        $totalAdmins = $userRepository->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('JSON_CONTAINS(u.roles, :admin_role) = 1 OR JSON_CONTAINS(u.roles, :super_admin_role) = 1')
            ->setParameter('admin_role', json_encode('ROLE_ADMIN'))
            ->setParameter('super_admin_role', json_encode('ROLE_SUPER_ADMIN'))
            ->getQuery()
            ->getSingleScalarResult();

        // Advertisement statistics
        $totalAds = $advertisementRepository->count([]);
        $activeAds = $advertisementRepository->count(['status' => 'active']);

        // Category statistics
        $totalCategories = $categoryRepository->count([]);
        $categoriesWithAds = $categoryRepository->createQueryBuilder('c')
            ->select('c.name, count(a.id) as adCount')
            ->leftJoin('c.advertisements', 'a')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();

        return $this->render('admin/index.html.twig', [
            'totalUsers' => $totalUsers,
            'totalModerators' => $totalModerators,
            'totalAdmins' => $totalAdmins,
            'totalAds' => $totalAds,
            'activeAds' => $activeAds,
            'categoriesWithAds' => $categoriesWithAds,
            'totalCategories' => $totalCategories,
        ]);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function users(EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $search = $request->query->get('q');

        $userRepository = $entityManager->getRepository(User::class);

        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->addSelect(
                "CASE 
            WHEN JSON_CONTAINS(u.roles, :super_admin_role) = 1 THEN 0
            WHEN JSON_CONTAINS(u.roles, :admin_role) = 1 THEN 1
            WHEN JSON_CONTAINS(u.roles, :mod_role) = 1 THEN 2
            ELSE 3 
        END AS HIDDEN rank"
            )
            ->orderBy('rank', 'ASC')
            ->addOrderBy('u.createdAt', 'DESC')
            ->setParameter('super_admin_role', json_encode('ROLE_SUPER_ADMIN'))
            ->setParameter('admin_role', json_encode('ROLE_ADMIN'))
            ->setParameter('mod_role', json_encode('ROLE_MODERATOR'));


        if ($search) {
            $queryBuilder
                ->andWhere('u.email LIKE :search OR u.firstName LIKE :search OR u.lastName LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $totalUsers = count($queryBuilder->getQuery()->getResult());
        $totalPages = ceil($totalUsers / $limit);


        $users = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'searchQuery' => $search
        ]);

    }

    #[Route('/user/{id}/role', name: 'app_admin_user_role', methods: ['POST'])]
    public function updateUserRole(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->denyAccessUnlessGranted('promote_user', $user, 'You cannot modify this user\'s role.');

        $role = $request->request->get('role');
        $allowedRoles = ['ROLE_USER', 'ROLE_MODERATOR', 'ROLE_ADMIN'];

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $allowedRoles[] = 'ROLE_SUPER_ADMIN';
            if (in_array($role, $allowedRoles)) {
                $this->addFlash('error', 'Only one super admin can exist');
                return $this->redirectToRoute('app_admin_users');
            }
        }



        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Super admin roles cannot be modified.');
            return $this->redirectToRoute('app_admin_users');
        }


        if (!$this->isGranted('ROLE_SUPER_ADMIN') &&
            in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Regular admins cannot modify other admin roles.');
            return $this->redirectToRoute('app_admin_users');
        }

        if (in_array($role, $allowedRoles)) {
            if ($role === 'ROLE_ADMIN' && !$this->isGranted('ROLE_SUPER_ADMIN')) {
                $this->addFlash('error', 'Only super admins can assign admin roles.');
                return $this->redirectToRoute('app_admin_users');
            }

            try {
                $user->setRoles([$role]);
                $entityManager->flush();
                $this->addFlash('success', 'User role updated successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while updating the role.');
            }
        } else {
            $this->addFlash('error', 'Invalid role selected.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/user/{id}/ban', name: 'app_admin_user_ban', methods: ['POST'])]
    public function banUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->denyAccessUnlessGranted('ban_user', $user, 'You cannot ban this user.');

        if ($this->isCsrfTokenValid('ban'.$user->getId(), $request->request->get('_token'))) {
            // Check if trying to ban a super admin
            if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
                $this->addFlash('error', 'Super admin users cannot be banned.');
                return $this->redirectToRoute('app_admin_users');
            }

            if (!$this->isGranted('ROLE_SUPER_ADMIN') &&
                in_array('ROLE_ADMIN', $user->getRoles())) {
                $this->addFlash('error', 'Regular admins cannot ban other admins.');
                return $this->redirectToRoute('app_admin_users');
            }

            $duration = $request->request->get('duration');
            $reason = $request->request->get('reason');

            $bannedUntil = $duration === 'permanent'
                ? null
                : (new \DateTimeImmutable())->modify("+{$duration} days");

            $user->ban($reason, $bannedUntil);
            $entityManager->flush();

            $logger->info('User banned status', [
                'is_banned' => $user->isBanned(),
                'ban_reason' => $user->getBanReason(),
                'banned_by' => $this->getUser()->getId()
            ]);

            $this->addFlash('success', 'User has been banned.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/user/{id}/unban', name: 'app_admin_user_unban', methods: ['POST'])]
    public function unbanUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('unban'.$user->getId(), $request->request->get('_token'))) {
            $user->unban();
            $entityManager->flush();

            $this->addFlash('success', 'User has been unbanned.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/user/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(User $user, EntityManagerInterface $entityManager, Request $request, ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
                $this->addFlash('error', 'Super admin user cannot be deleted.');
                return $this->redirectToRoute('app_admin_users');
            }

            if (!$this->isGranted('ROLE_SUPER_ADMIN') &&
                in_array('ROLE_ADMIN', $user->getRoles())) {
                $this->addFlash('error', 'Regular admins cannot delete other admins.');
                return $this->redirectToRoute('app_admin_users');
            }

            $entityManager->remove($user);
            $entityManager->flush();


            $this->addFlash('success', 'User has been deleted.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }
}