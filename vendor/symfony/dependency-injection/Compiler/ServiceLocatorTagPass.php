<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Compiler;

use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Alias;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ContainerBuilder;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Definition;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Reference;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ServiceLocator;
/**
 * Applies the "container.service_locator" tag by wrapping references into ServiceClosureArgument instances.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
final class ServiceLocatorTagPass extends \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Compiler\AbstractRecursivePass
{
    protected function processValue($value, $isRoot = \false)
    {
        if (!$value instanceof \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Definition || !$value->hasTag('container.service_locator')) {
            return parent::processValue($value, $isRoot);
        }
        if (!$value->getClass()) {
            $value->setClass(\_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ServiceLocator::class);
        }
        $arguments = $value->getArguments();
        if (!isset($arguments[0]) || !\is_array($arguments[0])) {
            throw new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException(\sprintf('Invalid definition for service "%s": an array of references is expected as first argument when the "container.service_locator" tag is set.', $this->currentId));
        }
        $i = 0;
        foreach ($arguments[0] as $k => $v) {
            if ($v instanceof \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument) {
                continue;
            }
            if (!$v instanceof \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Reference) {
                throw new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException(\sprintf('Invalid definition for service "%s": an array of references is expected as first argument when the "container.service_locator" tag is set, "%s" found for key "%s".', $this->currentId, \is_object($v) ? \get_class($v) : \gettype($v), $k));
            }
            if ($i === $k) {
                unset($arguments[0][$k]);
                $k = (string) $v;
                ++$i;
            } elseif (\is_int($k)) {
                $i = null;
            }
            $arguments[0][$k] = new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument($v);
        }
        \ksort($arguments[0]);
        $value->setArguments($arguments);
        $id = 'service_locator.' . \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ContainerBuilder::hash($value);
        if ($isRoot) {
            if ($id !== $this->currentId) {
                $this->container->setAlias($id, new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Alias($this->currentId, \false));
            }
            return $value;
        }
        $this->container->setDefinition($id, $value->setPublic(\false));
        return new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Reference($id);
    }
    /**
     * @param Reference[] $refMap
     * @param string|null $callerId
     *
     * @return Reference
     */
    public static function register(\_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ContainerBuilder $container, array $refMap, $callerId = null)
    {
        foreach ($refMap as $id => $ref) {
            if (!$ref instanceof \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Reference) {
                throw new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Exception\InvalidArgumentException(\sprintf('Invalid service locator definition: only services can be referenced, "%s" found for key "%s". Inject parameter values using constructors instead.', \is_object($ref) ? \get_class($ref) : \gettype($ref), $id));
            }
            $refMap[$id] = new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument($ref);
        }
        \ksort($refMap);
        $locator = (new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Definition(\_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ServiceLocator::class))->addArgument($refMap)->setPublic(\false)->addTag('container.service_locator');
        if (null !== $callerId && $container->hasDefinition($callerId)) {
            $locator->setBindings($container->getDefinition($callerId)->getBindings());
        }
        if (!$container->hasDefinition($id = 'service_locator.' . \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ContainerBuilder::hash($locator))) {
            $container->setDefinition($id, $locator);
        }
        if (null !== $callerId) {
            $locatorId = $id;
            // Locators are shared when they hold the exact same list of factories;
            // to have them specialized per consumer service, we use a cloning factory
            // to derivate customized instances from the prototype one.
            $container->register($id .= '.' . $callerId, \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ServiceLocator::class)->setPublic(\false)->setFactory([new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Reference($locatorId), 'withContext'])->addArgument($callerId)->addArgument(new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Reference('service_container'));
        }
        return new \_PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Reference($id);
    }
}