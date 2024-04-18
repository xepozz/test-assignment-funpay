<?php

declare(strict_types=1);

namespace Xepozz\FunpayTestAssignment\Tests;

class QueryBuilder
{
    public function build(string $sql, array $params): string
    {
        $resultSql = $sql;

        $hasPlaceholders = preg_match_all(
            <<<REGEXP
        /(?|
        \?a|
        \?d|
        \?\#|
        \?
        )/x
        REGEXP
            ,
            $sql,
            $placeholders,
            PREG_OFFSET_CAPTURE
        );
        if ($hasPlaceholders) {
            $substitutions = [];

            foreach ($placeholders[0] as $i => $placeholder) {
                [$symbol, $position] = $placeholder;
                $substitutions[] = [
                    'position' => $position,
                    'what' => $symbol,
                    'with' => $this->escape($params[$i], $symbol),
                ];
            }
            $offset = 0;
            foreach ($substitutions as $substitution) {
                [
                    'position' => $position,
                    'what' => $symbol,
                    'with' => $with,
                ] = $substitution;
                $resultSql = substr_replace($resultSql, $with, $position + $offset, strlen($symbol));
                $offset += strlen($with) - strlen($symbol);
            }
            //$resultSql = str_replace($placeholders[0], $substitutions, $resultSql);
        }

        return $resultSql;
    }

    private function escape(mixed $var, string $symbol): mixed
    {
        $result = $var;
        $varType = gettype($var);
        switch ($symbol) {
            case '?':
                $this->assertVariableTypes($varType, ['string', 'integer', 'float', 'boolean', 'null'], $var);
                $result = $this->castValue($var);
                break;
            case '?d':
                $this->assertVariableTypes($varType, ['integer', 'boolean'], $var);
                $result = $this->castValue($var);
                break;
            case '?#':
                $this->assertVariableTypes($varType, ['array', 'string'], $var);
                $result = $this->castValue($var, '`');
                break;
            case '?a':
                $this->assertVariableTypes($varType, ['array'], $var);
                $result = [];
                if (array_is_list($var)) {
                    $result = array_map($this->castValue(...), $var);
                } else {
                    foreach ($var as $key => $value) {
                        $result[] = sprintf('%s = %s', $key, $this->castValue($value));
                    }
                }
                $result = sprintf("%s", implode(", ", $result));
                break;
            default:
                throw new \Exception('Unsupported modified: ' . $symbol);
        }

        return $result;
    }

    private function castValue(mixed $value, string $escapeChar = "'"): string
    {
        return match (gettype($value)) {
            'string' => sprintf("%s%s%s", $escapeChar, $value, $escapeChar),
            'integer' => (string) $value,
            'NULL' => 'NULL',
            'boolean' => (string) (int) $value,
            'array' => sprintf(
                "%s%s%s",
                $escapeChar,
                implode(sprintf("%s, %s", $escapeChar, $escapeChar), $value),
                $escapeChar
            ),
        };
    }

    private function assertVariableTypes(string $varType, array $availableTypes, mixed $var): void
    {
        if (!in_array($varType, $availableTypes)) {
            throw new \Exception('Unsupported variable type: ' . $varType . ' var: ' . print_r($var, true));
        }
    }
}