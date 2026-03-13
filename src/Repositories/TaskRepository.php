<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

final class TaskRepository
{
    private ?PDO $pdo = null;
    private bool $usesSqlite;
    private string $jsonPath;

    public function __construct()
    {
        $this->usesSqlite = extension_loaded('pdo_sqlite');
        $jsonPath = getenv('JSON_DB_PATH');
        if (!$jsonPath) {
            $jsonPath = getenv('DB_PATH');
        }
        if (!$jsonPath) {
            $jsonPath = dirname(__DIR__, 2) . '/database/tasks.json';
        }
        $this->jsonPath = $jsonPath;

        if ($this->usesSqlite) {
            $this->pdo = Database::connection();
            return;
        }

        $directory = dirname($this->jsonPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        if (!file_exists($this->jsonPath)) {
            file_put_contents($this->jsonPath, json_encode([]));
        }
    }

    public function backend(): string
    {
        return $this->usesSqlite ? 'sqlite' : 'json-file';
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        if ($this->usesSqlite) {
            $stmt = $this->pdo->query('SELECT * FROM tasks ORDER BY id DESC');
            return $stmt->fetchAll();
        }

        $tasks = $this->readJson();
        usort(
            $tasks,
            static fn (array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']
        );

        return $tasks;
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        if ($this->usesSqlite) {
            $stmt = $this->pdo->prepare('SELECT * FROM tasks WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();

            return $row === false ? null : $row;
        }

        foreach ($this->readJson() as $task) {
            if ((int) $task['id'] === $id) {
                return $task;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): array
    {
        if ($this->usesSqlite) {
            $now = gmdate('c');
            $stmt = $this->pdo->prepare(
                'INSERT INTO tasks (title, description, status, created_at, updated_at)
                 VALUES (:title, :description, :status, :created_at, :updated_at)'
            );
            $stmt->execute([
                'title' => $payload['title'],
                'description' => $payload['description'] ?? null,
                'status' => $payload['status'] ?? 'todo',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $id = (int) $this->pdo->lastInsertId();
            return $this->find($id) ?? [];
        }

        $tasks = $this->readJson();
        $task = [
            'id' => $this->nextId($tasks),
            'title' => (string) $payload['title'],
            'description' => $payload['description'] ?? null,
            'status' => $payload['status'] ?? 'todo',
            'created_at' => gmdate('c'),
            'updated_at' => gmdate('c'),
        ];
        $tasks[] = $task;
        $this->writeJson($tasks);

        return $task;
    }

    /** @param array<string, mixed> $payload */
    public function update(int $id, array $payload): ?array
    {
        if ($this->usesSqlite) {
            $existing = $this->find($id);
            if ($existing === null) {
                return null;
            }

            $stmt = $this->pdo->prepare(
                'UPDATE tasks
                 SET title = :title,
                     description = :description,
                     status = :status,
                     updated_at = :updated_at
                 WHERE id = :id'
            );

            $stmt->execute([
                'id' => $id,
                'title' => $payload['title'] ?? $existing['title'],
                'description' => $payload['description'] ?? $existing['description'],
                'status' => $payload['status'] ?? $existing['status'],
                'updated_at' => gmdate('c'),
            ]);

            return $this->find($id);
        }

        $tasks = $this->readJson();
        foreach ($tasks as $index => $task) {
            if ((int) $task['id'] !== $id) {
                continue;
            }

            $tasks[$index] = [
                'id' => $id,
                'title' => $payload['title'] ?? $task['title'],
                'description' => $payload['description'] ?? $task['description'],
                'status' => $payload['status'] ?? $task['status'],
                'created_at' => $task['created_at'],
                'updated_at' => gmdate('c'),
            ];
            $this->writeJson($tasks);

            return $tasks[$index];
        }

        return null;
    }

    public function delete(int $id): bool
    {
        if ($this->usesSqlite) {
            $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id');
            $stmt->execute(['id' => $id]);

            return $stmt->rowCount() > 0;
        }

        $tasks = $this->readJson();
        $remaining = array_values(array_filter(
            $tasks,
            static fn (array $task): bool => (int) $task['id'] !== $id
        ));

        if (count($remaining) === count($tasks)) {
            return false;
        }

        $this->writeJson($remaining);
        return true;
    }

    /** @return array<int, array<string, mixed>> */
    private function readJson(): array
    {
        $raw = file_get_contents($this->jsonPath);
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<int, array<string, mixed>> $tasks */
    private function writeJson(array $tasks): void
    {
        file_put_contents(
            $this->jsonPath,
            json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /** @param array<int, array<string, mixed>> $tasks */
    private function nextId(array $tasks): int
    {
        if ($tasks === []) {
            return 1;
        }

        $max = 0;
        foreach ($tasks as $task) {
            $id = (int) ($task['id'] ?? 0);
            if ($id > $max) {
                $max = $id;
            }
        }

        return $max + 1;
    }
}
