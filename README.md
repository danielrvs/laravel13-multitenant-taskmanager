# Multitenant SaaS Task Manager

A production-oriented REST API built with **Laravel 13** that demonstrates advanced backend engineering patterns: multitenancy with automatic data isolation, a Repository/Service architecture with a transparent cache decorator, role-based authorization, idempotent background jobs, and safe batch deletion of stale data.

## Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 (PHP 8.3+) |
| Auth | Laravel Sanctum + Fortify |
| Database | PostgreSQL 18 |
| Cache | Redis 8 |
| Queue | Laravel Queue (database driver) |
| Infrastructure | Docker + Docker Compose |

---

## Architecture

### Multitenancy — Shared Database, Isolated Data

All tenants share the same database. Isolation is enforced transparently at the ORM level through a **Global Query Scope** (`TenantScope`) that appends a `WHERE tenant_id = ?` clause to every query automatically.

```
HTTP Request
    → TenantMiddleware          # reads tenant_id from the authenticated user
        → TenantManager         # singleton that holds the active tenant_id
            → BelongsToTenantTrait (bootBelongsToTenantTrait)
                → TenantScope   # injects WHERE tenant_id = ? on every Eloquent query
```

This means no service or repository needs to know about tenant filtering — it happens at the model layer, making it impossible to accidentally leak data between tenants.

### Repository Pattern with Cache Decorator

The `TaskRepository` contract is implemented by two classes chained via the Decorator pattern:

```
TaskRepository (interface)
    └── CachedTaskRepository    # decorator: read from cache, invalidate on writes
            └── EloquentTaskRepository  # source of truth
```

The cache layer is transparent to the rest of the application. The `AppServiceProvider` binds the interface to the `CachedTaskRepository`, injecting `EloquentTaskRepository` as its inner dependency. Cache keys are scoped per tenant (`tenant_{id}_tasks`, `tenant_{id}_tasks_{taskId}`), so a write from one tenant never invalidates another tenant's cache.

### Service Layer

Controllers are thin — they delegate all business logic to dedicated single-responsibility services:

```
TaskController
    → CreateTaskService  →  TaskRepository (via interface)
    → UpdateTaskService  →  TaskRepository
    → DeleteTaskService  →  TaskRepository
    → GetAllTasksService →  TaskRepository
    → GetTaskByIdService →  TaskRepository
```

### Data Transfer Objects (DTOs)

Form data flows through typed, immutable DTOs. The `AbstractDto` base class uses **PHP Reflection** to build itself from any `FormRequest` and serialize back to array, removing the need for manual mapping boilerplate in every DTO subclass. The `presentFields` property enables safe partial updates: `toArray()` only returns fields that were actually present in the original request, preventing accidental overwrites of untouched fields.

```php
// Controller — thin, no business logic
$dto = UpdateTaskDto::fromRequest($request); // reflection-based, automatic
$this->updateTaskService->execute($id, $dto);
```

### Authorization — Policies + RBAC

Three roles are enforced via a `TaskPolicy`:

| Role | View | Create | Update | Delete |
|---|:---:|:---:|:---:|:---:|
| `admin` | ✅ | ✅ | ✅ | ✅ |
| `manager` | ✅ | ✅ | ✅ | ❌ |
| `viewer` | ✅ | ❌ | ❌ | ❌ |

The `before()` hook on the policy grants admins unconditional access, keeping every other rule clean and declarative.

---

## Key Design Decisions

### Validation is tenant-aware
The `assigned_to` field in `StoreTaskRequest` validates not just that the user exists, but that they belong to the **same tenant** as the authenticated user:
```php
Rule::exists('users', 'id')->where('tenant_id', $this->user()->tenant_id)
```

### Domain invariants live on the Model
The `Task` model enforces business rules that cannot be expressed at the HTTP layer:
- `assignTo()` — prevents cross-tenant assignment and self-assignment, throwing a `ValidationException` if violated.
- `transitionStatus()` — encapsulates status machine logic.
- `isOverdue()` — pure computed property based on `due_date` and `status`.
- The `title` attribute setter rejects empty or oversized values at the persistence level, not just the request layer.

### Idempotent background jobs
`NotifyTaskAssignment` is fully idempotent. Before sending a notification it checks a `task_audits` audit log. If the user was already notified, it exits early. This makes it safe to retry failed jobs (configured with exponential backoff: 30s, 120s, 300s) without risk of duplicate notifications.

### Safe bulk deletion (chunkById + cache invalidation)
`tasks:purge-overdue` deletes overdue tasks that are more than one year old using `chunkById` to avoid OOM errors and table locks on large datasets. The sequence within each chunk is intentional:
1. **Delete from database first** — removes the source of truth.
2. **Invalidate cache after** — prevents a race condition where a cache miss between steps would re-populate the cache with already-deleted data.

---

## API Reference

All endpoints require a valid Sanctum token (`Authorization: Bearer <token>`).

```
GET    /api/tasks          List all tasks for the authenticated tenant
POST   /api/tasks          Create a new task
GET    /api/tasks/{id}     Get a single task
PUT    /api/tasks/{id}     Full update of a task
PATCH  /api/tasks/{id}     Partial update of a task
DELETE /api/tasks/{id}     Delete a task (admin only)
```

### Create task — request body

```json
{
    "title":       "Deploy to production",
    "description": "Run migrations and tag the release",
    "status":      "pending",
    "priority":    "high",
    "due_date":    "2025-12-31",
    "assigned_to": 42
}
```

`status` values: `pending` · `in_progress` · `completed`  
`priority` values: `low` · `medium` · `high`

---

## Getting Started

### Prerequisites

- Docker + Docker Compose
- GNU Make (optional, scripts are plain shell)

### 1. Clone and configure

```bash
git clone https://github.com/<your-username>/multitenant_saas_tasksmanager.git
cd multitenant_saas_tasksmanager
cp infrastructure/.env.example infrastructure/.env  # edit DB credentials
```

### 2. Start the stack

```bash
./docker-start.sh
```

This brings up three containers: `php_app` (Laravel), `postgres_db`, and `redis_cache`.

### 3. Bootstrap the application

```bash
docker exec -it php_app bash

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### 4. Stop

```bash
./docker-stop.sh
```

---

## Running Tests

```bash
docker exec -it php_app bash
php artisan test
```

Or with coverage:

```bash
php artisan test --coverage
```

### Test structure

```
tests/
├── Unit/
│   └── Model/Task/
│       ├── TaskTest.php          # Domain invariants (assignTo, transitionStatus, isOverdue)
│       └── TaskStatusTest.php    # Status enum & transition machine
└── Feature/
    ├── Task/
    │   └── TaskPolicyTest.php    # RBAC — role permissions per HTTP verb
    └── Jobs/
        └── NotifyTaskAssignmentTest.php  # Job dispatch + idempotency
```

---

## Console Commands

```bash
# Purge non-completed tasks with a due_date older than one year.
# Uses chunkById for memory efficiency. Safe to run as a scheduled cron job.
php artisan tasks:purge-overdue
```

Recommended schedule (add to `routes/console.php`):
```php
Schedule::command('tasks:purge-overdue')->weekly()->runInBackground();
```

---

## Project Structure

```
app/
├── Console/Commands/          Artisan commands
├── Contracts/Repositories/    Repository interfaces
├── DTOs/                      Immutable data transfer objects (reflection-based)
├── Enums/                     UserRole
├── Http/
│   ├── Controllers/Api/V1/    Thin controllers, no business logic
│   ├── Middleware/            TenantMiddleware — populates TenantManager
│   └── Requests/Task/         Form requests with tenant-aware validation
├── Infrastructure/
│   └── Repositories/Task/
│       ├── EloquentTaskRepository.php   Source of truth
│       └── CachedTaskRepository.php    Decorator: Redis cache layer
├── Jobs/                      NotifyTaskAssignment (idempotent, retriable)
├── Models/
│   ├── Task/
│   │   ├── Task.php           Domain model with business invariants
│   │   └── Vo/                Value objects (TaskStatus, TaskPriority)
│   └── Traits/                BelongsToTenantTrait, BelongsToUserTrait
├── Policies/                  TaskPolicy (RBAC)
├── Providers/                 AppServiceProvider — repository binding, Password defaults
└── Services/
    ├── Task/                  CreateTask, UpdateTask, DeleteTask, GetTask…
    └── Tenant/                TenantManager singleton
infrastructure/
├── docker-compose.local.yml
└── php/Dockerfile
```
