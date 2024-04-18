<?php

declare(strict_types=1);

namespace Xepozz\FunpayTestAssignment\Tests;

interface DatabaseInterface
{
    public function buildQuery(string $query, array $args = []): string;

    public function skip();
}
