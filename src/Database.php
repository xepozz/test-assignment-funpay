<?php

declare(strict_types=1);

namespace Xepozz\FunpayTestAssignment;

class Database implements DatabaseInterface
{
    public function __construct(
        private QueryBuilder $queryBuilder,
        private ?mysqli $mysqli = null,
    ) {
    }

    public function buildQuery(string $query, array $args = []): string
    {
        return $this->queryBuilder->build($query, $args);
    }

    public function skip()
    {
        return ModifierEnum::CONDITIONAL_BLOCK_SKIP;
    }
}
