<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Query;

use Glavweb\ApiBundle\ErrorCodes;
use Glavweb\ApiBundle\Exception\ClientErrorException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Разбор спискового query: filter[поле] и sort=-createdAt,title.
 *
 * Голое значение — точное равенство. Несколько полей и несколько операторов одного поля — И.
 * in и nin задаются повтором: filter[status][in][]=draft&filter[status][in][]=published.
 *
 * @author nilov <nilov@glavweb.ru>
 */
final class ApiFilter
{
    /** @var list<string> */
    public const array OPERATORS = [
        'eq',
        'neq',
        'gt',
        'gte',
        'lt',
        'lte',
        'contains',
        'not_contains',
        'in',
        'nin',
    ];

    /** @var list<string> */
    public const array LIST_OPERATORS = ['in', 'nin'];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function fromRequest(Request $request): array
    {
        $query = $request->query->all();
        if (!\array_key_exists('filter', $query)) {
            return [];
        }

        if (!\is_array($query['filter'])) {
            self::fail('Параметр filter задаётся как filter[поле]=значение.');
        }

        return self::parse($query['filter']);
    }

    /**
     * @param array<mixed> $raw
     *
     * @return array<string, array<string, mixed>>
     */
    public static function parse(array $raw): array
    {
        $filters = [];
        foreach ($raw as $field => $value) {
            if (!\is_string($field) || !self::isFieldName($field)) {
                self::fail('Неверное имя поля фильтра.');
            }
            $filters[$field] = self::operators($field, $value);
        }

        return $filters;
    }

    /**
     * @param array<mixed> $value
     */
    public static function isOperatorMap(array $value): bool
    {
        if ($value === [] || array_is_list($value)) {
            return false;
        }

        foreach ($value as $key => $item) {
            unset($item);
            if (!\is_string($key) || !\in_array($key, self::OPERATORS, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, string> поле => ASC|DESC
     */
    public static function ordering(?string $sort): array
    {
        if ($sort === null || $sort === '') {
            return [];
        }

        $ordering = [];
        foreach (explode(',', $sort) as $part) {
            $part = trim($part);
            if ($part === '' || $part === '-') {
                self::fail('Неверный формат сортировки. Ожидается sort=-createdAt,title.');
            }

            $descending = str_starts_with($part, '-');
            $field = $descending ? substr($part, 1) : $part;
            if (!self::isFieldName($field)) {
                self::fail(sprintf('Поле сортировки «%s» задано неверно.', $field));
            }
            if (\array_key_exists($field, $ordering)) {
                self::fail(sprintf('Поле «%s» повторяется в sort.', $field));
            }

            $ordering[$field] = $descending ? 'DESC' : 'ASC';
        }

        return $ordering;
    }

    /**
     * @param list<string> $fields
     * @param list<string> $allowed
     */
    public static function assertFields(array $fields, array $allowed): void
    {
        foreach ($fields as $field) {
            if (!\in_array($field, $allowed, true)) {
                self::fail(sprintf('Поле «%s» нельзя использовать в фильтре.', $field));
            }
        }
    }

    /**
     * @param array<string, mixed> $operators
     * @param list<string> $allowed
     */
    public static function assertOperators(array $operators, array $allowed, string $field): void
    {
        foreach (array_keys($operators) as $operator) {
            if (!\in_array($operator, $allowed, true)) {
                self::fail(sprintf('Оператор «%s» нельзя применить к полю «%s».', $operator, $field));
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function operators(string $field, mixed $value): array
    {
        if (\is_scalar($value)) {
            return ['eq' => (string) $value];
        }

        if (!\is_array($value) || !self::isOperatorMap($value)) {
            self::fail(sprintf('Фильтр «%s» задан неверно. Значение или filter[%s][eq]=…, список — filter[%s][in][]=….', $field, $field, $field));
        }

        $operators = [];
        foreach ($value as $operator => $item) {
            if (\in_array($operator, self::LIST_OPERATORS, true)) {
                if (!\is_array($item) || $item === [] || !array_is_list($item)) {
                    self::fail(sprintf('Оператор %s у поля «%s» задаётся списком filter[%s][%s][]=….', $operator, $field, $field, $operator));
                }
                $list = [];
                foreach ($item as $one) {
                    if (!\is_scalar($one) || (string) $one === '') {
                        self::fail(sprintf('Список %s у поля «%s» содержит пустое значение.', $operator, $field));
                    }
                    $list[] = (string) $one;
                }
                $operators[$operator] = $list;

                continue;
            }

            if (!\is_scalar($item)) {
                self::fail(sprintf('Оператор «%s» у поля «%s» принимает одно значение.', $operator, $field));
            }
            $operators[$operator] = (string) $item;
        }

        return $operators;
    }

    private static function isFieldName(string $field): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)*$/', $field) === 1;
    }

    private static function fail(string $message): never
    {
        throw new ClientErrorException($message, ErrorCodes::PARAMETER_WRONG_FORMAT);
    }
}
