<?php

declare(strict_types=1);

namespace Xepozz\FunpayTestAssignment;

class QueryBuilder
{
    private const REGULAR_EXPRESSION = <<<REGEXP
        /(?|
        (?'cond'\{(?>[^{}]+|\g'cond')*\})|
        \?a|
        \?d|
        \?\#|
        \?
        )/x
        REGEXP;
    private array $params = [];

    public function build(string $sql, array $params): string
    {
        $this->params = $params;

        return preg_replace_callback(
            self::REGULAR_EXPRESSION,
            function ($matches) {
                $symbol = $matches[0];
                $restParams = $this->params;
                $parameter = $this->popParameter();
                return match ($parameter) {
                    ModifierEnum::CONDITIONAL_BLOCK_SKIP => '',
                    default => $this->castValue($parameter, $symbol, $restParams),
                };
            },
            $sql,
        );
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
            throw new \Exception(
                sprintf(
                    'Unsupported variable type: %s var: %s. Possible types: %s',
                    $varType,
                    match (true) {
                        is_bool($var) => $var ? 'true' : 'false',
                        is_object($var) => sprintf('(object of \\%s)', $var::class),
                        default => print_r($var, true)
                    },
                    sprintf('"%s"', implode('", "', $availableTypes)),
                )
            );
        }
    }
}