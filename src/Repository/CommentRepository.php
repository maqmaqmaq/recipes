<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Finds the latest comments for a specific recipe.
     *
     * @param Recipe $recipe the recipe entity
     * @param int    $limit  the maximum number of comments to return
     *
     * @return Comment[] returns an array of Comment objects
     */
    public function findLatestByRecipe(Recipe $recipe, int $limit = 20): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.recipe = :recipe')
            ->setParameter('recipe', $recipe)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
