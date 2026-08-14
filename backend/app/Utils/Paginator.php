<?php

namespace App\Utils;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator as BasePaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Paginator
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Paginate an Eloquent query, Query Builder, Relation, or Collection/Array.
     *
     * @param  mixed  $target
     * @param  int  $perPage
     * @param  int|null  $page
     * @param  string  $pageName
     * @param  array  $options
     * @return LengthAwarePaginatorContract
     */
    public static function paginate(
        mixed $target,
        int $perPage = 10,
        ?int $page = null,
        string $pageName = 'page',
        array $options = []
    ): LengthAwarePaginatorContract {
        $perPage = max(1, $perPage);
        $page = max(1, $page ?? BasePaginator::resolveCurrentPage($pageName));

        if ($target instanceof EloquentBuilder || $target instanceof QueryBuilder || $target instanceof Relation) {
            return static::paginateQuery($target, $perPage, $page, $pageName, $options);
        }

        if ($target instanceof Collection || is_array($target)) {
            return static::paginateCollection($target, $perPage, $page, $pageName, $options);
        }

        throw new InvalidArgumentException(
            'Target must be an instance of Eloquent Builder, Query Builder, Relation, Collection, or array.'
        );
    }

    /**
     * Alias static method for creating a paginated instance.
     */
    public static function make(
        mixed $target,
        int $perPage = 10,
        ?int $page = null,
        string $pageName = 'page',
        array $options = []
    ): LengthAwarePaginatorContract {
        return static::paginate($target, $perPage, $page, $pageName, $options);
    }

    /**
     * Paginate a database or Eloquent query builder or relation.
     */
    public static function paginateQuery(
        EloquentBuilder|QueryBuilder|Relation $query,
        int $perPage = 10,
        ?int $page = null,
        string $pageName = 'page',
        array $options = []
    ): LengthAwarePaginatorContract {
        $perPage = max(1, $perPage);
        $page = max(1, $page ?? BasePaginator::resolveCurrentPage($pageName));

        $total = $query->count();

        $items = $total > 0
            ? $query->forPage($page, $perPage)->get()
            : collect();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            array_merge([
                'path' => BasePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ], $options)
        );

        return $paginator->withQueryString();
    }

    /**
     * Paginate a Collection or Array.
     */
    public static function paginateCollection(
        Collection|array $items,
        int $perPage = 10,
        ?int $page = null,
        string $pageName = 'page',
        array $options = []
    ): LengthAwarePaginatorContract {
        $perPage = max(1, $perPage);
        $page = max(1, $page ?? BasePaginator::resolveCurrentPage($pageName));

        $collection = $items instanceof Collection ? $items : collect($items);
        $total = $collection->count();

        $results = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            array_merge([
                'path' => BasePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ], $options)
        );

        return $paginator->withQueryString();
    }

    /**
     * Instance method for dependency injection usage.
     */
    public function makePaginated(
        mixed $target,
        int $perPage = 10,
        ?int $page = null,
        string $pageName = 'page',
        array $options = []
    ): LengthAwarePaginatorContract {
        return static::paginate($target, $perPage, $page, $pageName, $options);
    }
}
