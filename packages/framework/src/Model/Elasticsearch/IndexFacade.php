<?php

declare(strict_types=1);

namespace Shopsys\FrameworkBundle\Model\Elasticsearch;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Symfony\Component\Console\Output\OutputInterface;

class IndexFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Elasticsearch\IndexManager
     */
    protected $indexManager;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Elasticsearch\IndexDefinitionLoader
     */
    protected $indexDefinitionLoader;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Elasticsearch\IndexManager $indexManager
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     */
    public function __construct(
        IndexManager $indexManager,
        Domain $domain,
        IndexDefinitionLoader $indexDefinitionLoader
    ) {
        $this->indexManager = $indexManager;
        $this->domain = $domain;
        $this->indexDefinitionLoader = $indexDefinitionLoader;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Elasticsearch\AbstractIndex $index
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function createIndex(AbstractIndex $index, OutputInterface $output): void
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            $output->writeln(sprintf('Creating index "%s" on domain "%s"', $index->getName(), $domainConfig->getId()));
            $indexDefinition = $this->indexDefinitionLoader->getIndexDefinition($index, $domainConfig->getId());

            $this->indexManager->createIndex($indexDefinition);
            $this->indexManager->createAlias($indexDefinition);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Elasticsearch\AbstractIndex[] $indexes
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function createIndexes(array $indexes, OutputInterface $output): void
    {
        foreach ($indexes as $index) {
            $this->createIndex($index, $output);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Elasticsearch\AbstractIndex $index
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function deleteIndex(AbstractIndex $index, OutputInterface $output): void
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            $output->writeln(sprintf('Deleting index "%s" on domain "%s"', $index->getName(), $domainConfig->getId()));
            $documentDefinition = $this->indexDefinitionLoader->getIndexDefinition($index, $domainConfig->getId());
            $this->indexManager->deleteIndexByIndexDefinition($documentDefinition);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Elasticsearch\AbstractIndex[] $indexes
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function deleteIndexes(array $indexes, OutputInterface $output): void
    {
        foreach ($indexes as $index) {
            $this->deleteIndex($index, $output);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Elasticsearch\AbstractIndex $index
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function exportByIndex(AbstractIndex $index, OutputInterface $output): void
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            $output->writeln(sprintf('Exporting data of "%s" on domain "%s"', $index->getName(), $domainConfig->getId()));
            $indexDefinition = $this->indexDefinitionLoader->getIndexDefinition($index, $domainConfig->getId());
            $this->indexManager->export($indexDefinition, [], $output);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Elasticsearch\AbstractIndex[] $indexes
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function exportByIndexes(array $indexes, OutputInterface $output): void
    {
        foreach ($indexes as $index) {
            $this->exportByIndex($index, $output);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Elasticsearch\AbstractIndex[] $indexes
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function migrateByIndexes(array $indexes, OutputInterface $output): void
    {
        foreach ($indexes as $index) {
            $this->migrateIndex($index, $output);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Elasticsearch\AbstractIndex $index
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function migrateIndex(AbstractIndex $index, OutputInterface $output): void
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            $indexDefinition = $this->indexDefinitionLoader->getIndexDefinition($index, $domainConfig->getId());
            $existingIndexName = $this->indexManager->findCurrentIndexNameForAlias($indexDefinition->getIndexAlias());
            if ($existingIndexName === $indexDefinition->getVersionedIndexName()) {
                $output->writeln(sprintf('Index "%s" on domain "%s" is up to date', $index->getName(), $domainConfig->getId()));
                continue;
            }

            $output->writeln(sprintf('Migrating index "%s" on domain "%s"', $index->getName(), $domainConfig->getId()));

            $this->indexManager->createIndex($indexDefinition);
            $this->indexManager->reindex($existingIndexName, $indexDefinition->getVersionedIndexName());
            $this->indexManager->createAlias($indexDefinition);
            $this->indexManager->deleteIndex($existingIndexName);
        }
    }
}