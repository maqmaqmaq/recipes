<?php

namespace App\Repository;

use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 */
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }


    public function createSearchQueryBuilder($value): Query
    {
        $queryBuilder = $this->createQueryBuilder('r');

        if (!empty($search)) {
            $queryBuilder->where('r.title LIKE :search')
                ->setParameter('search', "%$value%");
        }

        return  $queryBuilder->getQuery();
    }
}
