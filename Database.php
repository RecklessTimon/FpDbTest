<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{

    private mysqli $mysqli;

    protected const PARAM_AUTO = '?';
    protected const PARAM_DEC = '?d';
    protected const PARAM_FLOAT = '?f';
    protected const PARAM_ARRAY = '?a';
    protected const PARAM_COLUMN = '?#';
    protected const PARAM_NULLABLE = [
        self::PARAM_AUTO => self::PARAM_AUTO,
        self::PARAM_DEC => self::PARAM_DEC,
        self::PARAM_FLOAT => self::PARAM_FLOAT,
    ];

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        $this->validateQuery($query);

        [$preparedQuery, $parameters] = $this->parseQuery($query);

        return $args ?
                sprintf($preparedQuery, ...$this->prepareArgs($args, $parameters)) :
                $query;
    }

    public function skip(): static
    {
        return $this;
    }

    /**
     * Checking the maximum number of parameters
     * 
     * @param string $query
     * @param int $max
     * 
     * @return DatabaseInterface
     * 
     * @throws Exception
     */
    protected function validateMaxParametersCount(string $query, int $max = 1): DatabaseInterface
    {
        if (substr_count($query, '?') > $max) {
            throw new Exception('Too many parameters');
        }

        return $this;
    }

    /**
     * @param string $query
     * 
     * @throws Exception
     */
    protected function validateQuery(string $query): DatabaseInterface
    {
        if (preg_match_all('/(\?[^dfa#\s}])|({[^{]*{)|(}[^{]*})/', $query, $matches)) {
            throw new Exception('Wrong parameters: ' . implode(', ', $matches[0]));
        }

        return $this;
    }

    /**
     * Format array values using formatAuto
     * 
     * @param array $arr
     * @return string
     */
    protected function formatArray(array $arr): string
    {

        $isList = array_is_list($arr);

        array_walk($arr,
                function (&$val, $key) use ($isList) {
                    $val = $isList ?
                            $this->formatAuto($val) :
                            "{$this->formatColumn($key)} = {$this->formatAuto($val)}"
                    ;
                });

        return implode(', ', $arr);
    }

    /**
     * Autoformat argument, based on argument type
     * 
     * @param mixed $arg allowed types: int, float, string, bool, null
     * 
     * @return string
     */
    protected function formatAuto(mixed $arg): string
    {
        return match ($type = gettype($arg)) {
            'boolean' => $this->formatInt($arg),
            'integer' => $this->formatInt($arg),
            'double' => $this->formatFloat($arg),
            'string' => $this->formatString($arg),
            'NULL' => 'NULL',
            default => throw new Exception("Unallowed argument type: '{$type}'")
        };
    }

    protected function formatColumn(mixed $arg): string
    {
        if (is_array($arg)) {
            return implode(', ', array_map([$this, 'formatColumn'], $arg));
        }

        return "`{$this->mysqli->real_escape_string($arg)}`";
    }

    protected function formatFloat(mixed $arg): string
    {

        return (string) floatval($arg);
    }

    protected function formatInt(mixed $arg): string
    {

        return (string) intval($arg);
    }

    protected function formatString(mixed $value): string
    {
        return "'{$this->mysqli->real_escape_string((string) $value)}'";
    }

    /**
     * Prepare the query for sprintf and fetch parameters
     * 
     * @return array [string $formattedQuery, string[] $parameters]
     */
    protected function parseQuery(string $query): array
    {
        $parameters = [];

        $callback = function ($matches) use (&$parameters) {

            if (substr($matches[1], 0, 1) !== '{') {
                $parameters[] = rtrim($matches[1], ' }');
            } else {
                $parameters[] = $this
                        ->validateMaxParametersCount($matches[1], 1)
                        ->parseQuery(trim($matches[1], '{}'))
                ;
            }

            return $matches[1] !== '? ' ? '%s' : '%s ';
        };

        return [
            preg_replace_callback('/((\?[dfa#\s])|(\?$)|({[^}]*}))/', $callback, $query),
            $parameters
        ];
    }

    /**
     * Prepare arg to sprintf
     * 
     * @param mixed $arg
     * @param mixed $parameters
     * 
     * @return string
     */
    protected function prepareArg(mixed $arg, mixed $parameters): string
    {

        if (is_array($parameters)) {
            if ($arg === $this) {
                return ''; //Skip
            } else {
                return sprintf($parameters[0], $this->prepareArg($arg, $parameters[1][0]));
            }
        }

        if ($arg === null && isset($this::PARAM_NULLABLE[$parameters])) {
            return 'NULL';
        } elseif ($arg === null) {
            throw new Exception(sprintf('Parameter %s can\'t be null', $parameters));
        }

        return match ($parameters) {
            static::PARAM_DEC => $this->formatInt($arg),
            static::PARAM_FLOAT => $this->formatFloat($arg),
            static::PARAM_ARRAY => $this->formatArray($arg),
            static::PARAM_COLUMN => $this->formatColumn($arg),
            static::PARAM_AUTO => $this->formatAuto($arg),
        };
    }

    /**
     * Prepare args for sprintf
     * 
     * @param array $args
     * @param array $parameters
     * 
     * @return array string[]
     */
    protected function prepareArgs(array $args, array $parameters): array
    {

        if (count($parameters) !== count($args)) {
            throw new Exception(\sprintf('Argument count error: %d arguments are required, %d given', count($parameters), count($args)));
        }

        foreach ($args as $key => &$v) {
            $v = $this->prepareArg($v, $parameters[$key]);
        }

        return $args;
    }
}
