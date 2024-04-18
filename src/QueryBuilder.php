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
                $this->assertVariableTypes($varType, ['array'], $var);
                $result = $this->castValue($var);
                break;
            case '?a':
                $this->assertVariableTypes($varType, ['array'], $var);
                $result = [];
                foreach ($var as $key => $value) {
                    $result[] = sprintf('%s = %s', $key, $this->castValue($value));
                }
                $result = sprintf("%s", implode(", ", $result));
                break;
            default:
                throw new \Exception('Unsupported modified: ' . $symbol);
        }

        return $result;
    }

    private function castValue(mixed $value): string
    {
        return match (gettype($value)) {
            'string' => sprintf("'%s'", $value),
            'integer' => (string) $value,
            'NULL' => 'NULL',
            'boolean' => (string) (int) $value,
            'array' => sprintf("'%s'", implode("', '", $value)),
        };
    }

    private function assertVariableTypes(string $varType, array $availableTypes, mixed $var): void
    {
        if (!in_array($varType, $availableTypes)) {
            throw new \Exception('Unsupported variable type: ' . $varType . ' var: ' . print_r($var, true));
        }
    }
}