<?php

declare(strict_types=1);

namespace App\Validation;

final class TaskValidator
{
    /** @param array<string, mixed> $payload */
    public static function validateCreate(array $payload): array
    {
        $errors = [];
        $title = trim((string) ($payload['title'] ?? ''));

        if ($title === '') {
            $errors[] = 'title e obrigatorio.';
        }
        if (strlen($title) > 140) {
            $errors[] = 'title deve ter no maximo 140 caracteres.';
        }

        $status = $payload['status'] ?? 'todo';
        if (!in_array($status, ['todo', 'in_progress', 'done'], true)) {
            $errors[] = 'status deve ser: todo, in_progress ou done.';
        }

        return $errors;
    }

    /** @param array<string, mixed> $payload */
    public static function validateUpdate(array $payload): array
    {
        $errors = [];

        if (array_key_exists('title', $payload) && trim((string) $payload['title']) === '') {
            $errors[] = 'title nao pode ser vazio.';
        }

        if (array_key_exists('title', $payload) && strlen((string) $payload['title']) > 140) {
            $errors[] = 'title deve ter no maximo 140 caracteres.';
        }

        if (array_key_exists('status', $payload) && !in_array($payload['status'], ['todo', 'in_progress', 'done'], true)) {
            $errors[] = 'status deve ser: todo, in_progress ou done.';
        }

        return $errors;
    }
}
