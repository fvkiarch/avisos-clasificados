<?php

declare(strict_types=1);


final class Container
{
    private array $factories = [];

    private array $instances = [];

    private array $resolving = [];

    public function singleton(string $id, Closure $factory): void
    {
        $this->factories[$id] = [$factory, true];
        unset($this->instances[$id]);
    }

    public function bind(string $id, Closure $factory): void
    {
        $this->factories[$id] = [$factory, false];
    }

    public function alias(string $abstract, string $concrete): void
    {
        $this->singleton($abstract, fn (Container $c) => $c->get($concrete));
    }

    public function instance(string $id, object $object): void
    {
        $this->instances[$id] = $object;
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->resolving[$id])) {
            throw new RuntimeException("Dependencia circular resolviendo $id");
        }
        $this->resolving[$id] = true;

        try {
            if (isset($this->factories[$id])) {
                [$factory, $shared] = $this->factories[$id];
                $object = $factory($this);

                if ($shared) {
                    $this->instances[$id] = $object;
                }

                return $object;
            }

            return $this->instances[$id] = $this->autowire($id);
        } finally {
            unset($this->resolving[$id]);
        }
    }


    public function call(array $callable, array $named = []): mixed
    {
        [$class, $method] = $callable;
        $object = is_object($class) ? $class : $this->get($class);
        $ref = new ReflectionMethod($object, $method);
        $args = array_map(fn (ReflectionParameter $p) => $this->resolveParameter($p, $named), $ref->getParameters());

        return $ref->invokeArgs($object, $args);
    }

    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("No puedo resolver '$class'");
        }
        $ref = new ReflectionClass($class);

        if (!$ref->isInstantiable()) {
            throw new RuntimeException("'$class': no puedo instanciar esto");
        }
        $ctor = $ref->getConstructor();

        if ($ctor === null) {
            return new $class();
        }
        $args = array_map(fn (ReflectionParameter $p) => $this->resolveParameter($p, []), $ctor->getParameters());

        return $ref->newInstanceArgs($args);
    }

    private function resolveParameter(ReflectionParameter $p, array $named): mixed
    {
        $type = $p->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->get($type->getName());
        }

        if (array_key_exists($p->getName(), $named)) {
            $value = $named[$p->getName()];

            if ($type instanceof ReflectionNamedType) {
                return match ($type->getName()) {
                    'int'   => filter_var($value, FILTER_VALIDATE_INT) !== false
                                ? (int) $value : throw new NotFoundException(),
                    'float' => (float) $value,
                    'string' => (string) $value,
                    default => $value,
                };
            }

            return $value;
        }

        if ($p->isDefaultValueAvailable()) {
            return $p->getDefaultValue();
        }

        throw new RuntimeException("No puedo resolver este parámetro \${$p->getName()}");
    }
}
