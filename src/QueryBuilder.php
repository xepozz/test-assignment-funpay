<?php

declare(strict_types=1);

namespace Xepozz\FunpayTestAssignment\Tests;

class QueryBuilder
{
    private array $params = [];

    public function build(string $sql, array $params): string
    {
        $this->params = $params;

        $resultSql = $sql;

        $hasPlaceholders = preg_match_all(
            <<<REGEXP
        /(?|
        (?'cond'\{(?>[^{}]+|\g'cond')*\})|
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
                $restParams = $this->params;
                $parameter = $this->popParameter();
                $substitutions[] = [
                    'position' => $position,
                    'what' => $symbol,
                    'with' => match ($parameter) {
                        ModifierEnum::CONDITIONAL_BLOCK_SKIP => '',
                        default => $this->castValue($parameter, $symbol, $restParams),
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

    private function popParameter()
    {
        return array_shift($this->params);
    }

    private function castValue(mixed $var, string $symbol, array $restParams): mixed
    {
        $varType = gettype($var);
        switch ($symbol) {
            case ModifierEnum::ANY->value:
                $this->assertVariableTypes($varType, ['string', 'integer', 'float', 'boolean', 'null', 'double'], $var);
                $result = $this->castValueInternal($var);
                break;
            case ModifierEnum::INTEGER->value:
                $this->assertVariableTypes($varType, ['integer', 'boolean', 'double'], $var);
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
                        $restParams,
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
            'string' => sprintf("%s%s%s", $escapeChar, addcslashes($value, "'"), $escapeChar),
            'integer' => (string) $value,
            'double' => (string) $value,
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