<?php
declare(strict_types=1);

namespace Maleficarum\Database\Data\Collection;

/**
 * Some useful methods used by Collections
 */
final class Tools
{
    /**
     * NOTICE: This will ONLY work with queries prepared by AbstractCollection
     * Return query without given paramNames
     *
     * @param string $query
     * @param array  $paramNames
     *
     * @return string
     */
    public static function withoutQueryParams(string $query, array $paramNames): string
    {
        $filteredQuery = $query;
        natsort($paramNames);
        $sortedParams = array_reverse($paramNames);
        // REFACTOR: ? replace all of those str_replace with one, powerful regexp?
        foreach ($sortedParams as $param) {
            $filteredQuery = str_replace(' ' . $param . ',', '', $filteredQuery);
            $filteredQuery = str_replace('(' . $param . ',', '(', $filteredQuery);
            $filteredQuery = str_replace($param . ')', ')', $filteredQuery);
        }
        $filteredQuery = str_replace(', )', ')', $filteredQuery);

        return $filteredQuery;
    }
}