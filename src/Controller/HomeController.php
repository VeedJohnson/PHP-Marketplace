<?php

namespace App\Controller;

use App\Entity\Advertisement;
use App\Entity\Category;
use App\Repository\AdvertisementRepository;
use App\Service\LocationProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        EntityManagerInterface $entityManager,
        Request $request,
        LocationProvider $locationProvider,
    ): Response {
        $categoryRepository = $entityManager->getRepository(Category::class);
        /** @var AdvertisementRepository $advertisementRepository */
        $advertisementRepository = $entityManager->getRepository(Advertisement::class);

        $page = $request->query->getInt('page', 1);
        $searchQuery = $request->query->get('q');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');
        $selectedCounty = $request->query->get('county');
        $selectedCity = $request->query->get('city');
        $limit = 10;

        $minPrice = empty($minPrice)  || $minPrice <= 0 ? null : (float)$minPrice;
        $maxPrice = empty($maxPrice)  || $maxPrice <= 0 ? null : (float)$maxPrice;

        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            $this->addFlash('error', 'Minimum price cannot be greater than maximum price');
            return $this->redirectToRoute('app_home', array_merge(
                $request->query->all(),
                ['min_price' => null, 'max_price' => null]
            ));
        }

        $categoryId = $request->query->get('category');
        $selectedCategory = null;

        if ($categoryId) {
            $selectedCategory = $categoryRepository->find($categoryId);
            if (!$selectedCategory) {
                return $this->redirectToRoute('app_home', array_merge(
                    $request->query->all(),
                    ['page' => 1, 'category' => null]
                ));
            }
        }

        // Get filtered advertisements
        $advertisements = $advertisementRepository->findByFilters(
            $selectedCategory,
            $searchQuery,
            $selectedCity,
            $selectedCounty,
            $minPrice,
            $maxPrice,
            $page,
            $limit
        );

        $totalActiveAds = $advertisementRepository->count(['status' => 'active']);
        $totalAds = $advertisementRepository->countByFilters($selectedCategory, $searchQuery, $selectedCity,
            $selectedCounty, $minPrice, $maxPrice);
        $totalPages = max(1, ceil($totalAds / $limit));

        // Redirect if page is out of bounds
        if ($page > $totalPages) {
            return $this->redirectToRoute('app_home', array_merge(
                $request->query->all(),
                ['page' => 1]
            ));
        }

        // Get all categories with their counts
        $categories = $categoryRepository->findAll();
        $categories = array_filter($categories, function (Category $category) {
            return $category->getAdvertisements()->filter(fn($ad) => $ad->getStatus() === 'active')->count() > 0;
        });

        return $this->render('home/index.html.twig', [
            'categories' => $categories,
            'advertisements' => $advertisements,
            'selectedCategory' => $selectedCategory,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalAds' => $totalAds,
            'totalActiveAds' => $totalActiveAds,
            'searchQuery' => $searchQuery,
            'counties' => $locationProvider->getAllLocations(),
            'selectedCounty' => $selectedCounty,
            'selectedCity' => $selectedCity,
            'minPrice' => $minPrice ?? null,
            'maxPrice' => $maxPrice ?? null,
        ]);
    }
}