<?php

namespace App\Service;

use App\Entity\Recipe;
use App\Factory\RecipeFactory;
use App\Repository\RecipeRepository;
use App\Service\ServiceException\RecipeSyncException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecipeSyncService
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly RecipeRepository $recipeRepository,
        private readonly RecipeFactory $recipeFactory,
        private readonly LoggerInterface $logger,
        private readonly string $apiUrl,
    ) {
    }

    /**
     * Synchronizes recipes from the external API.
     *
     * @return array{'added': int, 'skipped': int} counts of added/skipped recipes
     *
     * @throws RecipeSyncException|RedirectionExceptionInterface if the synchronization fails due to API or DB issues
     */
    public function sync(): array
    {
        $this->logger->info('Starting recipe synchronization from API: '.$this->apiUrl);
        $addedCount = 0;
        $skippedCount = 0;
        $batchCounter = 0;

        try {
            $response = $this->httpClient->request('GET', $this->apiUrl);

            if (200 !== $response->getStatusCode()) {
                throw new RecipeSyncException(sprintf('API request failed with status code %d.', $response->getStatusCode()));
            }

            $data = $response->toArray();

            if (!isset($data['meals']) || !is_array($data['meals'])) {
                $this->logger->warning('API response missing "meals" array or it is not an array.');

                return ['added' => 0, 'skipped' => 0];
            }

            if (empty($data['meals'])) {
                $this->logger->info('No meals found in the API response.');

                return ['added' => 0, 'skipped' => 0];
            }

            $apiTitles = array_map(fn ($meal) => trim($meal['strMeal'] ?? ''), $data['meals']);
            $apiTitles = array_filter($apiTitles); // Remove empty titles

            if (empty($apiTitles)) {
                $this->logger->warning('No valid meal titles found in API response.');

                return ['added' => 0, 'skipped' => 0];
            }

            $existingRecipesMap = $this->recipeRepository->findByTitles($apiTitles);

            foreach ($data['meals'] as $meal) {
                $title = trim($meal['strMeal'] ?? '');

                if (empty($title) || isset($existingRecipesMap[$title])) {
                    if (!empty($title)) {
                        $this->logger->debug(sprintf('Skipping existing recipe: "%s"', $title));
                        ++$skippedCount;
                    } else {
                        $this->logger->warning('Skipping meal with empty title.', ['meal_data' => $meal]);
                    }
                    continue;
                }

                $recipe = $this->recipeFactory->createFromApiData($meal);

                if ($recipe instanceof Recipe) {
                    $this->entityManager->persist($recipe);
                    ++$addedCount;
                    ++$batchCounter;

                    if (($batchCounter % self::BATCH_SIZE) === 0) {
                        $this->entityManager->flush();
                        $this->entityManager->clear();
                        $this->logger->info(sprintf('Flushed batch of %d recipes.', self::BATCH_SIZE));
                    }
                } else {
                    $this->logger->warning(sprintf('Failed to create recipe object for meal: "%s". Check factory logs.', $title));
                    ++$skippedCount;
                }
            }

            if (($batchCounter % self::BATCH_SIZE) !== 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $this->logger->info(sprintf('Flushed final batch of %d recipes.', $batchCounter % self::BATCH_SIZE));
            }

            $this->logger->info(sprintf('Recipe synchronization finished. Added: %d, Skipped: %d', $addedCount, $skippedCount));

            return ['added' => $addedCount, 'skipped' => $skippedCount];
        } catch (TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface|DecodingExceptionInterface $e) {
            $this->logger->error('API request/decoding error during recipe sync: '.$e->getMessage(), ['exception' => $e]);
            throw new RecipeSyncException('Failed to fetch or decode data from API: '.$e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            $this->logger->critical('Unexpected error during recipe sync: '.$e->getMessage(), ['exception' => $e]);
            throw new RecipeSyncException('An unexpected error occurred: '.$e->getMessage(), $e->getCode(), $e);
        }
    }
}
