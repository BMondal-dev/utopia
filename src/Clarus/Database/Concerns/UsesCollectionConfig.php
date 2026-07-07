<?php

namespace Clarus\Database\Concerns;

use Utopia\Config\Config;
use Utopia\Database\Database;

trait UsesCollectionConfig
{
    protected function createAttributeFromCollection(
        Database $database,
        string $collectionId,
        string $attributeId,
        ?string $from = null,
    ): void {
        $attribute = $this->getAttributeDefinition($from ?? $collectionId, $attributeId);
        $filters = $attribute['filters'] ?? [];
        $default = $attribute['default'] ?? null;

        $database->createAttribute(
            collection: $collectionId,
            id: $attributeId,
            type: $attribute['type'],
            size: $attribute['size'],
            required: $attribute['required'],
            default: \in_array('json', $filters, true) ? \json_encode($default) : $default,
            signed: $attribute['signed'] ?? true,
            array: $attribute['array'] ?? false,
            format: $attribute['format'] ?? '',
            formatOptions: $attribute['formatOptions'] ?? [],
            filters: $filters,
        );
    }

    /**
     * @param list<string> $attributeIds
     */
    protected function createAttributesFromCollection(
        Database $database,
        string $collectionId,
        array $attributeIds,
        ?string $from = null,
    ): void {
        foreach ($attributeIds as $attributeId) {
            $this->createAttributeFromCollection($database, $collectionId, $attributeId, $from);
        }
    }

    protected function createIndexFromCollection(
        Database $database,
        string $collectionId,
        string $indexId,
        ?string $from = null,
    ): void {
        $index = $this->getIndexDefinition($from ?? $collectionId, $indexId);

        $database->createIndex(
            collection: $collectionId,
            id: $indexId,
            type: $index['type'],
            attributes: $index['attributes'],
            lengths: $index['lengths'] ?? [],
            orders: $index['orders'] ?? [],
            ttl: $index['ttl'] ?? 1,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getCollectionDefinition(string $collectionId): array
    {
        $collections = Config::getParam('collections', []);
        $collection = $collections[$collectionId] ?? null;

        if (!\is_array($collection)) {
            throw new \RuntimeException("Collection '{$collectionId}' is not defined in app/config/collections.php.");
        }

        return $collection;
    }

    /**
     * @return array<string, mixed>
     */
    private function getAttributeDefinition(string $collectionId, string $attributeId): array
    {
        $collection = $this->getCollectionDefinition($collectionId);

        foreach ($collection['attributes'] ?? [] as $attribute) {
            if (($attribute['$id'] ?? '') === $attributeId) {
                return $attribute;
            }
        }

        throw new \RuntimeException("Attribute '{$attributeId}' is not defined for collection '{$collectionId}'.");
    }

    /**
     * @return array<string, mixed>
     */
    private function getIndexDefinition(string $collectionId, string $indexId): array
    {
        $collection = $this->getCollectionDefinition($collectionId);

        foreach ($collection['indexes'] ?? [] as $index) {
            if (($index['$id'] ?? '') === $indexId) {
                return $index;
            }
        }

        throw new \RuntimeException("Index '{$indexId}' is not defined for collection '{$collectionId}'.");
    }
}
