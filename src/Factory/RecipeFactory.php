<?php

namespace App\Factory;

use App\Entity\Recipe;

final class RecipeFactory
{
    public static function createFromApiData(array $mealData)
    {
        $recipe = new Recipe();
        $recipe->setTitle($mealData['strMeal']);
        $recipe->setInstructions($mealData['strInstructions']);
        $recipe->setCategory($mealData['strCategory']);
        $recipe->setTags($mealData['strTags'] ?? null);
        $recipe->setImageUrl($mealData['strMealThumb']);
        $recipe->setCreatedAt(new \DateTimeImmutable());

        return $recipe;
    }
}