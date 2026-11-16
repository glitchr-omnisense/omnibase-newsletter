<?php

namespace Base\Newsletter\Repository;

use Base\Newsletter\Entity\Subscriber;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * The subscribers, on omnibridge/doctrine's repository: it reads the core's finder language, so the back office's list
 * and its filters (SubscriberCrudController::configureFilters(), matchWith()) are read through the finder, as on every
 * persistence.
 */
class SubscriberRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Subscriber::class);
    }

    public function findOneByEmail(string $email): ?Subscriber
    {
        return $this->findOneBy(['email' => Subscriber::normalize($email)]);
    }

    public function findOneByToken(string $token): ?Subscriber
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * What the letters go to: confirmed and still there - of one language
     * when one is given.
     *
     * @return list<Subscriber>
     */
    public function findConfirmed(?string $locale = null): array
    {
        return $this->findBy($this->confirmed($locale), ['id' => 'ASC']);
    }

    public function countConfirmed(?string $locale = null): int
    {
        return $this->count($this->confirmed($locale));
    }

    /** Signed up, not confirmed yet, not gone. */
    public function countPending(): int
    {
        return $this->count([Group::of(Parser::SEPARATOR_AND,
            Criterion::of('confirmedAt', null, Parser::OPTION_NULL),
            Criterion::of('unsubscribedAt', null, Parser::OPTION_NULL),
        )]);
    }

    /** @return list<Subscriber> the last ones signed up, whatever became of them */
    public function findLatest(int $limit = 5): array
    {
        return $this->findBy([], ['createdAt' => 'DESC', 'id' => 'DESC'], $limit);
    }

    /** The last sign-up from this address, to hold the flood interval. */
    public function findLastFromIp(string $ip): ?Subscriber
    {
        return $this->findOneBy(['ip' => $ip], ['createdAt' => 'DESC']);
    }

    /**
     * Confirmed and still there, of one language when one is given.
     *
     * @return array<int|string, mixed>
     */
    private function confirmed(?string $locale): array
    {
        $criteria = [Group::of(Parser::SEPARATOR_AND,
            Criterion::of('confirmedAt', null, Parser::OPTION_NOT_NULL),
            Criterion::of('unsubscribedAt', null, Parser::OPTION_NULL),
        )];
        if ($locale) {
            $criteria['locale'] = mb_strtolower($locale);
        }

        return $criteria;
    }
}
