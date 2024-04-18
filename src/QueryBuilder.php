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
        \{.*?\}|
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
            $placeholders = $placeholders[0];

            $substitutions = [];

            foreach ($placeholders as $i => $placeholder) {
                [$symbol, $position] = $placeholder;
                $substitutions[] = [
                    'position' => $position,
                    'what' => $symbol,
                    'with' => match ($params[$i]) {
                        ModifierEnum::CONDITIONAL_BLOCK_SKIP => '',
                        default => $this->castValue($params[$i], $symbol),
                    },
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
        }

        return $resultSql;
    }

    private function castValue(mixed $var, string $symbol): mixed
    {
        $varType = gettype($var);
        switch ($symbol) {
            case ModifierEnum::ANY->value:
                $this->assertVariableTypes($varType, ['string', 'integer', 'float', 'boolean', 'null'], $var);
                $result = $this->castValueInternal($var);
                break;
            case ModifierEnum::INTEGER->value:
                $this->assertVariableTypes($varType, ['integer', 'boolean'], $var);
                $result = $this->castValueInternal($var);
                break;
            case ModifierEnum::IDENTIFIERS->value:
                $this->assertVariableTypes($varType, ['array', 'string'], $var);
                $result = $this->castValueInternal($var, '`');
                break;
            case ModifierEnum::ARRAY->value:
                $this->assertVariableTypes($varType, ['array'], $var);
                $result = [];
                if (array_is_list($var)) {
                    $result = array_map($this->castValueInternal(...), $var);
                } else {
                    foreach ($var as $key => $value) {
                        $result[] = sprintf(
                            '%s = %s',
                            $this->castValueInternal($key, '`'),
                            $this->castValueInternal($value)
                        );
                    }
                }
                $result = sprintf("%s", implode(", ", $result));
                break;
            default:
                if (str_starts_with($symbol, '{') && str_ends_with($symbol, '}')) {
                    $result = $this->build(
                        substr($symbol, 1, -1),
                        [$var],
                    );
                    break;
                }
                throw new \Exception('Unsupported modified: ' . $symbol);
        }

        return $result;
    }

    private function castValueInternal(mixed $value, string $escapeChar = "'"): string
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