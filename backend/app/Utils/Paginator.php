<?php

namespace App\Utils;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Paginator
{
    public const MAX_PER_PAGE = 100;

    public static function paginate(
        EloquentBuilder|QueryBuilder|Relation $query,
        int $perPage = 10,
        int $page = 1
    ): array {
        $perPage = min(max($perPage, 1), self::MAX_PER_PAGE);

        $page = max($page, 1);

        $total = $query->count();

        $lastPage = max(
            (int) ceil($total / $perPage),
            1
        );

        $page = min($page, $lastPage);

        $offset = ($page - 1) * $perPage;

        $items = $query
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return [
            'data' => $items,

            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => $total > 0 ? $offset + 1 : null,
                'to' => min($offset + $perPage, $total),
            ],
        ];
    }
}