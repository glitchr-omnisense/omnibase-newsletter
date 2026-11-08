<?php

namespace Base\Newsletter\Tests\Controller;

use Base\Admin\Filter\Filter;
use Base\Admin\Filter\Filters;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Parser;
use Base\Newsletter\Controller\Admin\Crud\SubscriberCrudController;
use Base\Newsletter\Entity\Subscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * The back office's "confirmed" and "unsubscribed" filters are states, a date
 * set or not: said in the core's finder language, which every persistence
 * reads (Filter::matchWith(), omnisense/omnibase 5.x), and the same on
 * Doctrine's QueryBuilder, where the list is still built on it (applyWith():
 * omnisense/omnibase 4.x, a repository that reads no finder). Neither is
 * deprecated any more: the admin no longer warns that the filter has only
 * applyWith().
 */
final class SubscriberFiltersTest extends TestCase
{
    private function filter(string $property): Filter
    {
        if (!class_exists(Filters::class) || !method_exists(Filter::class, 'matchWith')) {
            self::markTestSkipped('Needs omnibase/admin with Filter::matchWith().');
        }
        $controller = (new \ReflectionClass(SubscriberCrudController::class))->newInstanceWithoutConstructor();

        return $controller->configureFilters(Filters::new())->get($property) ?? self::fail($property.' is a filter');
    }

    /** @return iterable<string, array{string, string}> */
    public static function states(): iterable
    {
        yield 'confirmed' => ['confirmed', 'confirmedAt'];
        yield 'unsubscribed' => ['unsubscribed', 'unsubscribedAt'];
    }

    /** @dataProvider states */
    public function testSaidInTheFinderLanguage(string $property, string $field): void
    {
        $filter = $this->filter($property);
        self::assertTrue($filter->isNeutral(), 'every persistence can say it');
        if (!method_exists(Group::class, 'of')) {
            self::markTestSkipped('omnisense/omnibase 4.x: the list is built on Doctrine\'s QueryBuilder.');
        }

        self::assertEquals(Group::of(Parser::SEPARATOR_AND, Criterion::of($field, null, Parser::OPTION_NOT_NULL)), $filter->match('1'));
        self::assertEquals(Group::of(Parser::SEPARATOR_AND, Criterion::of($field, null, Parser::OPTION_NULL)), $filter->match('0'));
    }

    /** @dataProvider states */
    public function testTheSameOnDoctrinesQueryBuilder(string $property, string $field): void
    {
        $filter = $this->filter($property);
        foreach (['1' => 'IS NOT NULL', '0' => 'IS NULL'] as $value => $condition) {
            $queryBuilder = (new QueryBuilder($this->createMock(EntityManagerInterface::class)))->select('entity')->from(Subscriber::class, 'entity');
            $filter->apply($queryBuilder, 'entity', (string) $value);
            self::assertSame(sprintf('SELECT entity FROM %s entity WHERE entity.%s %s', Subscriber::class, $field, $condition), $queryBuilder->getDQL());
        }
    }
}
