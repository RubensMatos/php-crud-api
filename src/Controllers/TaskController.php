<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\TaskRepository;
use App\Validation\TaskValidator;

final class TaskController
{
    private TaskRepository $repository;

    public function __construct()
    {
        $this->repository = new TaskRepository();
    }

    /** @param array<string, string> $params */
    public function health(Request $request, array $params = []): void
    {
        Response::json([
            'success' => true,
            'status' => 'ok',
            'service' => 'PHP CRUD API',
            'storageBackend' => $this->repository->backend(),
            'timestamp' => gmdate('c'),
        ]);
    }

    /** @param array<string, string> $params */
    public function index(Request $request, array $params = []): void
    {
        $tasks = $this->repository->all();

        Response::json([
            'success' => true,
            'data' => $tasks,
            'meta' => ['count' => count($tasks)],
        ]);
    }

    /** @param array<string, string> $params */
    public function show(Request $request, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            Response::json(['success' => false, 'error' => 'id invalido.'], 400);
            return;
        }

        $task = $this->repository->find($id);
        if ($task === null) {
            Response::json(['success' => false, 'error' => 'task nao encontrada.'], 404);
            return;
        }

        Response::json(['success' => true, 'data' => $task]);
    }

    /** @param array<string, string> $params */
    public function store(Request $request, array $params = []): void
    {
        $errors = TaskValidator::validateCreate($request->body);
        if ($errors !== []) {
            Response::json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        $task = $this->repository->create($request->body);
        Response::json(['success' => true, 'data' => $task], 201);
    }

    /** @param array<string, string> $params */
    public function update(Request $request, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            Response::json(['success' => false, 'error' => 'id invalido.'], 400);
            return;
        }

        $errors = TaskValidator::validateUpdate($request->body);
        if ($errors !== []) {
            Response::json(['success' => false, 'errors' => $errors], 422);
            return;
        }

        $task = $this->repository->update($id, $request->body);
        if ($task === null) {
            Response::json(['success' => false, 'error' => 'task nao encontrada.'], 404);
            return;
        }

        Response::json(['success' => true, 'data' => $task]);
    }

    /** @param array<string, string> $params */
    public function destroy(Request $request, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            Response::json(['success' => false, 'error' => 'id invalido.'], 400);
            return;
        }

        $deleted = $this->repository->delete($id);
        if (!$deleted) {
            Response::json(['success' => false, 'error' => 'task nao encontrada.'], 404);
            return;
        }

        Response::json(['success' => true, 'message' => 'task removida com sucesso.']);
    }
}
