<?php

namespace Clarus\Database\Concerns;

use Utopia\Config\Config;
use Utopia\Database\Database;
use Utopia\Database\Document;

trait IteratesDocuments
{
    /**
     * Iterates all top-level configured collections and persists documents changed by the callback.
     *
     * The callback should return the changed Document. Return null to skip persisting.
     *
     * @param callable(Document): ?Document $callback
     */
    protected function forEachDocument(Database $database, callable $callback): void
    {
        foreach ($this->getTopLevelCollectionIds() as $collectionId) {
            $this->forEachDocumentInCollection($database, $collectionId, $callback);
        }
    }

    /**
     * Iterates a collection and persists documents changed by the callback.
     *
     * The callback should return the changed Document. Return null to skip persisting.
     *
     * @param callable(Document): ?Document $callback
     */
    protected function forEachDocumentInCollection(Database $database, string $collectionId, callable $callback): void
    {
        $database->foreach($collectionId, function (Document $document) use ($database, $collectionId, $callback): void {
            if ($document->getId() === '') {
                return;
            }

            $old = $document->getArrayCopy();
            $new = $callback($document);

            if (!$new instanceof Document || $new->getArrayCopy() === $old) {
                return;
            }

            $database->updateDocument($collectionId, $new->getId(), $new);
        });
    }

    /**
     * @return list<string>
     */
    private function getTopLevelCollectionIds(): array
    {
        $collections = Config::getParam('collections', []);
        $ids = [];

        foreach ($collections as $key => $collection) {
            if (($collection['$collection'] ?? '') !== Database::METADATA) {
                continue;
            }

            if ($key === 'migrations') {
                continue;
            }

            $ids[] = $collection['$id'] ?? $key;
        }

        return $ids;
    }
}
