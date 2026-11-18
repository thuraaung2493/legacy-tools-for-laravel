<?php

class LspHelper
{
    public static function relativePath($path)
    {
        if (!\Illuminate\Support\Str::contains($path, base_path())) {
            return (string) $path;
        }

        return ltrim(str_replace(base_path(), '', realpath($path) ?: $path), DIRECTORY_SEPARATOR);
    }

    public static function isVendor($path)
    {
        return \Illuminate\Support\Str::contains($path, base_path('vendor'));
    }

    public static function propertyDefault(ReflectionProperty $property, ?ReflectionParameter $parameter = null): array
    {
        if (method_exists($property, 'hasDefaultValue')) {
            if ($property->hasDefaultValue()) {
                return ['default' => $property->getDefaultValue()];
            }
        } else {
            $defaults = $property->getDeclaringClass()->getDefaultProperties();
            $name = $property->getName();

            if (array_key_exists($name, $defaults)) {
                return ['default' => $defaults[$name]];
            }
        }

        if ($parameter !== null && $parameter->isDefaultValueAvailable()) {
            return ['default' => $parameter->getDefaultValue()];
        }

        return [];
    }

    public static function formatDefaultValue($value)
    {
        switch (true) {
            case is_array($value):
                return 'array(...)';
            case $value instanceof UnitEnum:
                return get_class($value) . '::' . $value->name;
            case $value instanceof Closure:
                return 'Closure';
            case is_object($value):
                return get_class($value);
            case is_string($value):
                return var_export($value, true);
            case is_null($value):
                return 'null';
            case is_bool($value):
                return $value ? 'true' : 'false';
            default:
                return $value;
        }
    }

    public static function appModels(): array
    {
        $path = app_path();
        $namespace = app()->getNamespace();
        $models = glob($path . '/*.php') ?: [];

        if (is_dir($path . '/Models')) {
            foreach (\Illuminate\Support\Facades\File::allFiles($path . '/Models') as $file) {
                $models[] = $file->getPathname();
            }
        }

        foreach ($models as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            $relative = substr($file, strlen($path) + 1, -4);
            class_exists($namespace . str_replace(['/', DIRECTORY_SEPARATOR], '\\', $relative));
        }

        return array_filter(get_declared_classes(), function ($class) {
            return is_subclass_of($class, \Illuminate\Database\Eloquent\Model::class)
                && (new ReflectionClass($class))->isInstantiable();
        });
    }

    public static function typeName(?ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();

            return $type->allowsNull() && !in_array($name, ['mixed', 'null'], true) ? '?' . $name : $name;
        }

        return $type ? (string) $type : 'mixed';
    }

    public static function attributes(ReflectionMethod $method, string $attribute): array
    {
        return method_exists($method, 'getAttributes') ? $method->getAttributes($attribute) : [];
    }
}
