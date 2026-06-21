# Quiz Online — Phase A (Foundation) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the non-real-time foundation of the Kahoot-style quiz app — host authentication and full quiz/question/option management — as working, fully tested software.

**Architecture:** Standard Laravel server-rendered app. Eloquent models `Quiz → Question → Option` (cascading deletes). Manual session auth using Laravel's built-in `Auth` (no Breeze). Resource controllers + Blade views styled with the existing Tailwind v4 setup. Ownership enforced by a `QuizPolicy`.

**Tech Stack:** Laravel 13, PHP 8.x, Pest 4 (feature tests on in-memory SQLite), Tailwind v4 + Vite, MySQL (dev). No JavaScript framework — a small vanilla JS snippet handles dynamic option rows in the editor.

## Global Constraints

- **Spec:** `docs/superpowers/specs/2026-06-19-quiz-online-kahoot-design.md`. This plan covers **Phase A only**.
- **No new dependencies in Phase A.** Reverb/Echo/pusher-js are added in Phase B, not here.
- **Git:** Do NOT run `git commit` or `git push` unless the user explicitly authorizes it. The "Commit" steps below are checkpoints — stage/prepare only, and commit when instructed.
- **PHP style:** curly braces on all control structures; explicit return types and param type hints on every method; constructor property promotion where applicable. Run `vendor/bin/pint --dirty --format agent` before finalizing each task.
- **Testing:** feature tests run on SQLite `:memory:` (already configured in `phpunit.xml`). Use factories. Run with `php artisan test --compact`.
- **DB naming:** model `Quiz` maps to table `quizzes` (Laravel pluralization handles this — do not set `$table`).
- **Make files via Artisan** (`php artisan make:...`) then edit, per project conventions.

---

## File Structure

**Models / data**
- `app/Models/Quiz.php` — quiz entity; belongs to user, has many questions.
- `app/Models/Question.php` — question entity; belongs to quiz, has many options.
- `app/Models/Option.php` — answer option; belongs to question.
- `app/Models/User.php` *(modify)* — add `quizzes()` relationship.
- `database/migrations/*_create_quizzes_table.php`
- `database/migrations/*_create_questions_table.php`
- `database/migrations/*_create_options_table.php`
- `database/factories/{Quiz,Question,Option}Factory.php`
- `database/seeders/DatabaseSeeder.php` *(modify)* — demo host + sample quiz.

**Auth**
- `app/Http/Controllers/Auth/RegisteredUserController.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `resources/views/auth/login.blade.php`, `resources/views/auth/register.blade.php`

**Quiz management**
- `app/Http/Controllers/Controller.php` *(modify)* — add `AuthorizesRequests` trait.
- `app/Http/Controllers/QuizController.php`
- `app/Http/Controllers/QuestionController.php`
- `app/Policies/QuizPolicy.php`
- `resources/views/quizzes/index.blade.php` (dashboard)
- `resources/views/quizzes/create.blade.php`
- `resources/views/quizzes/edit.blade.php` (quiz settings + question editor)

**Shared**
- `resources/views/layouts/app.blade.php` — base layout with `@vite`.
- `resources/views/welcome.blade.php` *(modify)* — landing with login/register links.
- `routes/web.php` *(modify)* — all routes.

---

## Task 1: Models, Migrations, Factories

**Files:**
- Create: migrations for `quizzes`, `questions`, `options`
- Create: `app/Models/Quiz.php`, `app/Models/Question.php`, `app/Models/Option.php`
- Create: `database/factories/QuizFactory.php`, `QuestionFactory.php`, `OptionFactory.php`
- Modify: `app/Models/User.php` (add `quizzes()`)
- Modify: `tests/Pest.php` (enable `RefreshDatabase`)
- Test: `tests/Feature/Models/QuizStructureTest.php`

**Interfaces:**
- Produces:
  - `Quiz`: `fillable [user_id, title, description, is_published]`; `is_published` cast bool; `user(): BelongsTo`; `questions(): HasMany` (ordered by `position`).
  - `Question`: `fillable [quiz_id, question_text, time_limit, points_base, position]`; `quiz(): BelongsTo`; `options(): HasMany` (ordered by `position`); `correctOption(): HasOne`.
  - `Option`: `fillable [question_id, option_text, is_correct, position]`; `is_correct` cast bool; `question(): BelongsTo`.
  - `User::quizzes(): HasMany`.

- [ ] **Step 1: Enable RefreshDatabase for feature tests**

In `tests/Pest.php`, uncomment/add the trait:

```php
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
```

- [ ] **Step 2: Generate models, migrations, factories**

Run:
```bash
php artisan make:model Quiz -mf --no-interaction
php artisan make:model Question -mf --no-interaction
php artisan make:model Option -mf --no-interaction
```

- [ ] **Step 3: Write the migrations**

`database/migrations/*_create_quizzes_table.php` — `up()`:
```php
Schema::create('quizzes', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('description')->nullable();
    $table->boolean('is_published')->default(false);
    $table->timestamps();
});
```

`*_create_questions_table.php` — `up()`:
```php
Schema::create('questions', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
    $table->string('question_text');
    $table->unsignedInteger('time_limit')->default(20);
    $table->unsignedInteger('points_base')->default(1000);
    $table->unsignedInteger('position')->default(0);
    $table->timestamps();
});
```

`*_create_options_table.php` — `up()`:
```php
Schema::create('options', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('question_id')->constrained()->cascadeOnDelete();
    $table->string('option_text');
    $table->boolean('is_correct')->default(false);
    $table->unsignedInteger('position')->default(0);
    $table->timestamps();
});
```

> Migration order matters: `quizzes` before `questions` before `options`. Artisan timestamps them in creation order from Step 2, so they are already correct.

- [ ] **Step 4: Write the models**

`app/Models/Quiz.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'description', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }
}
```

`app/Models/Question.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Question extends Model
{
    use HasFactory;

    protected $fillable = ['quiz_id', 'question_text', 'time_limit', 'points_base', 'position'];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class)->orderBy('position');
    }

    public function correctOption(): HasOne
    {
        return $this->hasOne(Option::class)->where('is_correct', true);
    }
}
```

`app/Models/Option.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Option extends Model
{
    use HasFactory;

    protected $fillable = ['question_id', 'option_text', 'is_correct', 'position'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
```

Add to `app/Models/User.php` (inside the class):
```php
public function quizzes(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Quiz::class);
}
```

- [ ] **Step 5: Write the factories**

`database/factories/QuizFactory.php` — `definition()`:
```php
return [
    'user_id' => User::factory(),
    'title' => fake()->sentence(3),
    'description' => fake()->sentence(),
    'is_published' => false,
];
```
(Add `use App\Models\User;` at top.)

`database/factories/QuestionFactory.php` — `definition()`:
```php
return [
    'quiz_id' => Quiz::factory(),
    'question_text' => fake()->sentence().'?',
    'time_limit' => 20,
    'points_base' => 1000,
    'position' => 0,
];
```
(Add `use App\Models\Quiz;`.)

`database/factories/OptionFactory.php` — `definition()`:
```php
return [
    'question_id' => Question::factory(),
    'option_text' => fake()->word(),
    'is_correct' => false,
    'position' => 0,
];
```
(Add `use App\Models\Question;`.)

- [ ] **Step 6: Write the failing test**

`tests/Feature/Models/QuizStructureTest.php`:
```php
<?php

use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;

it('belongs to a user and returns questions in position order', function () {
    $quiz = Quiz::factory()->create();
    Question::factory()->for($quiz)->create(['position' => 2]);
    Question::factory()->for($quiz)->create(['position' => 1]);

    expect($quiz->user)->toBeInstanceOf(User::class);
    expect($quiz->questions)->toHaveCount(2);
    expect($quiz->questions->first()->position)->toBe(1);
});

it('exposes the correct option of a question', function () {
    $question = Question::factory()->create();
    Option::factory()->for($question)->create(['is_correct' => false]);
    $correct = Option::factory()->for($question)->create(['is_correct' => true]);

    expect($question->options)->toHaveCount(2);
    expect($question->correctOption->id)->toBe($correct->id);
});

it('cascades deletes from quiz down to options', function () {
    $quiz = Quiz::factory()->create();
    $question = Question::factory()->for($quiz)->create();
    Option::factory()->for($question)->create();

    $quiz->delete();

    expect(Question::count())->toBe(0)
        ->and(Option::count())->toBe(0);
});
```

- [ ] **Step 7: Run tests to verify they fail**

Run: `php artisan test --compact --filter=QuizStructureTest`
Expected: FAIL (tables/relations not migrated yet on first run, or class errors) — then PASS once Steps 3–5 are in place. If you wrote Steps 3–5 first, this confirms green.

- [ ] **Step 8: Run the full migration + test to verify green**

Run: `php artisan test --compact --filter=QuizStructureTest`
Expected: PASS (3 passed).

- [ ] **Step 9: Pint + commit checkpoint**

Run: `vendor/bin/pint --dirty --format agent`
Then (only if the user authorized commits):
```bash
git add app/Models database/migrations database/factories tests/Pest.php tests/Feature/Models
git commit -m "feat: add quiz/question/option models, migrations, factories"
```

---

## Task 2: Host Authentication

**Files:**
- Create: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Create: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Create: `resources/views/layouts/app.blade.php`
- Create: `resources/views/auth/login.blade.php`, `resources/views/auth/register.blade.php`
- Create: `resources/views/dashboard.blade.php` (temporary; replaced in Task 3)
- Modify: `routes/web.php`
- Test: `tests/Feature/Auth/AuthenticationTest.php`

**Interfaces:**
- Consumes: `User` model (default fillable `name, email, password`; `password` cast `hashed`).
- Produces: named routes `register`, `login`, `logout`, `dashboard`. After register/login, redirects to `route('dashboard')`.

- [ ] **Step 1: Write the failing test**

`tests/Feature/Auth/AuthenticationTest.php`:
```php
<?php

use App\Models\User;

it('renders the registration page', function () {
    $this->get('/register')->assertOk();
});

it('registers a new host and logs them in', function () {
    $response = $this->post('/register', [
        'name' => 'Host One',
        'email' => 'host@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
    expect(User::where('email', 'host@example.com')->exists())->toBeTrue();
});

it('renders the login page', function () {
    $this->get('/login')->assertOk();
});

it('authenticates an existing host', function () {
    $user = User::factory()->create(['password' => 'password123']);

    $this->post('/login', ['email' => $user->email, 'password' => 'password123'])
        ->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('logs the host out', function () {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect('/');
    $this->assertGuest();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=AuthenticationTest`
Expected: FAIL ("Route [register] not defined" or 404).

- [ ] **Step 3: Create the controllers**

Run:
```bash
php artisan make:controller Auth/RegisteredUserController --no-interaction
php artisan make:controller Auth/AuthenticatedSessionController --no-interaction
```

`app/Http/Controllers/Auth/RegisteredUserController.php`:
```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($validated);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
```

`app/Http/Controllers/Auth/AuthenticatedSessionController.php`:
```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
```

- [ ] **Step 4: Register routes**

Replace `routes/web.php` with:
```php
<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::view('/dashboard', 'dashboard')->name('dashboard');
});
```

- [ ] **Step 5: Create the layout and views**

`resources/views/layouts/app.blade.php`:
```blade
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quiz Online')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-4xl p-6">
        @if (session('status'))
            <div class="mb-4 rounded bg-green-100 px-4 py-2 text-green-800">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
```

`resources/views/auth/register.blade.php`:
```blade
@extends('layouts.app')
@section('title', 'Daftar')
@section('content')
<h1 class="mb-6 text-2xl font-bold">Daftar sebagai Host</h1>
<form method="POST" action="{{ route('register') }}" class="space-y-4 max-w-sm">
    @csrf
    <div>
        <label class="block text-sm font-medium">Nama</label>
        <input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded border-slate-300" required>
        @error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium">Email</label>
        <input name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded border-slate-300" required>
        @error('email')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium">Password</label>
        <input name="password" type="password" class="mt-1 w-full rounded border-slate-300" required>
        @error('password')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium">Konfirmasi Password</label>
        <input name="password_confirmation" type="password" class="mt-1 w-full rounded border-slate-300" required>
    </div>
    <button class="rounded bg-indigo-600 px-4 py-2 text-white">Daftar</button>
    <a href="{{ route('login') }}" class="ml-2 text-indigo-600">Sudah punya akun?</a>
</form>
@endsection
```

`resources/views/auth/login.blade.php`:
```blade
@extends('layouts.app')
@section('title', 'Login')
@section('content')
<h1 class="mb-6 text-2xl font-bold">Login Host</h1>
<form method="POST" action="{{ route('login') }}" class="space-y-4 max-w-sm">
    @csrf
    <div>
        <label class="block text-sm font-medium">Email</label>
        <input name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded border-slate-300" required>
        @error('email')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium">Password</label>
        <input name="password" type="password" class="mt-1 w-full rounded border-slate-300" required>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember"> Ingat saya</label>
    <button class="rounded bg-indigo-600 px-4 py-2 text-white">Login</button>
    <a href="{{ route('register') }}" class="ml-2 text-indigo-600">Daftar</a>
</form>
@endsection
```

`resources/views/dashboard.blade.php` (temporary placeholder):
```blade
@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<h1 class="text-2xl font-bold">Dashboard</h1>
<form method="POST" action="{{ route('logout') }}">@csrf<button class="mt-4 text-red-600">Logout</button></form>
@endsection
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --compact --filter=AuthenticationTest`
Expected: PASS (6 passed).

- [ ] **Step 7: Pint + commit checkpoint**

Run: `vendor/bin/pint --dirty --format agent`
Then (only if authorized):
```bash
git add app/Http/Controllers/Auth resources/views routes/web.php
git commit -m "feat: add host registration, login, logout"
```

---

## Task 3: Quiz CRUD + Ownership Policy

**Files:**
- Modify: `app/Http/Controllers/Controller.php` (add `AuthorizesRequests`)
- Create: `app/Http/Controllers/QuizController.php`
- Create: `app/Policies/QuizPolicy.php`
- Create: `resources/views/quizzes/index.blade.php`, `create.blade.php`, `edit.blade.php`
- Delete: `resources/views/dashboard.blade.php` (replaced by `quizzes/index`)
- Modify: `routes/web.php`
- Test: `tests/Feature/QuizManagementTest.php`

**Interfaces:**
- Consumes: `User::quizzes()`, `Quiz` model, `dashboard`/`login` routes.
- Produces: routes `dashboard` (→ `QuizController@index`), `quizzes.create`, `quizzes.store`, `quizzes.edit`, `quizzes.update`, `quizzes.destroy`. `QuizPolicy::update(User,Quiz): bool` and `::delete(User,Quiz): bool`. `store` redirects to `quizzes.edit`.

- [ ] **Step 1: Write the failing test**

`tests/Feature/QuizManagementTest.php`:
```php
<?php

use App\Models\Quiz;
use App\Models\User;

it('redirects guests away from the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('shows only the host own quizzes', function () {
    $user = User::factory()->create();
    Quiz::factory()->for($user)->create(['title' => 'Mine']);
    Quiz::factory()->create(['title' => 'Someone Else']);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Mine')
        ->assertDontSee('Someone Else');
});

it('creates a quiz and redirects to the editor', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('quizzes.store'), ['title' => 'My Quiz']);

    $quiz = Quiz::firstWhere('title', 'My Quiz');
    expect($quiz->user_id)->toBe($user->id);
    $response->assertRedirect(route('quizzes.edit', $quiz));
});

it('forbids editing another host quiz', function () {
    $quiz = Quiz::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('quizzes.edit', $quiz))
        ->assertForbidden();
});

it('updates an owned quiz', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->for($user)->create();

    $this->actingAs($user)->put(route('quizzes.update', $quiz), [
        'title' => 'Updated',
        'is_published' => '1',
    ])->assertRedirect();

    expect($quiz->fresh()->title)->toBe('Updated')
        ->and($quiz->fresh()->is_published)->toBeTrue();
});

it('deletes an owned quiz', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->for($user)->create();

    $this->actingAs($user)->delete(route('quizzes.destroy', $quiz))
        ->assertRedirect(route('dashboard'));

    expect(Quiz::count())->toBe(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=QuizManagementTest`
Expected: FAIL ("Route [quizzes.store] not defined").

- [ ] **Step 3: Add AuthorizesRequests to the base controller**

`app/Http/Controllers/Controller.php`:
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;
}
```

- [ ] **Step 4: Create the policy**

Run: `php artisan make:policy QuizPolicy --model=Quiz --no-interaction`

Replace the generated `update` and `delete` methods (remove the others to keep it focused):
```php
<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function update(User $user, Quiz $quiz): bool
    {
        return $user->id === $quiz->user_id;
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->id === $quiz->user_id;
    }
}
```
(Laravel 13 auto-discovers `QuizPolicy` for `Quiz` — no manual registration needed.)

- [ ] **Step 5: Create the controller**

Run: `php artisan make:controller QuizController --no-interaction`

`app/Http/Controllers/QuizController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $quizzes = $request->user()->quizzes()->withCount('questions')->latest()->get();

        return view('quizzes.index', ['quizzes' => $quizzes]);
    }

    public function create(): View
    {
        return view('quizzes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $quiz = $request->user()->quizzes()->create($validated);

        return redirect()->route('quizzes.edit', $quiz);
    }

    public function edit(Quiz $quiz): View
    {
        $this->authorize('update', $quiz);
        $quiz->load('questions.options');

        return view('quizzes.edit', ['quiz' => $quiz]);
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_published' => ['boolean'],
        ]);
        $validated['is_published'] = $request->boolean('is_published');

        $quiz->update($validated);

        return back()->with('status', 'Kuis diperbarui.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);
        $quiz->delete();

        return redirect()->route('dashboard')->with('status', 'Kuis dihapus.');
    }
}
```

- [ ] **Step 6: Update routes**

In `routes/web.php`, inside the existing `Route::middleware('auth')->group(...)`, replace the temporary `Route::view('/dashboard', ...)` line with:
```php
    Route::get('/dashboard', [\App\Http\Controllers\QuizController::class, 'index'])->name('dashboard');
    Route::resource('quizzes', \App\Http\Controllers\QuizController::class)->except(['index', 'show']);
```
Then delete `resources/views/dashboard.blade.php`.

- [ ] **Step 7: Create the views**

`resources/views/quizzes/index.blade.php`:
```blade
@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold">Kuis Saya</h1>
    <div class="flex items-center gap-3">
        <a href="{{ route('quizzes.create') }}" class="rounded bg-indigo-600 px-4 py-2 text-white">Buat Kuis</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm text-slate-500">Logout</button></form>
    </div>
</div>
<ul class="space-y-3">
    @forelse ($quizzes as $quiz)
        <li class="flex items-center justify-between rounded border bg-white p-4">
            <div>
                <p class="font-semibold">{{ $quiz->title }}</p>
                <p class="text-sm text-slate-500">{{ $quiz->questions_count }} soal · {{ $quiz->is_published ? 'Terbit' : 'Draf' }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('quizzes.edit', $quiz) }}" class="text-indigo-600">Edit</a>
                <form method="POST" action="{{ route('quizzes.destroy', $quiz) }}" onsubmit="return confirm('Hapus kuis ini?')">
                    @csrf @method('DELETE')
                    <button class="text-red-600">Hapus</button>
                </form>
            </div>
        </li>
    @empty
        <li class="rounded border border-dashed p-8 text-center text-slate-500">Belum ada kuis. Buat yang pertama!</li>
    @endforelse
</ul>
@endsection
```

`resources/views/quizzes/create.blade.php`:
```blade
@extends('layouts.app')
@section('title', 'Buat Kuis')
@section('content')
<h1 class="mb-6 text-2xl font-bold">Buat Kuis</h1>
<form method="POST" action="{{ route('quizzes.store') }}" class="max-w-lg space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium">Judul</label>
        <input name="title" value="{{ old('title') }}" class="mt-1 w-full rounded border-slate-300" required>
        @error('title')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium">Deskripsi (opsional)</label>
        <textarea name="description" class="mt-1 w-full rounded border-slate-300">{{ old('description') }}</textarea>
    </div>
    <button class="rounded bg-indigo-600 px-4 py-2 text-white">Simpan & Tambah Soal</button>
</form>
@endsection
```

`resources/views/quizzes/edit.blade.php` (quiz settings form only for now; the question editor section is added in Task 4 at the marked placeholder):
```blade
@extends('layouts.app')
@section('title', 'Edit Kuis')
@section('content')
<a href="{{ route('dashboard') }}" class="text-sm text-indigo-600">&larr; Kembali</a>
<h1 class="mb-6 mt-2 text-2xl font-bold">Edit Kuis</h1>

<form method="POST" action="{{ route('quizzes.update', $quiz) }}" class="mb-8 max-w-lg space-y-4">
    @csrf @method('PUT')
    <div>
        <label class="block text-sm font-medium">Judul</label>
        <input name="title" value="{{ old('title', $quiz->title) }}" class="mt-1 w-full rounded border-slate-300" required>
        @error('title')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium">Deskripsi</label>
        <textarea name="description" class="mt-1 w-full rounded border-slate-300">{{ old('description', $quiz->description) }}</textarea>
    </div>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_published" value="1" @checked($quiz->is_published)> Terbitkan
    </label>
    <button class="rounded bg-indigo-600 px-4 py-2 text-white">Simpan Pengaturan</button>
</form>

{{-- QUESTION-EDITOR-PLACEHOLDER (filled in Task 4) --}}
@endsection
```

- [ ] **Step 8: Run tests to verify they pass**

Run: `php artisan test --compact --filter=QuizManagementTest`
Expected: PASS (6 passed). Also run `php artisan test --compact` to confirm no regressions.

- [ ] **Step 9: Pint + commit checkpoint**

Run: `vendor/bin/pint --dirty --format agent`
Then (only if authorized):
```bash
git add app resources/views/quizzes routes/web.php tests/Feature/QuizManagementTest.php
git commit -m "feat: add quiz CRUD with ownership policy"
```

---

## Task 4: Question & Option Editor

**Files:**
- Create: `app/Http/Controllers/QuestionController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/quizzes/edit.blade.php` (replace the `QUESTION-EDITOR-PLACEHOLDER`)
- Test: `tests/Feature/QuestionEditorTest.php`

**Interfaces:**
- Consumes: `Quiz`, `Question`, `Option` models; `QuizPolicy::update`.
- Produces: routes `questions.store` (POST `quizzes/{quiz}/questions`), `questions.update` (PUT `questions/{question}`), `questions.destroy` (DELETE `questions/{question}`). Request shape: `question_text` (string), `time_limit` (int 5–120), `options` (array of 2–4 non-empty strings), `correct` (int index into `options`). Stored: one `Option` per entry, `is_correct = true` only at index `correct`; new question `position = max(position)+1`.

- [ ] **Step 1: Write the failing test**

`tests/Feature/QuestionEditorTest.php`:
```php
<?php

use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;

it('adds a question with options and one correct answer', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->for($user)->create();

    $this->actingAs($user)->post(route('questions.store', $quiz), [
        'question_text' => 'Ibu kota Perancis?',
        'time_limit' => 20,
        'options' => ['Paris', 'London', 'Roma'],
        'correct' => 0,
    ])->assertRedirect();

    $question = $quiz->questions()->first();
    expect($question->options)->toHaveCount(3)
        ->and($question->position)->toBe(1)
        ->and($question->correctOption->option_text)->toBe('Paris');
});

it('requires at least two options', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->for($user)->create();

    $this->actingAs($user)->post(route('questions.store', $quiz), [
        'question_text' => 'Q',
        'time_limit' => 20,
        'options' => ['hanya satu'],
        'correct' => 0,
    ])->assertSessionHasErrors('options');
});

it('rejects a correct index out of range', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->for($user)->create();

    $this->actingAs($user)->post(route('questions.store', $quiz), [
        'question_text' => 'Q',
        'time_limit' => 20,
        'options' => ['a', 'b'],
        'correct' => 5,
    ])->assertSessionHasErrors('correct');
});

it('updates a question and replaces its options', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->for($user)->create();
    $question = Question::factory()->for($quiz)->create();
    Option::factory()->for($question)->count(2)->create();

    $this->actingAs($user)->put(route('questions.update', $question), [
        'question_text' => 'Teks baru',
        'time_limit' => 30,
        'options' => ['x', 'y'],
        'correct' => 1,
    ])->assertRedirect();

    expect($question->fresh()->question_text)->toBe('Teks baru')
        ->and($question->options()->count())->toBe(2)
        ->and($question->fresh()->correctOption->option_text)->toBe('y');
});

it('deletes a question', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->for($user)->create();
    $question = Question::factory()->for($quiz)->create();

    $this->actingAs($user)->delete(route('questions.destroy', $question))->assertRedirect();

    expect($quiz->questions()->count())->toBe(0);
});

it('forbids adding questions to another host quiz', function () {
    $quiz = Quiz::factory()->create();

    $this->actingAs(User::factory()->create())->post(route('questions.store', $quiz), [
        'question_text' => 'Q',
        'time_limit' => 20,
        'options' => ['a', 'b'],
        'correct' => 0,
    ])->assertForbidden();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=QuestionEditorTest`
Expected: FAIL ("Route [questions.store] not defined").

- [ ] **Step 3: Create the controller**

Run: `php artisan make:controller QuestionController --no-interaction`

`app/Http/Controllers/QuestionController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QuestionController extends Controller
{
    public function store(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $validated = $this->validateQuestion($request);

        $question = $quiz->questions()->create([
            'question_text' => $validated['question_text'],
            'time_limit' => $validated['time_limit'],
            'points_base' => 1000,
            'position' => ($quiz->questions()->max('position') ?? 0) + 1,
        ]);

        $this->syncOptions($question, $validated['options'], (int) $validated['correct']);

        return back()->with('status', 'Soal ditambahkan.');
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $this->authorize('update', $question->quiz);

        $validated = $this->validateQuestion($request);

        $question->update([
            'question_text' => $validated['question_text'],
            'time_limit' => $validated['time_limit'],
        ]);

        $this->syncOptions($question, $validated['options'], (int) $validated['correct']);

        return back()->with('status', 'Soal diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('update', $question->quiz);
        $question->delete();

        return back()->with('status', 'Soal dihapus.');
    }

    /**
     * @return array{question_text: string, time_limit: int, options: array<int, string>, correct: int}
     */
    protected function validateQuestion(Request $request): array
    {
        $validated = $request->validate([
            'question_text' => ['required', 'string', 'max:255'],
            'time_limit' => ['required', 'integer', 'min:5', 'max:120'],
            'options' => ['required', 'array', 'min:2', 'max:4'],
            'options.*' => ['required', 'string', 'max:255'],
            'correct' => ['required', 'integer', 'min:0'],
        ]);

        $options = array_values($validated['options']);

        if ((int) $validated['correct'] >= count($options)) {
            throw ValidationException::withMessages([
                'correct' => 'Pilih jawaban benar yang valid.',
            ]);
        }

        $validated['options'] = $options;

        return $validated;
    }

    /**
     * @param  array<int, string>  $options
     */
    protected function syncOptions(Question $question, array $options, int $correct): void
    {
        $question->options()->delete();

        foreach ($options as $index => $text) {
            $question->options()->create([
                'option_text' => $text,
                'is_correct' => $index === $correct,
                'position' => $index,
            ]);
        }
    }
}
```

- [ ] **Step 4: Register routes**

In `routes/web.php`, inside the `Route::middleware('auth')->group(...)`, add:
```php
    Route::post('quizzes/{quiz}/questions', [\App\Http\Controllers\QuestionController::class, 'store'])->name('questions.store');
    Route::put('questions/{question}', [\App\Http\Controllers\QuestionController::class, 'update'])->name('questions.update');
    Route::delete('questions/{question}', [\App\Http\Controllers\QuestionController::class, 'destroy'])->name('questions.destroy');
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact --filter=QuestionEditorTest`
Expected: PASS (6 passed).

- [ ] **Step 6: Build the editor UI**

Replace the `{{-- QUESTION-EDITOR-PLACEHOLDER (filled in Task 4) --}}` line in `resources/views/quizzes/edit.blade.php` with:
```blade
<hr class="my-8">
<h2 class="mb-4 text-xl font-bold">Soal ({{ $quiz->questions->count() }})</h2>

<ul class="mb-8 space-y-3">
    @foreach ($quiz->questions as $question)
        <li class="rounded border bg-white p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold">{{ $question->position }}. {{ $question->question_text }}</p>
                    <ul class="mt-1 text-sm text-slate-600">
                        @foreach ($question->options as $option)
                            <li>{{ $option->is_correct ? '✅' : '◻️' }} {{ $option->option_text }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-1 text-xs text-slate-400">Waktu: {{ $question->time_limit }} dtk</p>
                </div>
                <form method="POST" action="{{ route('questions.destroy', $question) }}" onsubmit="return confirm('Hapus soal?')">
                    @csrf @method('DELETE')
                    <button class="text-sm text-red-600">Hapus</button>
                </form>
            </div>
        </li>
    @endforeach
</ul>

<h3 class="mb-3 font-semibold">Tambah Soal</h3>
<form method="POST" action="{{ route('questions.store', $quiz) }}" class="max-w-lg space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium">Pertanyaan</label>
        <input name="question_text" value="{{ old('question_text') }}" class="mt-1 w-full rounded border-slate-300" required>
        @error('question_text')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium">Batas waktu (detik)</label>
        <input name="time_limit" type="number" min="5" max="120" value="{{ old('time_limit', 20) }}" class="mt-1 w-32 rounded border-slate-300" required>
        @error('time_limit')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div id="options" class="space-y-2">
        <p class="text-sm font-medium">Opsi jawaban (pilih radio = jawaban benar)</p>
        @for ($i = 0; $i < 2; $i++)
            <div class="flex items-center gap-2">
                <input type="radio" name="correct" value="{{ $i }}" {{ $i === 0 ? 'checked' : '' }} required>
                <input name="options[]" class="w-full rounded border-slate-300" placeholder="Opsi {{ $i + 1 }}" required>
            </div>
        @endfor
    </div>
    @error('options')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    @error('correct')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

    <button type="button" id="add-option" class="text-sm text-indigo-600">+ Tambah opsi (maks 4)</button>
    <div>
        <button class="rounded bg-indigo-600 px-4 py-2 text-white">Tambah Soal</button>
    </div>
</form>

<script>
    document.getElementById('add-option').addEventListener('click', function () {
        const container = document.getElementById('options');
        const count = container.querySelectorAll('input[name="options[]"]').length;
        if (count >= 4) { return; }
        const row = document.createElement('div');
        row.className = 'flex items-center gap-2';
        row.innerHTML =
            '<input type="radio" name="correct" value="' + count + '">' +
            '<input name="options[]" class="w-full rounded border-slate-300" placeholder="Opsi ' + (count + 1) + '" required>';
        container.appendChild(row);
    });
</script>
```

- [ ] **Step 7: Verify the full suite is green**

Run: `php artisan test --compact`
Expected: PASS (all tests across Tasks 1–4).

- [ ] **Step 8: Pint + commit checkpoint**

Run: `vendor/bin/pint --dirty --format agent`
Then (only if authorized):
```bash
git add app/Http/Controllers/QuestionController.php routes/web.php resources/views/quizzes/edit.blade.php tests/Feature/QuestionEditorTest.php
git commit -m "feat: add question and option editor"
```

---

## Task 5: Sample Data Seeder + Landing Page

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `resources/views/welcome.blade.php`
- Test: `tests/Feature/DatabaseSeederTest.php`

**Interfaces:**
- Consumes: `User`, `Quiz`, `Question`, `Option` models.
- Produces: a demo host `host@example.com` / password `password` with one published quiz of 2 questions. Landing page links to `login`/`register`/`dashboard`.

- [ ] **Step 1: Write the failing test**

`tests/Feature/DatabaseSeederTest.php`:
```php
<?php

use App\Models\User;

it('seeds a demo host with a sample quiz', function () {
    $this->seed();

    $host = User::firstWhere('email', 'host@example.com');
    expect($host)->not->toBeNull()
        ->and($host->quizzes()->first()->questions()->count())->toBe(2);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DatabaseSeederTest`
Expected: FAIL (no such host).

- [ ] **Step 3: Write the seeder**

`database/seeders/DatabaseSeeder.php` — `run()`:
```php
public function run(): void
{
    $host = User::factory()->create([
        'name' => 'Demo Host',
        'email' => 'host@example.com',
        'password' => 'password',
    ]);

    $quiz = Quiz::factory()->for($host)->create([
        'title' => 'Pengetahuan Umum',
        'is_published' => true,
    ]);

    $questions = [
        ['Ibu kota Indonesia?', ['Jakarta', 'Bandung', 'Surabaya'], 0],
        ['2 + 2 = ?', ['3', '4', '5'], 1],
    ];

    foreach ($questions as $i => [$text, $options, $correct]) {
        $question = $quiz->questions()->create([
            'question_text' => $text,
            'time_limit' => 20,
            'points_base' => 1000,
            'position' => $i + 1,
        ]);

        foreach ($options as $pos => $optionText) {
            $question->options()->create([
                'option_text' => $optionText,
                'is_correct' => $pos === $correct,
                'position' => $pos,
            ]);
        }
    }
}
```
Add at top: `use App\Models\Quiz;` and `use App\Models\User;`.

- [ ] **Step 4: Update the landing page**

`resources/views/welcome.blade.php`:
```blade
@extends('layouts.app')
@section('title', 'Quiz Online')
@section('content')
<div class="py-16 text-center">
    <h1 class="text-4xl font-bold">Quiz Online</h1>
    <p class="mt-2 text-slate-600">Buat kuis, undang peserta, main bareng secara real-time.</p>
    <div class="mt-8 flex justify-center gap-4">
        @auth
            <a href="{{ route('dashboard') }}" class="rounded bg-indigo-600 px-6 py-3 text-white">Ke Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="rounded bg-indigo-600 px-6 py-3 text-white">Login Host</a>
            <a href="{{ route('register') }}" class="rounded border px-6 py-3">Daftar</a>
        @endauth
    </div>
    {{-- Kotak "Masuk PIN" untuk peserta ditambahkan di Phase B --}}
</div>
@endsection
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact`
Expected: PASS (full suite green).

- [ ] **Step 6: Manual smoke check**

Run: `php artisan migrate:fresh --seed` then `composer run dev`. Visit `/`, log in as `host@example.com` / `password`, create/edit a quiz and add a question. Confirm Tailwind styles render (run `npm run build` if not).

- [ ] **Step 7: Pint + commit checkpoint**

Run: `vendor/bin/pint --dirty --format agent`
Then (only if authorized):
```bash
git add database/seeders/DatabaseSeeder.php resources/views/welcome.blade.php tests/Feature/DatabaseSeederTest.php
git commit -m "feat: add sample seeder and landing page"
```

---

## Self-Review (against the spec)

**Spec coverage (Phase A scope only):**
- Model data (`quizzes`, `questions`, `options`, `users` + relations) → Task 1. ✅
- Host auth (register/login/logout) → Task 2. ✅
- Dashboard (list own quizzes, create/edit/delete) → Task 3. ✅
- Quiz editor (title/desc, add/edit/delete questions, 2–4 options, mark correct) → Tasks 3 & 4. ✅
- Ownership enforcement → `QuizPolicy`, Task 3. ✅
- Sample seeder → Task 5. ✅
- Landing page (login/register; PIN box deferred to Phase B per spec) → Task 5. ✅
- **Deferred to Phase B (correctly out of this plan):** `game_sessions`, `participants`, `answers`, PIN join, Reverb/Echo, scoring, leaderboard, live screens. **Deferred to Phase C:** question reorder (drag), podium, final polish.

**Placeholder scan:** No "TBD/TODO" left; the one intentional placeholder (`QUESTION-EDITOR-PLACEHOLDER`) is explicitly replaced in Task 4 Step 6. ✅

**Type consistency:** `validateQuestion()` returns the documented array shape; `syncOptions(Question, array, int)` signature matches both call sites; route names (`dashboard`, `quizzes.*`, `questions.*`) consistent across controllers, routes, views, and tests; `QuizPolicy::update/delete` used in `QuizController` and `QuestionController` (via `$question->quiz`). ✅

---

## Phases B & C (outline — each gets its own detailed plan after Phase A ships)

**Phase B — Live Game (adds Reverb/Echo/pusher-js):**
1. Add deps + configure broadcasting/Reverb; set up Laravel Echo in `resources/js`.
2. Migrations + models: `game_sessions` (pin, status, current_question_index, current_question_started_at), `participants` (nickname, session_token, total_score), `answers` (selected_option_id, is_correct, response_time_ms, points_awarded; unique per participant+question).
3. Host: create session from a quiz (generate unique PIN), lobby screen, Start/Next actions driving the state machine.
4. Participant: join by PIN + nickname (guard duplicate nickname, joined-after-start, finished/invalid PIN; rejoin via `session_token` cookie).
5. Broadcast events on `game.{sessionId}`: `ParticipantJoined`, `GameStarted`, `QuestionStarted`, `AnswerReceived`, `QuestionEnded`, `GameEnded`.
6. Server-authoritative scoring: `points = round(points_base × (1 − (response_time_ms / time_limit_ms) / 2))`; per-game leaderboard.
7. Question + answer screens (host projector view & participant device view) wired to Echo.

**Phase C — Polish:**
- Final podium/results screens, answer-distribution bar chart, edge-case hardening, optional Pest 4 browser happy-path test, Tailwind styling pass, question drag-reorder.

---
```
