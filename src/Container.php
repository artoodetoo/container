<?php

namespace R2\DependencyInjection;

use R2\DependencyInjection\ContainerInterface;
use R2\DependencyInjection\ContainerAwareInterface;

use InvalidArgumentException as ArgsException;

/**
 * Service Container
 */
class Container implements ContainerInterface
{
    private $shared = [];

    private $config = [
        'config'    => [],
        'shared'    => [],
        'multiple'  => [],
    ];

    public function __construct(array $config = null)
    {
        if (!empty($config)) {
            $this->config($config);
        }
    }

    public function config(array $config)
    {
        $this->config = array_replace_recursive($this->config, $config);
    }

    public function resolve($value)
    {
        $matches = null;
        if (is_string($value) && strpos($value, '%') !== false) {
            if (preg_match('~^%([a-z_0-9.]+)%$~', $value, $matches)) {
                return $this->substitute($matches);
            } else {
                $value = preg_replace_callback('~%([a-z_0-9.]+)%~', [$this, 'substitute'], $value);
            }
        } elseif (is_string($value) && isset($value{0}) && $value{0} == '@') {
            return $this->get(substr($value, 1));
        } elseif (is_array($value)) {
            foreach ($value as &$v) {
                $v = $this->resolve($v);
            }
            unset($v);
        }
        return $value;
    }

    protected function substitute($matches)
    {
        if (array_key_exists($matches[1], $this->config['parameters'])) {
            return $this->config['parameters'][$matches[1]];
        }
        return '';
    }

    public function get($id)
    {
        if (isset($this->shared[$id])) {
            return $this->shared[$id];
        }
        $toShare = false;
        if (isset($this->config['shared'][$id])) {
            $toShare = true;
            $config = (array) $this->config['shared'][$id];
        } elseif (isset($this->config['multiple'][$id])) {
            $config = (array) $this->config['multiple'][$id];
        } else {
            throw new ArgsException('Wrong property name '.$id);
        }
        $class = array_shift($config);
        $args = [];
        foreach ($config as $k => $v) {
            $args[] = $k{0} == '%' ? $this->resolve($v) : $v;
        }
        if ($class{0} == '@' && strpos($class, ':') !== false) {
            list($factoryName, $methodName) = explode(':', $class);
            $factory = $this->resolve($factoryName);
            $service = call_user_func_array([$factory, $methodName], $args);
        } else {
            $service = new $class(...$args); // cool php 5.6+ feature
            if ($service instanceof ContainerAwareInterface) {
                $service->setContainer($this);
            }
        }
        if ($toShare) {
            $this->shared[$id] = $service;
        }
        return $service;
    }

    public function set($id, $service)
    {
        return $this->shared[$id] = $service;
    }

    public function getParameter($name, $default = null)
    {
        $segments = explode('.', $name);
        $ptr =& $this->config;
        foreach ($segments as $s) {
            if (!array_key_exists($s, $ptr)) {
                return $default;
            }
            $ptr =& $ptr[$s];
        }

        return $ptr;
    }

    public function setParameter($name, $value)
    {
        $segments = explode('.', $name);
        $n = count($segments);
        $ptr =& $this->config;
        foreach ($segments as $s) {
            if (--$n) {
                if (!array_key_exists($s, $ptr)) {
                    $ptr[$s] = [];
                } elseif (!is_array($ptr[$s])) {
                    throw new ArgsException("Scalar \"{$s}\" in the path \"{$name}\"");
                }
                $ptr =& $ptr[$s];
            } else {
                $ptr[$s] = $value;
            }
        }

        return $this;
    }

}
