# Kuchenne receptury

Aplikacja do synchronizacji i wyświetlania przepisów kulinarnych z TheMealDB.

## Instalacja

### 1. Rozpakowanie repozytorium
Najpierw wypakuj repozytorium do lokalnego folderu i
wejdź do wypakowanego lokalnie katalogu (co zapewne już zrobiłeś)

### 2. Instalacja
```sh
docker-compose up -d --build
```

### 3. Przygotowanie bazy - puszczenie migracji
(powinny być już puszczone bo odpala się job na starcie dockera)
```sh
docker exec -it symfony_app bash
php bin/console doctrine:migrations:migrate
```

### 4. Pobranie przepisów z API TheMealDB
Aby zsynchronizować ręcznie przepisy z TheMealDB, uruchom:
(powinny być już zsynchronizowane bo odpala się job na starcie dockera)
```sh
php bin/console app:sync-recipes
```
cron jest ustawiony co godzinę

### 5. Linki:

http://127.0.0.1:8080/recipes - przepisy
http://127.0.0.1:8080/favorites - ulubione
