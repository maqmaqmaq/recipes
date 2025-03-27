<?php

namespace App\Service;

use App\Entity\Recipe;
use App\Factory\RecipeFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecipeSyncService
{
    private string $URL = 'https://www.themealdb.com/api/json/v1/1/search.php?s=';

    public function __construct(
        private readonly HttpClientInterface    $httpClient,
        private readonly EntityManagerInterface $entityManager,
        )
    {
    }

    public function sync(): void
    {
        $response = $this->httpClient->request('GET', $this->URL);
        $data = $response->toArray();

        if (!isset($data['meals'])) {
            return;
        }

        foreach ($data['meals'] as $meal) {
             $existingRecipe = $this->entityManager
                 ->getRepository(Recipe::class)
                 ->findOneBy(['title' => $meal['strMeal']]);

             if (!$existingRecipe) {
                 $recipe = RecipeFactory::createFromApiData($meal);
                 $this->entityManager->persist($recipe);
             }
        }

        $this->entityManager->flush();
    }
}
