<?php

use Illuminate\Routing\Route;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Artisan;
use Laravel\Folio\FolioManager;
use Symfony\Component\Console\Output\BufferedOutput;

$routes = new class
{
    public function all()
    {
        return collect(app('router')->getRoutes()->getRoutes())
            ->map(fn (Route $route) => $this->getRoute($route))
            ->merge($this->getFolioRoutes());
    }

    protected function getFolioRoutes()
    {
        try {
            $output = new BufferedOutput;

            Artisan::call('folio:list', ['--json' => true], $output);

            $mountPaths = collect(app(FolioManager::class)->mountPaths());

            return collect(json_decode($output->fetch(), true))->map(fn ($route) => $this->getFolioRoute($route, $mountPaths));
        } catch (Exception|Throwable $e) {
            return [];
        }
    }

    protected function getFolioRoute($route, $mountPaths)
    {
        if ($mountPaths->count() === 1) {
            $mountPath = $mountPaths[0];
        } else {
            $mountPath = $mountPaths->first(fn ($mp) => file_exists($mp->path . DIRECTORY_SEPARATOR . $route['view']));
        }

        $path = $route['view'];

        if ($mountPath) {
            $path = $mountPath->path . DIRECTORY_SEPARATOR . $path;
        }

        return [
            'method'     => $route['method'],
            'uri'        => $route['uri'],
            'name'       => $route['name'],
            'action'     => null,
            'parameters' => [],
            'filename'   => LspHelper::relativePath($path),
            'line'       => 0,
        ];
    }

    protected function getRoute(Route $route)
    {
        try {
            $reflection = $this->getRouteReflection($route);
        } catch (Throwable $e) {
            $reflection = null;
        }

        return [
            'method' => collect($route->methods())
                ->filter(fn ($method) => $method !== 'HEAD')
                ->implode('|'),
            'uri'        => $route->uri(),
            'name'       => $route->getName(),
            'action'     => $route->getActionName(),
            'parameters' => $route->parameterNames(),
            'filename'   => $reflection ? LspHelper::relativePath($reflection->getFileName()) : null,
            'line'       => $reflection ? $reflection->getStartLine() : null,
            'livewire'   => $this->getLivewireView($route),
        ];
    }

    protected function getLivewireView(Route $route): ?string
    {
        if ($route->getActionName() !== 'Livewire\Features\SupportRouting\LivewirePageController') {
            return null;
        }

        return $route->defaults['_livewire_component'] ?? null;
    }

    protected function getRouteReflection(Route $route)
    {
        $action = $route->getActionName();

        if ($action === 'Closure') {
            return new ReflectionFunction($route->getAction()['uses']);
        }

        if (strpos($action, '@') === false) {
            return new ReflectionClass($action);
        }

        [$controller, $method] = explode('@', $action, 2);

        try {
            return new ReflectionMethod($controller, $method);
        } catch (ReflectionException $e) {
            $generator = app(UrlGenerator::class);
            $namespace = method_exists($generator, 'getRootControllerNamespace')
                ? $generator->getRootControllerNamespace()
                : null;
            $namespace = $namespace ?? (app()->getNamespace() . 'Http\Controllers');

            return new ReflectionMethod($namespace . '\\' . ltrim($controller, '\\'), $method);
        }
    }
};

echo $routes->all()->toJson();
