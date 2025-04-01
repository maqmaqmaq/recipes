<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Recipe;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecipeController extends AbstractController
{
    private const DEFAULT_PAGE_LIMIT = 10;

    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/recipes', name: 'recipe_list')]
    public function index(
        RecipeRepository $recipeRepository,
        PaginatorInterface $paginator,
        Request $request,
        ParameterBagInterface $params,
    ): Response {
        $search = $request->query->get('search', '');
        $limit = $params->get('app.recipes_per_page') ?? self::DEFAULT_PAGE_LIMIT;

        $query = $recipeRepository->findByTitleLikeQuery($search);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $limit
        );

        return $this->render('recipe/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
            'limit' => $limit,
        ]);
    }

    #[Route('/recipes/{id}', name: 'recipe_detail', requirements: ['id' => '\d+'])] // Add requirement for ID to be integer
    public function show(
        Recipe $recipe,
        Request $request,
        EntityManagerInterface $entityManager,
        CommentRepository $commentRepository,
        ParameterBagInterface $params,
    ): Response {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setRecipe($recipe);
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Comment added successfully!');

            return $this->redirectToRoute('recipe_detail', ['id' => $recipe->getId()]);
        }

        $commentLimit = $params->get('app.comments_per_page') ?? 20;

        $comments = $commentRepository->findLatestByRecipe($recipe, $commentLimit);

        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipe,
            'comments' => $comments,
            'commentForm' => $form->createView(),
        ]);
    }

    #[Route('/favorites', name: 'recipe_favorites')]
    public function favorites(Request $request, RecipeRepository $recipeRepository): Response // Inject repository
    {
        $favoritesIds = json_decode($request->query->get('ids', '[]'), true);

        $favoritesIds = array_filter(array_map('intval', $favoritesIds), fn ($id) => $id > 0);

        $recipes = $recipeRepository->findBy(['id' => $favoritesIds]);

        return $this->render('recipe/favorites.html.twig', [
            'recipes' => $recipes,
        ]);
    }
}
