<?php

namespace App\Factory;

use App\Entity\Recipe;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RecipeFactory
{
    public function __construct(
        private readonly ?LoggerInterface $logger = null,
        private readonly ?ValidatorInterface $validator = null,
    ) {
    }

    /**
     * Creates a Recipe entity from API data array.
     * Returns null if essential data is missing.
     *
     * @param array $mealData data from the external API for a single meal
     *
     * @return Recipe|null the created Recipe entity or null on validation failure
     */
    public function createFromApiData(array $mealData): ?Recipe
    {
        if (empty($mealData['strMeal']) || empty($mealData['strInstructions']) || empty($mealData['strCategory']) || empty($mealData['strMealThumb'])) {
            $this->logger?->warning('Missing essential data in meal payload, cannot create recipe.', ['meal_data' => $mealData]);

            return null;
        }

        $recipe = new Recipe();
        $recipe->setTitle(trim($mealData['strMeal']));
        $recipe->setInstructions(trim($mealData['strInstructions']));
        $recipe->setCategory(trim($mealData['strCategory']));
        $recipe->setTags(isset($mealData['strTags']) && '' !== trim($mealData['strTags']) ? trim($mealData['strTags']) : null);
        $recipe->setImageUrl(trim($mealData['strMealThumb']));

        return $recipe;
    }
}
