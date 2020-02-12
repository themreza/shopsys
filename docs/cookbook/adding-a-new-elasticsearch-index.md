# Adding a New Elasticsearch Index

In this cookbook, we will add a new elasticsearch index for categories, implement basic functions for data export, implement cron module, and support for partial export.

## New elasticsearch mapping

As a first step we need to define [elasticsearch mapping](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html) in `src/Resources/definition/category/` for all domains (e.g. for 2 domains with ID 1 and 2: `1.json`, `2.json`).

## New CategoryIndex

Create a class `CategoryIndex` in `src/Model/Category/Elasticsearch`. The class must extends class `AbstractIndex`.

```php
declare(strict_types=1);

namespace App\Model\Category\Elasticsearch;

use Shopsys\FrameworkBundle\Component\Elasticsearch\AbstractIndex;

class CategoryIndex extends AbstractIndex
{
    public function getName(): string
    {
        // TODO: Implement getName() method.
    }
    public function getTotalCount(int $domainId): int
    {
        // TODO: Implement getTotalCount() method.
    }
    public function getExportDataForIds(int $domainId, array $restrictToIds): array
    {
        // TODO: Implement getExportDataForIds() method.
    }

    public function getExportDataForBatch(int $domainId,int $lastProcessedId,int $batchSize) : array
    {
        // TODO: Implement getExportDataForBatch() method.
    }
}
```

Register new index into `services.yml`

```yaml
    Shopsys\FrameworkBundle\Model\Category\Elasticsearch\CategoryIndex: ~
```

## Create new index into elasticsearch

To create an index into elasticsearch we need to implement `getName()` method in `CategoryIndex`.
The best practice is to define index name into constant due to later usage for obtaining data.

```diff
+   public const INDEX_NAME = 'category';
+
    public function getName(): string
    {
-       // TODO: Implement getName() method.
+       return static::INDEX_NAME;
    }
```

So far it is the most minimalistic implementation.
Now we are able to create an index in elasticsearch by running `./phing elasticsearch-index-create -D elasticsearc.index=category`.
Also we can use `./phing elasticsearch-index-recreate` or `./phing elasticsearch-index-delete`.

!!! note
Command `./phing elasticsearch-index-create -D elasticsearc.index=category` will creates elasticsearch index only for out CategoryIndex.
Using `./phing elasticsearch-index-create` (without `-D` flag) it will create elasticsearch indexes for all registered ones in your project (product, category, and so on).

## Export data into elasticsearch

Creating and deleting index is nice but it is not really useful.
As a next step we will implement method `getTotalCount()` and `getExportDataForBatch()` to be able export data.

We can use already existing method in `\Shopsys\FrameworkBundle\Model\Category\CategoryRepository::getTranslatedVisibleSubcategoriesByDomain()`.
The method `getTranslatedVisibleSubcategoriesByDomain()` needs as a second argument an instance of `DomainConfig` so we need to inject an instance of `Domain` class along with instance of `CategortRepository` into `CategoryIndex`.  

```diff
+   /**
+    * @var \Shopsys\FrameworkBundle\Model\Category\CategoryRepository
+    */
+   protected $categoryRepository;
+
+   /**
+    * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
+    */
+   protected $domain;
+
+   /**
+    * @param \Shopsys\FrameworkBundle\Model\Category\CategoryRepository $categoryRepository
+    * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
+    */
+   public function __construct(CategoryRepository $categoryRepository, Domain $domain)
+   {
+       $this->categoryRepository = $categoryRepository;
+       $this->domain = $domain;
+   }
+
```

When we have injected services we may implement `getTotalCount()`

```diff
   public function getTotalCount(int $domainId): int
    {
-       // TODO: Implement getTotalCount() method.
+       return count($this->categoryRepository->getTranslatedVisibleSubcategoriesByDomain(
+           $this->categoryRepository->getRootCategory(),
+           $this->domain->getDomainConfigById($domainId)
+       ));
    }
```

and also a `getExportDataForBatch()` with a private converting method `convertToElastic()`

```diff
    public function getExportDataForBatch(int $domainId,int $lastProcessedId,int $batchSize) : array
    {
-       // TODO: Implement getExportDataForBatch() method.
+       $domainConfig = $this->domain->getDomainConfigById($domainId);
+       $categories = $this->categoryRepository->getTranslatedVisibleSubcategoriesByDomain(
+           $this->categoryRepository->getRootCategory(),
+           $domainConfig
+       );
+       $locale = $domainConfig->getLocale();
+       foreach ($categories as $category) {
+           $result[$category->getId()] = $this->convertToElastic($category, $domainId, $locale);
+       }
+       return $result;
    }
+
+   private function convertToElastic(Category $category, int $domainId, string $locale): array
+   {
+       return [
+           'name' => $category->getName($locale),
+           'description' => $category->getDescription($domainId),
+           'parentId' => $category->getParent()->getId(),
+           'level' => $category->getLevel(),
+           'uuid' => $category->getUuid(),
+       ];
+   }
```

!!! note
The `getExportDataForBatch()` must return serialized array of rows indexed by its ID

Now we can export categories data (name, description, parentId, level, and uuid) into elasticsearch index via `./phing elasticsearch-export -D elaticsearch.index=category` (we need to have created index first, see the step above).

### Exporting via cron

We may automate the export process via CronModule which is super easy.
All we need to achieve this goal is to create a new class `CategoryExportCronModule` which extends `AbstractExportCronModule`.

The most important task here is to override parent constructor with changing a type hint of the first argument to our created index (`CategoryIndex`).

```php
declare(strict_types=1);

namespace App\Model\Category\Elasticsearch;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Elasticsearch\AbstractExportCronModule;
use Shopsys\FrameworkBundle\Component\Elasticsearch\IndexDefinitionLoader;
use Shopsys\FrameworkBundle\Component\Elasticsearch\IndexFacade;

class CategoryExportCronModule extends AbstractExportCronModule
{
    /**
     * @param \App\Model\Category\Elasticsearch\CategoryIndex $index
     * @param \Shopsys\FrameworkBundle\Component\Elasticsearch\IndexFacade $indexFacade
     * @param \Shopsys\FrameworkBundle\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        CategoryIndex $index,
        IndexFacade $indexFacade,
        IndexDefinitionLoader $indexDefinitionLoader,
        Domain $domain
    ) {
        parent::__construct($index, $indexFacade, $indexDefinitionLoader, $domain);
    }
}
```

Now if we have [crons](../introduction/cron.md) properly configured the new cron will be started automatically with others.
Or you may want to [configure](../cookbook/working-with-multiple-cron-instances.md) this cron module with different timing

### Implement partial update

Sometimes we want to export data immediately after the original data are changed (hiding category, rename, ...).
For this purpose we need to implement `CategoryIndex::getExportDataForIds()`, scheduler for queuing, and [subscriber](https://symfony.com/doc/current/event_dispatcher.html#creating-an-event-subscriber) for processing queue.

Anywhere you want to add row to immediate export you may call `CategoryExportScheduler::scheduleRowIdForImmediateExport()` for adding a row into the queue.

#### Scheduler

Scheduler is used as a queue of IDs which we want to export. When we make any changes we may add an affected category ID into this queue and Subscriber will pick it up after request is done.

Create class `CategoryExportScheduler` which extends `AbstractExportScheduler`. We do not need to override nor implement any method.

```php
declare(strict_types=1);

namespace App\Model\Category\Elasticsearch;

use Shopsys\FrameworkBundle\Component\Elasticsearch\AbstractExportScheduler;

class CategoryExportScheduler extends AbstractExportScheduler
{
}
```

#### Subscriber

Create class `CategoryExportSubscriber` which extends `AbstractExportSubsriber`.
Override its `__construct()`, and `getSubscribedEvents()`.

Here is important to override constructors arguments type hint.
Instead of abstract classes from `\Shopsys\FrameworkBundle\Component\Elasticsearch` you need to replace it with our new implementation (`CategoryExportScheduler`, `CategoryIndex`).

Implementation of `getSubscribedEvents()` is desired to use `exportScheduledRows()` from abstract class but also we can implement in by ourselves.

```php
declare(strict_types=1);

namespace App\Model\Category\Elasticsearch;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Elasticsearch\AbstractExportSubscriber;
use Shopsys\FrameworkBundle\Component\Elasticsearch\IndexDefinitionLoader;
use Shopsys\FrameworkBundle\Component\Elasticsearch\IndexRepository;
use Symfony\Component\HttpKernel\KernelEvents;

class CategoryExportSubscriber extends AbstractExportSubscriber
{
    /**
     * @param \App\Model\Category\Elasticsearch\CategoryExportScheduler $categoryExportScheduler
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Shopsys\FrameworkBundle\Component\Elasticsearch\IndexRepository $indexRepository
     * @param \Shopsys\FrameworkBundle\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     * @param \App\Model\Category\Elasticsearch\CategoryIndex $index
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        CategoryExportScheduler $categoryExportScheduler,
        EntityManagerInterface $entityManager,
        IndexRepository $indexRepository,
        IndexDefinitionLoader $indexDefinitionLoader,
        CategoryIndex $index,
        Domain $domain
    ) {
        parent::__construct($categoryExportScheduler, $entityManager, $indexRepository, $indexDefinitionLoader, $index, $domain);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                ['exportScheduledRows', 0],
            ],
        ];
    }
}
```

#### CategoryIndex::getExportDataForIds()

To finish partial exports we need to implement the last unimplemented method in `CategoryIndex` to return all categories by their identifiers.
We may also use already existing method from `CategoryRepository`.

```diff
    public function getExportDataForIds(int $domainId, array $restrictToIds): array
    {
-       // TODO: Implement getExportDataForIds() method.
+       $categories = $this->categoryRepository->getCategoriesByIds($restrictToIds);
+
+       $domainConfig = $this->domain->getDomainConfigById($domainId);
+       $locale = $domainConfig->getLocale();
+       foreach ($categories as $category) {
+           $result[$category->getId()] = $this->convertToElastic($category, $domainId, $locale);
+       }
+       return $result;
    }
```

## Conclusion

We have created a new index category into elasticsearch. We are able to fill it with data (by a cron or immediately after row is changed).
From now you are able to use elasticsearch as a data source (data providing functionality is needed to be implemented) instead PostgreSQL which will save your performance.