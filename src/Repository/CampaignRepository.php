<?php

namespace Base\Newsletter\Repository;

use Base\Newsletter\Entity\Campaign;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method Campaign|null find($id, $lockMode = null, $lockVersion = null)
 * @method Campaign|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Campaign[]    findAll()
 * @method Campaign[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class CampaignRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Campaign::class);
    }
}
