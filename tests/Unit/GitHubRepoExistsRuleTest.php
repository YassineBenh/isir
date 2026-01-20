<?php

use App\Rules\GitHubRepoExists;
use App\Services\GitHubService;

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
