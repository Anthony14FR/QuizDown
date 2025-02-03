<?php

namespace App\Repository;

use App\Entity\Quiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
    }

    public function findPaginatedFilteredQuizzes(
        ?string $category = null,
        ?string $tag = null,
        ?string $order = 'desc',
        ?string $searchTerm = '',
        int $page = 1,
        int $limit = 9,
    ) {
        $qb = $this->createQueryBuilder('q')
            ->leftJoin('q.categories', 'c')
            ->leftJoin('q.tags', 't')
            ->addSelect('c', 't');

        if (!empty($category)) {
            $qb->andWhere('c.name = :category')
               ->setParameter('category', $category);
        }

        if (!empty($tag)) {
            $qb->andWhere('t.name = :tag')
               ->setParameter('tag', $tag);
        }

        if (!empty($searchTerm)) {
            $qb->andWhere('q.title LIKE :searchTerm OR q.description LIKE :searchTerm')
               ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }

        $order = in_array(strtolower($order), ['asc', 'desc']) ? $order : 'desc';

        $qb->orderBy('q.createdAt', $order)
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countFilteredQuizzes(?string $category = null, ?string $tag = null, ?string $searchTerm = ''): int
    {
        $qb = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->leftJoin('q.categories', 'c')
            ->leftJoin('q.tags', 't');

        if (!empty($category)) {
            $qb->andWhere('c.name = :category')
               ->setParameter('category', $category);
        }

        if (!empty($tag)) {
            $qb->andWhere('t.name = :tag')
               ->setParameter('tag', $tag);
        }

        if (!empty($searchTerm)) {
            $qb->andWhere('q.title LIKE :searchTerm OR q.description LIKE :searchTerm')
               ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
