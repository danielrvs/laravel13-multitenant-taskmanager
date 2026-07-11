<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Task\CreateTaskDto;
use App\DTOs\Task\UpdateTaskDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Task\Task;
use App\Services\Task\CreateTaskService;
use App\Services\Task\DeleteTaskService;
use App\Services\Task\GetAllTasksService;
use App\Services\Task\GetTaskByIdService;
use App\Services\Task\UpdateTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function __construct(
        private readonly GetAllTasksService $getAllTasksService,
        private readonly GetTaskByIdService $getTaskByIdService,
        private readonly CreateTaskService $createTaskService,
        private readonly DeleteTaskService $deleteTaskService,
        private readonly UpdateTaskService $updateTaskService
    ) {}

    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', [Task::class]);

        return response()->json($this->getAllTasksService->execute());
    }

    public function show(int $id): JsonResponse
    {
        Gate::authorize('viewAny', [Task::class]);

        return response()->json($this->getTaskByIdService->execute($id));
    }

    public function create(StoreTaskRequest $request): JsonResponse
    {
        Gate::authorize('create', [Task::class]);
        $dto = CreateTaskDto::fromRequest($request);
        $data = $this->createTaskService->execute($dto);

        return response()->json([
            'data' => $data,
            'message' => 'Task created successfully',
        ], 201);
    }

    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        Gate::authorize('update', [Task::class, $id]);

        $dto = UpdateTaskDto::fromRequest($request);
        $this->updateTaskService->execute($id, $dto);

        return response()->json([], 204);

    }

    public function delete(int $id): JsonResponse
    {
        Gate::authorize('delete', [Task::class, $id]);

        $this->deleteTaskService->execute($id);

        return response()->json([], 204);

    }
}
