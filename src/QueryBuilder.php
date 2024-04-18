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
                if (!in_array($varType, ['string', 'integer', 'float', 'boolean', 'null'])) {
                    throw new \Exception('Unsupported variable type: ' . $varType . ' var: ' . print_r($var, true));
                }
                $result = match ($varType) {
                    'string' => sprintf("'%s'", $var),
                    'integer' => (string) $var,
                };
                break;
            case '?d':
                if (!in_array($varType, ['integer', 'boolean'])) {
                    throw new \Exception('Unsupported variable type: ' . $varType . ' var: ' . print_r($var, true));
                }
                $result = match ($varType) {
                    'integer' => (string) $var,
                    'boolean' => (string) (int) $var,
                };
                break;
            case '?#':
                if (!in_array($varType, ['array'])) {
                    throw new \Exception('Unsupported variable type: ' . $varType . ' var: ' . print_r($var, true));
                }
                $result = match ($varType) {
                    'array' => sprintf("'%s'", implode("', '", $var)),
                };
                break;
            default:
                throw new \Exception('Unsupported modified: ' . $symbol);
        }

        return $result;
    }
}