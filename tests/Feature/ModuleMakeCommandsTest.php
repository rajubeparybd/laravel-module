<?php

use RajuBepary\LaravelModule\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RajuBepary\LaravelModule\Support\ModuleManager;

uses()->group('module-make-commands')->beforeEach(function () {
    $this->modulesPath = config('laravel-module.path', __DIR__.'/../Fixtures/Modules');

    // Clean up any existing test modules from previous runs
    $testModules = glob($this->modulesPath.'/Test*', GLOB_ONLYDIR);
    foreach ($testModules as $modulePath) {
        File::deleteDirectory($modulePath);
    }

    // Prevent module system from trying to load test modules during tests
    $moduleManager = app(ModuleManager::class);

    // Ensure database tables exist
    if (!\Schema::hasTable('modules')) {
        // Create modules table for testing
        \Schema::create('modules', function ($table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('version')->default('1.0.0');
            $table->boolean('active')->default(false);
            $table->timestamps();
        });
    }
});

afterEach(function () {
    // Cleanup any created test modules
});

test('creates controller in module', function () {
    $moduleName = 'TestShop';
    $controllerName = 'ProductController';
    $modulePath = $this->modulesPath.'/'.$moduleName;
    $controllerPath = $modulePath.'/Http/Controllers/'.$controllerName.'.php';

    // Create test module structure
    createTestModule($moduleName);

    // Run the command
    $this->artisan('lm:make:controller', [
        'name' => 'ProductController',
        '--module' => 'TestShop',
    ])
        ->assertExitCode(0);

    // Assert file was created
    expect(file_exists($controllerPath))->toBeTrue();

    // Assert file content contains expected elements
    $content = File::get($controllerPath);
    expect($content)->toContain('namespace Modules\\TestShop\\Http\\Controllers;');
    expect($content)->toContain('class ProductController extends Controller');
    expect($content)->toContain('public function index(): View');

    // Cleanup
    cleanupTestModule($moduleName);
});

test('creates api controller with json response types', function () {
    $moduleName = 'TestApi';
    $controllerName = 'ApiController';
    $modulePath = $this->modulesPath.'/'.$moduleName;
    $controllerPath = $modulePath.'/Http/Controllers/'.$controllerName.'.php';

    createTestModule($moduleName);

    $this->artisan('lm:make:controller', [
        'name' => 'ApiController',
        '--module' => 'TestApi',
        '--api' => true,
    ])
        ->assertExitCode(0);

    expect(file_exists($controllerPath))->toBeTrue();

    $content = File::get($controllerPath);
    expect($content)->toContain('public function index(): JsonResponse');
    expect($content)->toContain('public function store(Request $request): JsonResponse');

    cleanupTestModule($moduleName);
});

test('creates model with guarded attribute', function () {
    $moduleName = 'TestModels';
    $modelName = 'Product';
    $modulePath = $this->modulesPath.'/'.$moduleName;
    $modelPath = $modulePath.'/Models/'.$modelName.'.php';

    createTestModule($moduleName);

    $this->artisan('lm:make:model', [
        'name' => 'Product',
        '--module' => 'TestModels',
    ])
        ->assertExitCode(0);

    expect(file_exists($modelPath))->toBeTrue();

    $content = File::get($modelPath);
    expect($content)->toContain('namespace Modules\\TestModels\\Models;');
    expect($content)->toContain('use Illuminate\Database\Eloquent\Attributes\Guarded;');
    expect($content)->toContain('#[Guarded([\'id\'])]');
    expect($content)->toContain('class Product extends Model');
    expect($content)->toContain('protected function casts(): array');

    cleanupTestModule($moduleName);
});

test('creates form request with validation rules', function () {
    $moduleName = 'TestRequests';
    $requestName = 'StoreProductRequest';
    $modulePath = $this->modulesPath.'/'.$moduleName;
    $requestPath = $modulePath.'/Http/Requests/'.$requestName.'.php';

    createTestModule($moduleName);

    $this->artisan('lm:make:request', [
        'name' => 'StoreProductRequest',
        '--module' => 'TestRequests',
    ])
        ->assertExitCode(0);

    expect(file_exists($requestPath))->toBeTrue();

    $content = File::get($requestPath);
    expect($content)->toContain('namespace Modules\\TestRequests\\Http\\Requests;');
    expect($content)->toContain('class StoreProductRequest extends FormRequest');
    expect($content)->toContain('public function authorize(): bool');
    expect($content)->toContain('public function rules(): array');

    cleanupTestModule($moduleName);
});

test('creates middleware with proper types', function () {
    $moduleName = 'TestMiddleware';
    $middlewareName = 'CheckAuth';
    $modulePath = $this->modulesPath.'/'.$moduleName;
    $middlewarePath = $modulePath.'/Http/Middleware/'.$middlewareName.'.php';

    createTestModule($moduleName);

    $this->artisan('lm:make:middleware', [
        'name' => 'CheckAuth',
        '--module' => 'TestMiddleware',
    ])
        ->assertExitCode(0);

    expect(file_exists($middlewarePath))->toBeTrue();

    $content = File::get($middlewarePath);
    expect($content)->toContain('namespace Modules\\TestMiddleware\\Http\\Middleware;');
    expect($content)->toContain('use Symfony\Component\HttpFoundation\Response;');
    expect($content)->toContain('public function handle(Request $request, Closure $next): Response');
    expect($content)->toContain('public function terminate(Request $request, Response $response): void');

    cleanupTestModule($moduleName);
});

test('creates migration with proper timestamp', function () {
    $moduleName = 'TestMigrations';
    $modulePath = $this->modulesPath.'/'.$moduleName;
    $migrationDir = $modulePath.'/database/migrations';

    createTestModule($moduleName);

    $this->artisan('lm:make:migration', [
        'name' => 'create_products_table',
        '--module' => 'TestMigrations',
    ])
        ->assertExitCode(0);

    // Find the created migration file - the pattern might be different
    $files = File::glob($migrationDir.'/*.php');
    expect(count($files))->toBeGreaterThan(0);

    // Find the migration file that contains our table name
    $migrationPath = null;
    foreach ($files as $file) {
        $content = File::get($file);
        if (str_contains($content, 'create_products_table')) {
            $migrationPath = $file;
            break;
        }
    }

    expect($migrationPath)->not->toBeNull();

    $content = File::get($migrationPath);
    expect($content)->toContain('return new class extends Migration');
    expect($content)->toContain('Schema::create(\'create_products_table\'');
    expect($content)->toContain('public function up(): void');
    expect($content)->toContain('public function down(): void');

    cleanupTestModule($moduleName);
});

test('creates seeder with run method', function () {
    $moduleName = 'TestSeeders';
    $seederName = 'ProductSeeder';
    $modulePath = $this->modulesPath.'/'.$moduleName;
    $seederPath = $modulePath.'/database/seeders/'.$seederName.'.php';

    createTestModule($moduleName);

    $this->artisan('lm:make:seeder', [
        'name' => 'ProductSeeder',
        '--module' => 'TestSeeders',
    ])
        ->assertExitCode(0);

    expect(file_exists($seederPath))->toBeTrue();

    $content = File::get($seederPath);
    expect($content)->toContain('namespace Modules\\TestSeeders\\database\\seeders;');
    expect($content)->toContain('class ProductSeeder extends Seeder');
    expect($content)->toContain('public function run(): void');
    expect($content)->toContain('public function truncate(): void');

    cleanupTestModule($moduleName);
});

test('fails when module does not exist', function () {
    try {
        $this->artisan('lm:make:controller', [
            'name' => 'TestController',
            '--module' => 'NonExistentModule',
        ]);
        $this->fail('Expected RuntimeException was not thrown');
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->toBe('Module [NonExistentModule] does not exist.');
    }
});

test('fails when file already exists without force', function () {
    $moduleName = 'TestDuplicate';
    $controllerName = 'DuplicateController';
    $modulePath = $this->modulesPath.'/'.$moduleName;
    $controllerPath = $modulePath.'/Http/Controllers/'.$controllerName.'.php';

    createTestModule($moduleName);

    // Create controller first time
    $this->artisan('lm:make:controller', [
        'name' => 'DuplicateController',
        '--module' => 'TestDuplicate',
    ])
        ->assertExitCode(0);

    // Try to create again without --force
    $this->artisan('lm:make:controller', [
        'name' => 'DuplicateController',
        '--module' => 'TestDuplicate',
    ])
        ->assertExitCode(1);

    // Create again with --force
    $this->artisan('lm:make:controller', [
        'name' => 'DuplicateController',
        '--module' => 'TestDuplicate',
        '--force' => true,
    ])
        ->assertExitCode(0);

    cleanupTestModule($moduleName);
});

// Helper functions
function createTestModule(string $moduleName): void
{
    $modulesPath = config('laravel-module.path', __DIR__.'/../Fixtures/Modules');
    $modulePath = $modulesPath.'/'.$moduleName;

    // Create module directory structure
    File::makeDirectory($modulePath.'/Http/Controllers', 0755, true);
    File::makeDirectory($modulePath.'/Models', 0755, true);
    File::makeDirectory($modulePath.'/Http/Requests', 0755, true);
    File::makeDirectory($modulePath.'/Http/Middleware', 0755, true);
    File::makeDirectory($modulePath.'/database/migrations', 0755, true);
    File::makeDirectory($modulePath.'/database/seeders', 0755, true);

    // Create module.json
    $moduleJson = [
        'id' => Str::snake($moduleName, '-'),
        'name' => $moduleName,
        'description' => 'Test module',
        'version' => '1.0.0',
        'author' => 'Test',
        'provider' => "Modules\\{$moduleName}\\{$moduleName}ServiceProvider",
        'requires' => [],
        'protected' => false,
    ];

    File::put(
        $modulePath.'/module.json',
        json_encode($moduleJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    // Create Service Provider
    $providerStub = "<?php\n\nnamespace Modules\\{$moduleName};\n\nuse Illuminate\Support\ServiceProvider;\n\nclass {$moduleName}ServiceProvider extends ServiceProvider\n{\n    public function register(): void\n    {\n        //\n    }\n\n    public function boot(): void\n    {\n        //\n    }\n}";

    File::put($modulePath."/{$moduleName}ServiceProvider.php", $providerStub);
}

function cleanupTestModule(string $moduleName): void
{
    $modulesPath = config('laravel-module.path', __DIR__.'/../Fixtures/Modules');
    $modulePath = $modulesPath.'/'.$moduleName;

    if (File::exists($modulePath)) {
        File::deleteDirectory($modulePath);
    }
}
