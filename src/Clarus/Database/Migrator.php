<?php

namespace Clarus\Database;

use DateTimeImmutable;
use DateTimeInterface;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Exception\Duplicate as DuplicateException;
use Utopia\Database\Helpers\ID;

class Migrator
{
    private const string COLLECTION_MIGRATIONS = 'migrations';

    /**
     * @param list<Migration> $migrations
     */
    public function __construct(
        private readonly array $migrations = [],
    ) {
        $this->assertValidMigrationList($this->migrations);
    }

    public function run(Database $db): int
    {
        $pending = 0;
        $applied = 0;

        foreach ($this->migrations as $migration) {
            if ($this->isApplied($db, $migration)) {
                continue;
            }

            $pending++;
            $this->log("Running {$migration->getId()}: {$migration->getName()}");

            try {
                $migration->execute($db);
                $this->recordApplied($db, $migration);
            } catch (\Throwable $error) {
                $this->log("Failed {$migration->getId()}: {$error->getMessage()}");
                throw $error;
            }

            $applied++;
            $this->log("Done {$migration->getId()}");
        }

        if ($pending === 0) {
            $this->log('No pending migrations.');
        }

        return $applied;
    }

    public function status(Database $db): void
    {
        $applied = [];
        $pending = [];

        foreach ($this->migrations as $migration) {
            $document = $this->getAppliedMigration($db, $migration);

            if ($document->isEmpty()) {
                $pending[] = $migration;
                continue;
            }

            $applied[] = [
                'migration' => $migration,
                'appliedAt' => $document->getAttribute('appliedAt', 'unknown'),
            ];
        }

        $this->log('Applied:');
        if ($applied === []) {
            $this->log('- none');
        } else {
            foreach ($applied as $item) {
                /** @var Migration $migration */
                $migration = $item['migration'];
                $this->log("- {$migration->getId()} ({$item['appliedAt']}) {$migration->getName()}");
            }
        }

        $this->log('');
        $this->log('Pending:');
        if ($pending === []) {
            $this->log('- none');
        } else {
            foreach ($pending as $migration) {
                $this->log("- {$migration->getId()} {$migration->getName()}");
            }
        }
    }

    private function isApplied(Database $db, Migration $migration): bool
    {
        return !$this->getAppliedMigration($db, $migration)->isEmpty();
    }

    private function getAppliedMigration(Database $db, Migration $migration): Document
    {
        return $db->getDocument(self::COLLECTION_MIGRATIONS, $migration->getId());
    }

    private function recordApplied(Database $db, Migration $migration): void
    {
        try {
            $db->createDocument(self::COLLECTION_MIGRATIONS, new Document([
                '$id' => ID::custom($migration->getId()),
                'name' => $migration->getName(),
                'appliedAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            ]));
        } catch (DuplicateException) {
            // Another process recorded this migration first. The migration itself completed successfully.
        }
    }

    /**
     * @param list<Migration> $migrations
     */
    private function assertValidMigrationList(array $migrations): void
    {
        $ids = [];

        foreach ($migrations as $migration) {
            if (!$migration instanceof Migration) {
                throw new \InvalidArgumentException('Migration list can only contain Migration instances.');
            }

            $id = $migration->getId();

            if ($id === '') {
                throw new \InvalidArgumentException('Migration ID cannot be empty.');
            }

            if (isset($ids[$id])) {
                throw new \InvalidArgumentException("Duplicate migration ID: {$id}");
            }

            $ids[$id] = true;
        }
    }

    private function log(string $message): void
    {
        \fwrite(STDOUT, $message . PHP_EOL);
    }
}
