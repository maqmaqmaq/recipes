<?php

namespace App\Controller;

use App\Entity\Recipe;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Comment;
use App\Form\CommentType;

class RecipeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/recipes', name: 'recipe_list')]
    public function index(EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
    {
        $search = $request->query->get('search', '');

        $queryBuilder = $entityManager->getRepository(Recipe::class)
            ->createSearchQueryBuilder($search);


        $pagination = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), 10);

        return $this->render('recipe/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search
        ]);
    }



    #[Route('/recipes/{id}', name: 'recipe_detail')]
    public function show(Recipe $recipe, Request $request, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setRecipe($recipe);
            $comment->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('recipe_detail', ['id' => $recipe->getId()]);
        }

        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipe,
            'comments' => $recipe->getComments()->slice(-20), // Pobierz 20 najnowszych
            'form' => $form->createView(),
        ]);
    }

    #[Route('/favorites', name: 'recipe_favorites')]
    public function favorites(EntityManagerInterface $entityManager, Request $request): Response
    {
        $favorites = json_decode($request->query->get('ids', '[]'), true);
        $recipes = $entityManager->getRepository(Recipe::class)->findBy(['id' => $favorites]);

        return $this->render('recipe/favorites.html.twig', [
            'recipes' => $recipes,
        ]);
    }
}
