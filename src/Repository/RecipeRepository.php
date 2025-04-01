<?php

namespace App\Repository;

use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 *
 * @method Recipe|null find($id, $lockMode = null, $lockVersion = null)
 * @method Recipe|null findOneBy(array $criteria, array $orderBy = null)
 * @method Recipe[]    findAll()
 * @method Recipe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    /**
     * Returns a Query object for recipes matching a title search term, suitable for pagination.
     *
     * @param string|null $searchTerm the term to search for in recipe titles
     */
    public function findByTitleLikeQuery(?string $searchTerm): Query
    {
        $queryBuilder = $this->createQueryBuilder('r')
                             ->orderBy('r.title', 'ASC'); // Add a default order

        if (!empty($searchTerm)) {
            $queryBuilder->where('LOWER(r.title) LIKE LOWER(:search)') // Case-insensitive search
                ->setParameter('search', '%'.trim($searchTerm).'%');
        }

        return $queryBuilder->getQuery();
    }

    /**
     * Finds recipes by a list of titles (e.g., for checking existence during sync).
     * Returns an array of Recipe entities indexed by their title.
     *
     * @return array<string, Recipe>
     */
    public function findByTitles(array $titles): array
    {
        if (empty($titles)) {
            return [];
        }

        $qb = $this->createQueryBuilder('r')
           ->where('r.title IN (:titles)')
           ->setParameter('titles', $titles);

        return $qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
    }

    /**
     * Finds the most recent recipes.
     *
     * @param int $limit maximum number of recipes to return
     *
     * @return Recipe[]
     */
    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
