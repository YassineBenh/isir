<?php

use App\Services\GitHubService;
use Github\Exception\RuntimeException;
use GrahamCampbell\GitHub\GitHubManager;

beforeEach(function () {
    $this->githubManager = Mockery::mock(GitHubManager::class);
    $this->service = new GitHubService($this->githubManager);
});

describe('repoExists', function () {
    it('returns true when repository exists', function () {
        $repoApi = Mockery::mock();
        $repoApi->shouldReceive('show')
            ->with('laravel', 'framework')
            ->once()
            ->andReturn(['id' => 1, 'name' => 'framework']);

        $this->githubManager->shouldReceive('repo')
            ->once()
            ->andReturn($repoApi);

        $result = $this->service->repoExists('laravel', 'framework');

        expect($result)->toBe(['exists' => true, 'error' => null]);
    });

    it('returns false when repository does not exist', function () {
        $repoApi = Mockery::mock();
        $repoApi->shouldReceive('show')
            ->with('nonexistent', 'repo')
            ->once()
            ->andThrow(new RuntimeException('Not Found', 404));

        $this->githubManager->shouldReceive('repo')
            ->once()
            ->andReturn($repoApi);

        $result = $this->service->repoExists('nonexistent', 'repo');

        expect($result)->toBe(['exists' => false, 'error' => null]);
    });

    it('returns error message when rate limited', function () {
        $repoApi = Mockery::mock();
        $repoApi->shouldReceive('show')
            ->with('some', 'repo')
            ->once()
            ->andThrow(new RuntimeException('API rate limit exceeded', 403));

        $this->githubManager->shouldReceive('repo')
            ->once()
            ->andReturn($repoApi);

        $result = $this->service->repoExists('some', 'repo');

        expect($result['exists'])->toBeFalse();
        expect($result['error'])->toBe('API rate limit exceeded');
    });
});

describe('repoExistsByUrl', function () {
    it('parses owner/repo format correctly', function () {
        $repoApi = Mockery::mock();
        $repoApi->shouldReceive('show')
            ->with('laravel', 'framework')
            ->once()
            ->andReturn(['id' => 1]);

        $this->githubManager->shouldReceive('repo')
            ->once()
            ->andReturn($repoApi);

        $result = $this->service->repoExistsByUrl('laravel/framework');

        expect($result['exists'])->toBeTrue();
    });

    it('parses full GitHub URL correctly', function () {
        $repoApi = Mockery::mock();
        $repoApi->shouldReceive('show')
            ->with('laravel', 'framework')
            ->once()
            ->andReturn(['id' => 1]);

        $this->githubManager->shouldReceive('repo')
            ->once()
            ->andReturn($repoApi);

        $result = $this->service->repoExistsByUrl('https://github.com/laravel/framework');

        expect($result['exists'])->toBeTrue();
    });

    it('returns error for invalid URL format', function () {
        $result = $this->service->repoExistsByUrl('invalid-url');

        expect($result)->toBe([
            'exists' => false,
            'error' => 'Invalid GitHub repository URL format.',
        ]);
    });
});

describe('validateRepositories', function () {
    it('returns valid when all repos exist', function () {
        $repoApi = Mockery::mock();
        $repoApi->shouldReceive('show')
            ->with('laravel', 'framework')
            ->once()
            ->andReturn(['id' => 1]);
        $repoApi->shouldReceive('show')
            ->with('laravel', 'laravel')
            ->once()
            ->andReturn(['id' => 2]);

        $this->githubManager->shouldReceive('repo')
            ->twice()
            ->andReturn($repoApi);

        $result = $this->service->validateRepositories([
            'laravel/framework',
            'laravel/laravel',
        ]);

        expect($result['valid'])->toBeTrue();
        expect($result['errors'])->toBeEmpty();
    });

    it('returns errors for non-existent repos', function () {
        $repoApi = Mockery::mock();
        $repoApi->shouldReceive('show')
            ->with('laravel', 'framework')
            ->once()
            ->andReturn(['id' => 1]);
        $repoApi->shouldReceive('show')
            ->with('nonexistent', 'repo')
            ->once()
            ->andThrow(new RuntimeException('Not Found', 404));

        $this->githubManager->shouldReceive('repo')
            ->twice()
            ->andReturn($repoApi);

        $result = $this->service->validateRepositories([
            'laravel/framework',
            'nonexistent/repo',
        ]);

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toHaveKey('source_urls.1');
        expect($result['errors']['source_urls.1'])->toContain('nonexistent/repo');
    });
});
