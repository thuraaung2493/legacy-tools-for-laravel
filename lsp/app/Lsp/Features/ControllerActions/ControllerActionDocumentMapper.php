<?php

declare(strict_types=1);

namespace App\Lsp\Features\ControllerActions;

use App\Lsp\Detection\AutocompleteArgument;
use App\Lsp\Detection\DetectedArgument;
use App\Lsp\Detection\Pattern;
use App\Lsp\Features\Support\DocumentMapper;
use App\Lsp\Project;
use Illuminate\Support\Collection;

class ControllerActionDocumentMapper extends DocumentMapper
{
    /**
     * Create a new controller action document mapper instance.
     */
    public function __construct(
        protected Project $project,
    ) {}

    /**
     * Get controller action detection patterns.
     *
     * @return array<int, Pattern>
     */
    protected function patterns(): array
    {
        return [
            Pattern::method(method: ['get', 'post', 'patch', 'put', 'delete', 'options'], class: Pattern::contract('Routing\\Registrar'), argument: 1),
            Pattern::method(method: 'match', class: Pattern::contract('Routing\\Registrar'), argument: 2),
            Pattern::method(method: ['get', 'post', 'patch', 'put', 'delete', 'options', 'any'], class: Pattern::facade('Route'), argument: 1),
            Pattern::method(method: ['match', 'addRoute', 'newRoute'], class: Pattern::facade('Route'), argument: 2),
            Pattern::method(method: 'fallback', class: Pattern::facade('Route'), argument: 0),
        ];
    }

    /**
     * Convert the given argument to document links.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function toLinks(DetectedArgument $argument): array
    {
        $routes = $this->matchingRoutes($argument);
        $route = $routes->first();

        if ($routes->count() !== 1 || !is_string($route['filename'] ?? null)) {
            return [];
        }

        return [
            $this->project->link(
                $argument->range(),
                $route['filename'],
                is_numeric($route['line'] ?? null) ? (int) $route['line'] : null,
            ),
        ];
    }

    /**
     * Convert the given argument to diagnostics.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function toDiagnostics(DetectedArgument $argument): array
    {
        $value = $argument->stringValue();

        if ($value === null || !str_contains($value, '@') || $this->matchingRoutes($argument)->isNotEmpty()) {
            return [];
        }

        return [[
            'range'    => $argument->range(),
            'severity' => 2,
            'source'   => 'Legacy Tools for Laravel',
            'code'     => 'controllerAction',
            'message'  => "Controller/Method [{$value}] not found.",
        ]];
    }

    /**
     * Convert the given argument to completion items.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function toCompletions(AutocompleteArgument $argument): array
    {
        if ($argument->isArray()) {
            return [];
        }

        return $this->controllers()
            ->filter(fn (mixed $controller): bool => is_string($controller) && $controller !== '')
            ->map(fn (string $controller): array => [
                'label'    => $controller,
                'kind'     => 13,
                'textEdit' => [
                    'range'   => $argument->replacementRange(),
                    'newText' => $controller,
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * Match registered routes for an absolute or namespace-relative action.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function matchingRoutes(DetectedArgument $argument): Collection
    {
        $value = $argument->stringValue();

        if ($value === null || $value === '') {
            return collect();
        }

        $routes = $this->routes()->whereNotNull('action');
        $action = ltrim($value, '\\');
        $exact = $routes->first(fn (array $route): bool => ltrim($route['action'], '\\') === $action);

        if ($exact !== null) {
            return collect([$exact]);
        }

        if (str_starts_with($value, '\\')) {
            return collect();
        }

        $arguments = $argument->item()['arguments']['children'];
        $uriParts = $arguments[$argument->argumentIndex() - 1]['children'] ?? [];
        $uriArgument = count($uriParts) === 1 ? $uriParts[0] : [];
        $uri = ($uriArgument['type'] ?? null) === 'string' && !($uriArgument['interpolated'] ?? false)
            ? trim($uriArgument['value'], '/')
            : '';

        return $routes
            ->filter(function (array $route) use ($action, $uri): bool {
                if (!str_ends_with($route['action'], '\\' . $action)) {
                    return false;
                }

                $registeredUri = trim($route['uri'], '/');

                return $uri === '' || $registeredUri === $uri || str_ends_with($registeredUri, '/' . $uri);
            })
            ->unique('action')
            ->values();
    }

    /**
     * Get the available routes.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function routes(): Collection
    {
        return $this->project->index->routes();
    }

    /**
     * Get the available controllers.
     *
     * @return Collection<int, string>
     */
    protected function controllers(): Collection
    {
        return $this->project->index->controllers();
    }
}
