<?php

namespace App\Repository;

use App\Entity\Advertisement;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Advertisement>
 */
class AdvertisementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Advertisement::class);
    }

    // src/Repository/AdvertisementRepository.php
    public function findByCategory(Category $category, int $page = 1, int $limit = 9): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.categories', 'c')
            ->where('c.id = :categoryId')
            ->andWhere('a.status = :status')
            ->setParameter('categoryId', $category->getId())
            ->setParameter('status', 'active')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit)
            ->getQuery()
            ->getResult();
    }

    public function countByCategory(Category $category): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->innerJoin('a.categories', 'c')
            ->where('c.id = :categoryId')
            ->andWhere('a.status = :status')
            ->setParameter('categoryId', $category->getId())
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByFilters(
        ?Category $category = null,
        ?string $searchQuery = null,
        ?string $selectedCity = null,
        ?string $selectedCounty = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        int $page = 1,
        int $limit = 9
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', 'active');

        if ($searchQuery) {
            $qb->andWhere('a.title LIKE :query')
                ->setParameter('query', '%' . $searchQuery . '%');
        }

        if ($category) {
            $qb->innerJoin('a.categories', 'c')
                ->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $category->getId());
        }

        if ($selectedCity) {
            $qb->andWhere('a.city = :city')
                ->andWhere('a.state = :state')
                ->setParameter('city', $selectedCity)
                ->setParameter('state', $selectedCounty);
        }

        if ($selectedCounty) {
            $qb->andWhere('a.state = :state')
                ->setParameter('state', $selectedCounty);
        }

        if ($minPrice !== null) {
            $qb->andWhere('a.price >= :minPrice')
                ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('a.price <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
        }

        return $qb->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit)
            ->getQuery()
            ->getResult();
    }

    public function countByFilters(?Category $category = null, ?string $searchQuery = null, ?string $selectedCity = null, ?string $selectedCounty = null, ?float $minPrice = null, ?float $maxPrice = null,): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.status = :status')
            ->setParameter('status', 'active');

        if ($searchQuery) {
            $qb->andWhere('a.title LIKE :query')
                ->setParameter('query', '%' . $searchQuery . '%');
        }

        if ($category) {
            $qb->innerJoin('a.categories', 'c')
                ->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $category->getId());
        }

        if ($selectedCity) {
            $qb->andWhere('a.city = :city')
                ->andWhere('a.state = :state')
                ->setParameter('city', $selectedCity)
                ->setParameter('state', $selectedCounty);
        }

        if ($selectedCounty) {
            $qb->andWhere('a.state = :state')
                ->setParameter('state', $selectedCounty);
        }

        if ($minPrice !== null) {
            $qb->andWhere('a.price >= :minPrice')
                ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('a.price <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
