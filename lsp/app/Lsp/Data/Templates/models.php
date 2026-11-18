<?php

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\DocBlockFactory;
use Symfony\Component\Console\Output\BufferedOutput;

if (class_exists('\phpDocumentor\Reflection\DocBlockFactory')) {
    $factory = DocBlockFactory::createInstance();
} else {
    $factory = null;
}

$docblocks = new class($factory)
{
    protected $factory;

    public function __construct($factory)
    {
        $this->factory = $factory;
    }

    public function forMethod($method)
    {
        if ($this->factory !== null && $method->getDocComment()) {
            $docblock = $this->factory->create($method->getDocComment());
            $params = collect($docblock->getTagsByName('param'))->map(fn ($p) => (string) $p)->all();
            $return = $docblock->getTagsByName('return')[0] ?? null;
            $return = $return !== null ? (string) $return : null;

            return [$params, $return];
        }

        $params = collect($method->getParameters())
            ->map(function (ReflectionParameter $param) {
                $type = $param->getType();
                $types = $type ? [$type] : [];

                if ($type && method_exists($type, 'getTypes')) {
                    $types = $type->getTypes();
                }

                $types = collect($types)
                    ->filter()
                    ->values()
                    ->map(fn ($type) => $type instanceof ReflectionNamedType ? $type->getName() : (string) $type);

                return trim($types->join('|') . ' $' . $param->getName());
            })
            ->all();

        $returnType = $method->getReturnType();
        $return = $returnType instanceof ReflectionNamedType ? $returnType->getName() : ($returnType ? (string) $returnType : null);

        return [$params, $return];
    }
};

$models = new class($factory)
{
    protected $factory;

    public function __construct($factory)
    {
        $this->factory = $factory;
    }

    public function all()
    {
        return collect(LspHelper::appModels())
            ->filter(fn ($class) => !in_array($class, [Pivot::class, User::class]))
            ->values()
            ->flatMap(fn (string $className) => $this->getInfo($className))
            ->filter();
    }

    protected function getCastReturnType($className)
    {
        if ($className === null) {
            return null;
        }

        try {
            $method = (new ReflectionClass($className))->getMethod('get');

            if ($method->hasReturnType()) {
                $type = $method->getReturnType();

                return $type instanceof ReflectionNamedType ? $type->getName() : (string) $type;
            }

            return $className;
        } catch (Exception|Throwable $e) {
            return $className;
        }
    }

    protected function fromArtisan($className)
    {
        if (!isset(Artisan::all()['model:show'])) {
            return $this->fromLegacyModel($className);
        }

        $output = new BufferedOutput;

        try {
            Artisan::call(
                'model:show',
                [
                    'model'  => $className,
                    '--json' => true,
                ],
                $output
            );
        } catch (Exception|Throwable $e) {
            return null;
        }

        return json_decode($output->fetch(), true);
    }

    protected function fromLegacyModel(string $className): ?array
    {
        try {
            $model = new $className;
        } catch (Throwable $e) {
            return null;
        }

        $columns = [];

        try {
            $columns = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());
        } catch (Throwable $e) {
            // Explicit attributes remain available when the development database is offline.
        }

        $casts = $model->getCasts();
        $accessors = $model->getMutatedAttributes();
        $attributes = array_unique(array_merge(
            $columns,
            array_keys($model->getAttributes()),
            $model->getFillable(),
            array_keys($casts),
            $accessors
        ));

        return [
            'class'      => $className,
            'database'   => $model->getConnectionName(),
            'table'      => $model->getTable(),
            'attributes' => array_values(array_map(fn ($name) => [
                'name'     => $name,
                'cast'     => $casts[$name] ?? (in_array($name, $accessors, true) ? 'accessor' : null),
                'fillable' => $model->isFillable($name),
                'hidden'   => in_array($name, $model->getHidden(), true),
            ], $attributes)),
            'relations'  => $this->legacyRelations($model),
        ];
    }

    protected function legacyRelations(Model $model): array
    {
        $reflection = new ReflectionClass($model);
        $relations = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0 || $method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }

            $type = $method->getReturnType();
            $file = $method->getFileName();
            $lines = $file ? file($file) : false;
            $body = $lines ? implode('', array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1)) : '';

            if (!($type instanceof ReflectionNamedType && is_a($type->getName(), Relation::class, true))
                && !preg_match('/return\s+\$this->(?:hasOne|hasMany|belongsTo|belongsToMany|hasOneThrough|hasManyThrough|morphTo|morphOne|morphMany|morphToMany|morphedByMany)\s*\(/', $body)) {
                continue;
            }

            try {
                $relation = Relation::noConstraints(fn () => $method->invoke($model));

                if ($relation instanceof Relation) {
                    $relations[] = [
                        'name'    => $method->getName(),
                        'type'    => class_basename($relation),
                        'related' => get_class($relation->getRelated()),
                    ];
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        return $relations;
    }

    protected function collectExistingProperties($reflection)
    {
        if ($this->factory === null) {
            return collect();
        }

        if ($comment = $reflection->getDocComment()) {
            $docblock = $this->factory->create($comment);

            $existingProperties = collect($docblock->getTagsByName('property'))->map(fn ($p) => $p->getVariableName());
            $existingReadProperties = collect($docblock->getTagsByName('property-read'))->map(fn ($p) => $p->getVariableName());

            return $existingProperties->merge($existingReadProperties);
        }

        return collect();
    }

    protected function getParentClass(ReflectionClass $reflection)
    {
        if (!$reflection->getParentClass()) {
            return null;
        }

        $parent = $reflection->getParentClass()->getName();

        if ($parent === Model::class) {
            return null;
        }

        return Str::start($parent, '\\');
    }

    protected function getInfo($className)
    {
        if (($data = $this->fromArtisan($className)) === null) {
            return null;
        }

        $reflection = new ReflectionClass($className);

        $data['extends'] = $this->getParentClass($reflection);

        $existingProperties = $this->collectExistingProperties($reflection);

        $data['attributes'] = collect($data['attributes'])
            ->map(fn ($attrs) => array_merge($attrs, [
                'title_case' => (string) Str::of($attrs['name'])->title()->replace('_', ''),
                'documented' => $existingProperties->contains($attrs['name']),
                'cast'       => $this->getCastReturnType($attrs['cast']),
            ]))
            ->toArray();

        $data['scopes'] = collect($reflection->getMethods())
            ->filter(fn (ReflectionMethod $method) => !$method->isStatic() && (LspHelper::attributes($method, Scope::class) || ($method->isPublic() && Str::startsWith($method->name, 'scope'))))
            ->map(fn (ReflectionMethod $method) => [
                'name'       => lcfirst(Str::startsWith($method->name, 'scope') ? substr($method->name, 5) : $method->name),
                'method'     => $method->name,
                'parameters' => collect($method->getParameters())->map(fn (ReflectionParameter $parameter) => $this->getScopeParameterInfo($parameter)),
            ])
            ->values()
            ->toArray();

        $data['relations'] = collect($data['relations'])
            ->map(fn ($relation) => array_merge($relation, [
                'snake_case' => Str::snake($relation['name']),
            ]))
            ->toArray();

        $data['path'] = LspHelper::relativePath($reflection->getFileName() ?: '');

        return [
            $className => $data,
        ];
    }

    protected function getScopeParameterInfo(ReflectionParameter $parameter): array
    {
        $result = [
            'name'                => $parameter->getName(),
            'type'                => $this->typeToString($parameter->getType()),
            'hasDefault'          => $parameter->isDefaultValueAvailable(),
            'isVariadic'          => $parameter->isVariadic(),
            'isPassedByReference' => $parameter->isPassedByReference(),
        ];

        if ($parameter->isDefaultValueAvailable()) {
            $result['default'] = $this->defaultValueToString($parameter);
        }

        return $result;
    }

    protected function typeToString(?ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            return $this->namedTypeToString($type);
        }

        if ($type instanceof ReflectionUnionType) {
            return $this->unionTypeToString($type);
        }

        if ($type instanceof ReflectionIntersectionType) {
            return $this->intersectionTypeToString($type);
        }

        return 'mixed';
    }

    protected function namedTypeToString(ReflectionNamedType $type): string
    {
        $name = $type->getName();

        if (!$type->isBuiltin() && !in_array($name, ['self', 'parent', 'static'])) {
            $name = '\\' . $name;
        }

        if ($type->allowsNull() && !in_array($name, ['null', 'mixed', 'void'])) {
            $name = '?' . $name;
        }

        return $name;
    }

    protected function unionTypeToString(ReflectionUnionType $type): string
    {
        return implode('|', array_map(function (ReflectionType $type) {
            $result = $this->typeToString($type);

            if ($type instanceof ReflectionIntersectionType) {
                return "({$result})";
            }

            return $result;
        }, $type->getTypes()));
    }

    protected function intersectionTypeToString(ReflectionIntersectionType $type): string
    {
        return implode('&', array_map(fn (ReflectionType $part) => $this->typeToString($part), $type->getTypes()));
    }

    protected function defaultValueToString(ReflectionParameter $param): string
    {
        if ($param->isDefaultValueConstant()) {
            return '\\' . $param->getDefaultValueConstantName();
        }

        $value = $param->getDefaultValue();

        switch (true) {
            case is_null($value):
                return 'null';
            case is_numeric($value):
                return (string) $value;
            case is_bool($value):
                return $value ? 'true' : 'false';
            case is_array($value):
                return '[]';
            case is_object($value):
                return 'new \\' . get_class($value);
            default:
                return var_export($value, true);
        }
    }
};

$builder = new class($docblocks)
{
    protected $docblocks;

    public function __construct($docblocks)
    {
        $this->docblocks = $docblocks;
    }

    public function methods()
    {
        $reflection = new ReflectionClass(Builder::class);

        return collect($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED))
            ->filter(fn (ReflectionMethod $method) => !Str::startsWith($method->getName(), '__') || (!$method->isPublic() && empty(LspHelper::attributes($method, Scope::class))))
            ->map(fn (ReflectionMethod $method) => $this->getMethodInfo($method))
            ->filter()
            ->values();
    }

    protected function getMethodInfo($method)
    {
        [$params, $return] = $this->docblocks->forMethod($method);

        return [
            'name'       => $method->getName(),
            'parameters' => $params,
            'return'     => $return,
        ];
    }
};

echo json_encode([
    'builderMethods' => $builder->methods(),
    'models'         => $models->all(),
]);
