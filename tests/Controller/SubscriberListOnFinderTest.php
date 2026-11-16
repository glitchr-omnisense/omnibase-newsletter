<?php

namespace Base\Newsletter\Tests\Controller;

use Base\Database\Repository\Entities;
use Base\Newsletter\Repository\SubscriberRepository;
use PHPUnit\Framework\TestCase;

/**
 * The back office's list of subscribers is read through the core's finder, as on every persistence: SubscriberRepository
 * is omnibridge/doctrine's repository, which reads the finder language and is one of the core's repositories (Entities),
 * so omnibase/admin builds the list and its "confirmed" and "unsubscribed" filters with findBy() and their matchWith()
 * (the admin's only list path since the ensemble: SubscriberFiltersTest checks the filters' criteria).
 */
final class SubscriberListOnFinderTest extends TestCase
{
    public function testTheRepositoryReadsTheFinder(): void
    {
        self::assertTrue(is_subclass_of(SubscriberRepository::class, Entities::class), 'one of the core\'s repositories');
        self::assertTrue(method_exists(SubscriberRepository::class, '__call'), 'the finder language (findByConfirmedAtNotNull()...)');
    }
}
