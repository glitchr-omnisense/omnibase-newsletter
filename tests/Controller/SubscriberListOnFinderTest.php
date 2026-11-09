<?php

namespace Base\Newsletter\Tests\Controller;

use Base\Admin\Controller\AbstractCrudController;
use Base\Admin\Filter\Filter;
use Base\Admin\Filter\Filters;
use Base\Database\Repository\Entities;
use Base\Database\Repository\Finder\Group;
use Base\Newsletter\Controller\Admin\Crud\SubscriberCrudController;
use Base\Newsletter\Repository\SubscriberRepository;
use PHPUnit\Framework\TestCase;

/**
 * The back office's list of subscribers is read through the core's finder, as on every persistence: SubscriberRepository
 * is omnibridge/doctrine's repository, which reads the finder language and is one of the core's repositories (Entities),
 * so omnibase/admin builds the list and its "confirmed" and "unsubscribed" filters with findBy() and their matchWith(),
 * where a plain Doctrine repository kept the list on Doctrine's QueryBuilder (their applyWith()).
 */
final class SubscriberListOnFinderTest extends TestCase
{
    public function testTheRepositoryReadsTheFinder(): void
    {
        self::assertTrue(is_subclass_of(SubscriberRepository::class, Entities::class), 'one of the core\'s repositories');
        self::assertTrue(method_exists(SubscriberRepository::class, '__call'), 'the finder language (findByConfirmedAtNotNull()...)');
    }

    public function testTheListAndItsFiltersAreReadThroughTheFinder(): void
    {
        if (!class_exists(Filters::class) || !method_exists(Filter::class, 'matchWith')) {
            self::markTestSkipped('Needs omnibase/admin with Filter::matchWith().');
        }
        if (!method_exists(Group::class, 'of')) {
            self::markTestSkipped('omnisense/omnibase 4.x: the list is built on Doctrine\'s QueryBuilder.');
        }
        $controller = (new \ReflectionClass(SubscriberCrudController::class))->newInstanceWithoutConstructor();
        $subscribers = (new \ReflectionClass(SubscriberRepository::class))->newInstanceWithoutConstructor();
        $onFinder = new \ReflectionMethod(AbstractCrudController::class, 'indexOnFinder');

        foreach ([[], ['confirmed' => '1'], ['unsubscribed' => '0', 'confirmed' => '0']] as $values) {
            self::assertTrue($onFinder->invoke($controller, $subscribers, $controller->configureFilters(Filters::new()), $values), json_encode($values));
        }
    }
}
