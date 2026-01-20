<?php

use App\Models\Source;
use App\Rules\GitHubRepoExists;
use App\Services\GitHubService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('passes validation when repo exists in database without calling GitHub API', function () {
    // Create a source in the database
    Source::factory()->create([
        'name' => 'laravel/framework',
    ]);

    // Mock should NOT be called since repo exists in DB
    $mockService = Mockery::mock(GitHubService::class);
    $mockService->shouldNotReceive('repoExistsByUrl');

    app()->instance(GitHubService::class, $mockService);

    $rule = new GitHubRepoExists;
    $failed = false;

    $rule->validate('source_urls.0', 'laravel/framework', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('passes validation when repo exists in database with different URL format', function () {
    Source::factory()->create([
        'name' => 'laravel/framework',
    ]);

    $mockService = Mockery::mock(GitHubService::class);
    $mockService->shouldNotReceive('repoExistsByUrl');

    app()->instance(GitHubService::class, $mockService);

    $rule = new GitHubRepoExists;
    $failed = false;

    // Test with full URL format
    $rule->validate('source_urls.0', 'https://github.com/laravel/framework', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('fails validation when repo does not exist', function () {
    $mockService = Mockery::mock(GitHubService::class);
    $mockService->shouldReceive('repoExistsByUrl')
        ->with('nonexistent/repo')
        ->once()
        ->andReturn(['exists' => false, 'error' => null]);

    app()->instance(GitHubService::class, $mockService);

    $rule = new GitHubRepoExists;
    $failed = false;
    $errorMessage = '';

    $rule->validate('source_urls.0', 'nonexistent/repo', function ($message) use (&$failed, &$errorMessage) {
        $failed = true;
        $errorMessage = $message;
    });

    expect($failed)->toBeTrue();
    expect($errorMessage)->toContain('nonexistent/repo');
    expect($errorMessage)->toContain('does not exist or is private');
});

it('passes validation when repo exists', function () {
    $mockService = Mockery::mock(GitHubService::class);
    $mockService->shouldReceive('repoExistsByUrl')
        ->with('laravel/framework')
        ->once()
        ->andReturn(['exists' => true, 'error' => null]);

    app()->instance(GitHubService::class, $mockService);

    $rule = new GitHubRepoExists;
    $failed = false;

    $rule->validate('source_urls.0', 'laravel/framework', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('fails validation when rate limited but shows user-friendly message', function () {
    $mockService = Mockery::mock(GitHubService::class);
    $mockService->shouldReceive('repoExistsByUrl')
        ->with('some/repo')
        ->once()
        ->andReturn(['exists' => false, 'error' => 'API rate limit exceeded']);

    app()->instance(GitHubService::class, $mockService);

    $rule = new GitHubRepoExists;
    $failed = false;
    $errorMessage = '';

    $rule->validate('source_urls.0', 'some/repo', function ($message) use (&$failed, &$errorMessage) {
        $failed = true;
        $errorMessage = $message;
    });

    // Should fail but with user-friendly message (not the technical error)
    expect($failed)->toBeTrue();
    expect($errorMessage)->toContain('does not exist or is private');
    expect($errorMessage)->not->toContain('rate limit');
});

it('fails validation on any API error but shows user-friendly message', function () {
    $mockService = Mockery::mock(GitHubService::class);
    $mockService->shouldReceive('repoExistsByUrl')
        ->with('some/repo')
        ->once()
        ->andReturn(['exists' => false, 'error' => 'Network error']);

    app()->instance(GitHubService::class, $mockService);

    $rule = new GitHubRepoExists;
    $failed = false;
    $errorMessage = '';

    $rule->validate('source_urls.0', 'some/repo', function ($message) use (&$failed, &$errorMessage) {
        $failed = true;
        $errorMessage = $message;
    });

    // Should fail but with user-friendly message (not the technical error)
    expect($failed)->toBeTrue();
    expect($errorMessage)->toContain('does not exist or is private');
    expect($errorMessage)->not->toContain('Network error');
});
