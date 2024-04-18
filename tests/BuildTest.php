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

        yield 'any string param' => [
            'SELECT * FROM users WHERE name = ? AND block = 0',
            ['Jack'],
            "SELECT * FROM users WHERE name = 'Jack' AND block = 0",
        ];
        yield 'any string param with quote' => [
            'SELECT * FROM users WHERE name IN (?, ?)',
            ['Jack', "Dmitrii's Father"],
            "SELECT * FROM users WHERE name IN ('Jack', 'Dmitrii\'s Father')",
        ];
        yield 'any int param' => [
            'SELECT * FROM users WHERE block = ?',
            [55],
            "SELECT * FROM users WHERE block = 55",
        ];
        yield 'any float param' => [
            'SELECT * FROM users WHERE block = ?',
            [55.33],
            "SELECT * FROM users WHERE block = 55.33",
        ];
        yield 'any null param' => [
            'SELECT * FROM users WHERE block = ?',
            [55.33],
            "SELECT * FROM users WHERE block = 55.33",
        ];

        yield 'int param' => [
            "SELECT * FROM users WHERE user_id = ?d",
            [2],
            "SELECT * FROM users WHERE user_id = 2",
        ];
        yield 'int hex param' => [
            "SELECT * FROM users WHERE user_id = ?d",
            [0xFF],
            "SELECT * FROM users WHERE user_id = 255",
        ];
        yield 'float param' => [
            "SELECT * FROM users WHERE user_id = ?d",
            [3.3333],
            "SELECT * FROM users WHERE user_id = 3.3333",
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
            "SELECT ?# FROM users",
            [['name', 'email']],
            "SELECT `name`, `email` FROM users",
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

        yield 'condition skip' => [
            "SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}",
            ['user_id', [1, 2, 3], ModifierEnum::CONDITIONAL_BLOCK_SKIP],
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3)',
        ];
        yield 'condition replace' => [
            "SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}",
            ['user_id', [1, 2, 3], true],
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3) AND block = 1',
        ];
        yield 'condition replace multiple1' => [
            "SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}{ OR block = ?d}",
            ['user_id', [1, 2, 3], true, ModifierEnum::CONDITIONAL_BLOCK_SKIP],
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3) AND block = 1',
        ];
        yield 'condition replace multiple2' => [
            "SELECT name FROM users WHERE ?# IN (?a){AND block = ?d}{ OR block = ?d}",
            ['user_id', [1, 2, 3], ModifierEnum::CONDITIONAL_BLOCK_SKIP, true],
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3) OR block = 1',
        ];

        yield 'recursive conditional skip parent' => [
            "SELECT name FROM users{ WHERE block = ?d{ OR block = ?d}}",
            [ModifierEnum::CONDITIONAL_BLOCK_SKIP, true],
            'SELECT name FROM users',
        ];
        yield 'recursive conditional skip children' => [
            "SELECT name FROM users{ WHERE block = ?d{ OR block = ?d}}",
            [true, ModifierEnum::CONDITIONAL_BLOCK_SKIP],
            'SELECT name FROM users WHERE block = 1',
        ];
        yield 'recursive conditional' => [
            "SELECT name FROM users WHERE {block = ?d{ OR block = ?d}}",
            [true, false],
            'SELECT name FROM users WHERE block = 1 OR block = 0',
        ];

        yield 'recursive conditional with tail' => [
            "SELECT name FROM users WHERE {block = ?d{ OR block = ?d}} ORDER BY ?#",
            [true, false, 'name'],
            'SELECT name FROM users WHERE block = 1 OR block = 0 ORDER BY `name`',
        ];
        yield 'tail after recursive conditional skip' => [
            "SELECT name FROM users WHERE {block = ?d{ OR block = ?d}} ORDER BY ?#{ LIMIT ?}",
            [true, false, 'name', ModifierEnum::CONDITIONAL_BLOCK_SKIP],
            'SELECT name FROM users WHERE block = 1 OR block = 0 ORDER BY `name`',
        ];
        yield 'tail after recursive conditional replace' => [
            "SELECT name FROM users WHERE {block = ?d{ OR block = ?d}} ORDER BY ?#{ LIMIT ?}",
            [true, false, 'name', 1],
            'SELECT name FROM users WHERE block = 1 OR block = 0 ORDER BY `name` LIMIT 1',
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