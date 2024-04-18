<?php

declare(strict_types=1);

namespace Xepozz\FunpayTestAssignment\Tests;

class QueryBuilder
{
    public function build(string $sql, array $params): string
    {
        $resultSql = $sql;

        return $resultSql;
    }
}