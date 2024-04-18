<?php

declare(strict_types=1);

namespace Xepozz\FunpayTestAssignment\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Xepozz\FunpayTestAssignment\Database;
use Xepozz\FunpayTestAssignment\QueryBuilder;

class IntegrationTest extends TestCase
{
    public function testBuildQuery(): void
    {
        $qb = new QueryBuilder();
        $database = new Database($qb);

        $results = [];

        $results[] = $database->buildQuery('SELECT name FROM users WHERE user_id = 1');

        $results[] = $database->buildQuery(
            'SELECT * FROM users WHERE name = ? AND block = 0',
            ['Jack']
        );

        $results[] = $database->buildQuery(
            'SELECT ?# FROM users WHERE user_id = ?d AND block = ?d',
            [['name', 'email'], 2, true]
        );

        $results[] = $database->buildQuery(
            'UPDATE users SET ?a WHERE user_id = -1',
            [['name' => 'Jack', 'email' => null]]
        );

        foreach ([null, true] as $block) {
            $results[] = $database->buildQuery(
                'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}',
                ['user_id', [1, 2, 3], $block ?? $database->skip()]
            );
        }

        $correct = [
            'SELECT name FROM users WHERE user_id = 1',
            'SELECT * FROM users WHERE name = \'Jack\' AND block = 0',
            'SELECT `name`, `email` FROM users WHERE user_id = 2 AND block = 1',
            'UPDATE users SET `name` = \'Jack\', `email` = NULL WHERE user_id = -1',
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3)',
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3) AND block = 1',
        ];

        $this->assertEquals($correct, $results);
        if ($results !== $correct) {
            throw new Exception('Failure.');
        }
    }
}