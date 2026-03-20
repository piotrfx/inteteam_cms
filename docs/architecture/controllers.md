# Controller, Service & DTO Pattern

**Applies to:** every admin and public controller in inteteam_cms.

---

## The Rule

Controllers do four things only:
1. Authorise the request
2. Delegate validation to a Form Request
3. Call one service method
4. Return a response

All business logic — uniqueness checks, cache busting, event dispatching, conditional branching — lives in the service layer. If a controller method is doing more than the four steps above, the excess belongs in the service.

---

## Anatomy of a Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTO\Pages\CreatePageData;
use App\DTO\Pages\UpdatePageData;
use App\Http\Requests\Pages\StorePageRequest;
use App\Http\Requests\Pages\UpdatePageRequest;
use App\Models\CmsPage;
use App\Services\PageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

final class PageController
{
    public function __construct(
        private readonly PageService $pages,
    ) {}

    public function index(): Response
    {
        abort_unless(auth('cms')->user()->can('viewAny', CmsPage::class), 403);

        return inertia('Admin/Pages/Index', [
            'pages' => $this->pages->paginate(),
        ]);
    }

    public function create(): Response
    {
        abort_unless(auth('cms')->user()->can('create', CmsPage::class), 403);

        return inertia('Admin/Pages/Create');
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        abort_unless(auth('cms')->user()->can('create', CmsPage::class), 403);

        $this->pages->create(
            user: auth('cms')->user(),
            data: CreatePageData::fromRequest($request),
        );

        return redirect()->route('admin.pages.index')
            ->with(['alert' => 'Page created.', 'type' => 'success']);
    }

    public function edit(CmsPage $page): Response
    {
        abort_unless(auth('cms')->user()->can('update', $page), 403);

        return inertia('Admin/Pages/Edit', [
            'page' => $page,
        ]);
    }

    public function update(UpdatePageRequest $request, CmsPage $page): RedirectResponse
    {
        abort_unless(auth('cms')->user()->can('update', $page), 403);

        $this->pages->update($page, UpdatePageData::fromRequest($request));

        return back()->with(['alert' => 'Page saved.', 'type' => 'success']);
    }

    public function destroy(CmsPage $page): RedirectResponse
    {
        abort_unless(auth('cms')->user()->can('delete', $page), 403);

        $this->pages->delete($page);

        return redirect()->route('admin.pages.index')
            ->with(['alert' => 'Page deleted.', 'type' => 'success']);
    }

    public function publish(CmsPage $page): RedirectResponse
    {
        abort_unless(auth('cms')->user()->can('publish', $page), 403);

        $this->pages->publish($page);

        return back()->with(['alert' => 'Page is now live.', 'type' => 'success']);
    }
}
```

What this controller does **not** contain:
- Slug generation
- Duplicate type check
- Cache busting
- Revision creation
- Any `if` branch on business state

All of that is in `PageService`.

---

## Authorization Pattern

Always `abort_unless` with the `cms` guard. Never `$this->authorize()` (that uses the `web` guard and throws a different exception class). Never inline role checks.

```php
// Correct
abort_unless(auth('cms')->user()->can('publish', $page), 403);

// Wrong — uses web guard
$this->authorize('publish', $page);

// Wrong — inline role check, bypasses policy
abort_unless(auth('cms')->user()->role === 'admin', 403);
```

For `viewAny`/`create` (no model instance), pass the class name:
```php
abort_unless(auth('cms')->user()->can('viewAny', CmsPage::class), 403);
```

---

## Form Requests

One Form Request per action that receives input (store, update). The `authorize()` method always returns `true` — authorization is handled in the controller, not the Form Request.

Rules use **array syntax only** — never pipe strings.

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Pages;

use App\Support\BlockTypeRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller handles authorization
    }

    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:255'],
            'type'            => ['required', 'string', Rule::in(['home','about','contact','privacy','terms','custom'])],
            'blocks'          => ['present', 'array'],
            'blocks.*.id'     => ['required', 'string'],
            'blocks.*.type'   => ['required', 'string', Rule::in(BlockTypeRegistry::all())],
            'blocks.*.data'   => ['required', 'array'],
            'status'          => ['required', Rule::in(['draft','published'])],
            'slug'            => ['nullable', 'string', 'regex:/^[a-z0-9-]+$/', 'max:100'],
            'seo_title'       => ['nullable', 'string', 'max:60'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'seo_robots'      => ['nullable', Rule::in(['index,follow','noindex,nofollow'])],
        ];
    }
}
```

---

## DTOs

One DTO per distinct data shape (create vs update are usually different). Always `readonly`. Always has a `fromRequest()` named constructor that maps validated input to typed properties.

```php
<?php

declare(strict_types=1);

namespace App\DTO\Pages;

use App\Http\Requests\Pages\StorePageRequest;

final readonly class CreatePageData
{
    public function __construct(
        public string  $title,
        public string  $type,
        public array   $blocks,
        public string  $status,
        public ?string $slug,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public ?string $seoRobots,
    ) {}

    public static function fromRequest(StorePageRequest $request): self
    {
        return new self(
            title:          $request->validated('title'),
            type:           $request->validated('type'),
            blocks:         $request->validated('blocks'),
            status:         $request->validated('status'),
            slug:           $request->validated('slug'),
            seoTitle:       $request->validated('seo_title'),
            seoDescription: $request->validated('seo_description'),
            seoRobots:      $request->validated('seo_robots'),
        );
    }
}
```

The DTO is the contract between the controller and the service. The service never calls `$request->validated()` directly.

---

## Services

`final` class. Constructor-injected dependencies only. Never calls `auth()` or `request()` — those are controller concerns. Receives the user as a parameter when user context is needed.

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Pages\CreatePageData;
use App\DTO\Pages\UpdatePageData;
use App\Exceptions\DuplicatePageTypeException;
use App\Models\CmsPage;
use App\Models\CmsUser;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class PageService
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return CmsPage::latest()->paginate($perPage);
    }

    public function create(CmsUser $user, CreatePageData $data): CmsPage
    {
        $this->assertUniqueType($data->type);

        $page = CmsPage::create([
            'title'           => $data->title,
            'slug'            => $data->slug ?? Str::slug($data->title),
            'type'            => $data->type,
            'blocks'          => $data->blocks,
            'status'          => $data->status,
            'seo_title'       => $data->seoTitle,
            'seo_description' => $data->seoDescription,
            'seo_robots'      => $data->seoRobots,
            'created_by'      => $user->id,
        ]);

        $this->bustCache($page);

        return $page;
    }

    public function update(CmsPage $page, UpdatePageData $data): CmsPage
    {
        $page->update([
            'title'           => $data->title,
            'blocks'          => $data->blocks,
            'seo_title'       => $data->seoTitle,
            'seo_description' => $data->seoDescription,
            'seo_robots'      => $data->seoRobots,
        ]);

        $this->bustCache($page);

        return $page->refresh();
    }

    public function publish(CmsPage $page): CmsPage
    {
        $page->update([
            'status'       => 'published',
            'published_at' => $page->published_at ?? now(),
        ]);

        $this->bustCache($page);

        return $page->refresh();
    }

    public function delete(CmsPage $page): void
    {
        $page->delete();
        $this->bustCache($page);
    }

    private function assertUniqueType(string $type): void
    {
        if ($type === 'custom') {
            return;
        }

        if (CmsPage::where('type', $type)->exists()) {
            throw new DuplicatePageTypeException(
                "A page of type '{$type}' already exists for this site."
            );
        }
    }

    private function bustCache(CmsPage $page): void
    {
        Cache::forget("cms:page:{$page->company_id}:{$page->slug}");
        Cache::forget("cms:sitemap:{$page->company_id}");
    }
}
```

---

## Policy

One policy class per model. Registered via `#[UsePolicy]` attribute on the model.

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CmsPage;
use App\Models\CmsUser;

final class CmsPagePolicy
{
    public function viewAny(CmsUser $user): bool
    {
        return true; // all authenticated users can list pages
    }

    public function create(CmsUser $user): bool
    {
        return in_array($user->role, ['admin', 'editor'], true);
    }

    public function update(CmsUser $user, CmsPage $page): bool
    {
        return in_array($user->role, ['admin', 'editor'], true);
    }

    public function publish(CmsUser $user, CmsPage $page): bool
    {
        return $user->role === 'admin';
    }

    public function delete(CmsUser $user, CmsPage $page): bool
    {
        return $user->role === 'admin';
    }
}
```

Model:
```php
#[UsePolicy(CmsPagePolicy::class)]
final class CmsPage extends Model
{
    use HasUlids, HasCompanyScope, SoftDeletes;
}
```

---

## Full Request Lifecycle

```
HTTP POST /admin/pages
    │
    ├─ ResolveTenant middleware  → sets app('current_company')
    ├─ auth middleware           → verifies cms guard session
    │
    ▼
StorePageRequest::rules()       → validates, 422 on failure
    │
    ▼
PageController::store()
    ├─ abort_unless(...can('create'))   → 403 if unauthorised
    ├─ CreatePageData::fromRequest()    → maps validated input to DTO
    └─ PageService::create()
            ├─ assertUniqueType()       → throws DuplicatePageTypeException
            ├─ CmsPage::create()        → HasCompanyScope auto-sets company_id
            └─ bustCache()
    │
    ▼
redirect()->route('admin.pages.index')
    ->with(['alert' => 'Page created.', 'type' => 'success'])
```

---

## Size Limits

| Layer | Max lines | Reason to split |
|-------|-----------|----------------|
| Controller | 150 | More than ~8 actions → extract a sub-controller |
| Service | 250 | Split by concern: `PageService` + `PagePublishService` |
| Form Request | 80 | Large rule sets → extract validation methods |
| DTO | 50 | Large DTOs → usually a sign the action is doing too much |
| Policy | 60 | Simple boolean checks only — no queries in policies |

---

## Public Site Controllers

Public controllers follow the same pattern but simpler — no authorization (public routes), read-only, always cache-first:

```php
final class PublicPageController
{
    public function __construct(
        private readonly PageService   $pages,
        private readonly SeoMetaService $seo,
    ) {}

    public function show(string $slug): Response|NotFoundHttpException
    {
        $company = app('current_company');

        $page = Cache::remember(
            "cms:page:{$company->id}:{$slug}",
            300,
            fn () => $this->pages->findBySlug($company->id, $slug),
        );

        abort_if($page === null || $page->status !== 'published', 404);

        return response()->view('themes.' . $company->theme . '.pages.show', [
            'page' => $page,
            'seo'  => $this->seo->forPage($page, $company),
        ]);
    }
}
```
