<?php

declare(strict_types=1);

namespace Xepozz\FunpayTestAssignment\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BuildTest extends TestCase
{
    public static function dataQueryBuilder()
    {
        yield 'no-params' => [
            'SELECT name FROM users WHERE user_id = 1',
            [],
            'SELECT name FROM users WHERE user_id = 1',
        ];
        yield 'string param' => [
            'SELECT * FROM users WHERE name = ? AND block = 0',
            ['Jack'],
            "SELECT * FROM users WHERE name = 'Jack' AND block = 0",
        ];
        yield 'int param' => [
            "SELECT * FROM users WHERE user_id = ?d",
            [2],
            "SELECT * FROM users WHERE user_id = 2",
        ];
        yield 'bool to int param' => [
            "SELECT * FROM users WHERE name = 'Jack' AND block = ?d",
            [true],
            "SELECT * FROM users WHERE name = 'Jack' AND block = 1",
        ];
        yield 'two params' => [
            "SELECT * FROM users WHERE name = ? AND block = ?d",
            ['Jack', true],
            "SELECT * FROM users WHERE name = 'Jack' AND block = 1",
        ];
        yield 'array of identifiers' => [
            "SELECT ?# FROM users WHERE user_id = ?d AND block = ?d",
            [['name', 'email'], 2, true],
            "SELECT `name`, `email` FROM users WHERE user_id = 2 AND block = 1",
        ];
        yield 'array params' => [
            "UPDATE users SET ?a WHERE user_id = -1",
            [['name' => 'Jack', 'email' => null]],
            "UPDATE users SET `name` = 'Jack', `email` = NULL WHERE user_id = -1",
        ];
        yield 'list of ints' => [
            "SELECT name FROM users WHERE user_id IN (?a)",
            [[1, 2, 3]],
            'SELECT name FROM users WHERE user_id IN (1, 2, 3)',
        ];
        yield 'array of ints' => [
            "SELECT name FROM users WHERE ?# IN (?a)",
            ['user_id', [1, 2, 3]],
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3)',
        ];
    }

    #[DataProvider('dataQueryBuilder')]
    public function testQueryBuilder(string $sql, array $params, string $expectedSql)
    {
        $qb = new QueryBuilder();
        $actualSql = $qb->build($sql, $params);

        $this->assertEquals($expectedSql, $actualSql);
    }
}