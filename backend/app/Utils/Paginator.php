<?php

namespace App\Utils;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Paginator
{
    /**
     * Maximum allowed perPage value.
     */
    public const MAX_PER_PAGE = 100;

    public function __construct()
    {
        //
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Paginate an Eloquent query, Query Builder, Relation, Collection, or array.
     */
    public static function paginate(
        mixed $target,
        int $perPage = 10,
        ?int $page = null,
        string $pageName = 'page',
        array $options = []
    ): LengthAwarePaginatorContract {
        $perPage = static::clampPerPage($perPage);
        $page    = static::resolvePage($page, $pageName);

        return match (true) {
            $target instanceof EloquentBuilder,
            $target instanceof QueryBuilder,
            $target instanceof Relation    => static::paginateQuery($target, $perPage, $page, $pageName, $options),

            $target instanceof Collection,
            is_array($target)              => static::paginateCollection($target, $perPage, $page, $pageName, $options),

            default => throw new InvalidArgumentException(
                'Target must be an Eloquent Builder, Query Builder, Relation, Collection, or array.'
            ),
        };
    }

    /**
     * Alias for paginate().
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
     * Paginate a database or Eloquent query builder / relation.
     */
    public static function paginateQuery(
        EloquentBuilder|QueryBuilder|Relation $query,
        int $perPage = 10,
        ?int $page = null,
        string $pageName = 'page',
        array $options = []
    ): LengthAwarePaginatorContract {
        $perPage = static::clampPerPage($perPage);
        $page    = static::resolvePage($page, $pageName);
        $total   = (int) $query->count();
        $offset  = ($page - 1) * $perPage;
        $items   = $total > 0 ? $query->offset($offset)->limit($perPage)->get() : [];

        return static::buildPaginator($items, $total, $perPage, $page, $pageName, $options);
    }

    /**
     * Paginate a Collection or plain PHP array.
     */
    public static function paginateCollection(
        Collection|array $items,
        int $perPage = 10,
        ?int $page = null,
        string $pageName = 'page',
        array $options = []
    ): LengthAwarePaginatorContract {
        $perPage = static::clampPerPage($perPage);
        $page    = static::resolvePage($page, $pageName);

        // Normalise to a plain PHP array for native slicing.
        $array  = $items instanceof Collection ? $items->all() : (array) $items;
        $total  = count($array);
        $offset = ($page - 1) * $perPage;
        $slice  = array_values(array_slice($array, $offset, $perPage));

        return static::buildPaginator($slice, $total, $perPage, $page, $pageName, $options);
    }

    /**
     * Instance method for dependency-injection usage.
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

    // -------------------------------------------------------------------------
    // Private helpers (native PHP only — no Illuminate helpers)
    // -------------------------------------------------------------------------

    /**
     * Clamp perPage to [1, MAX_PER_PAGE].
     */
    private static function clampPerPage(int $perPage): int
    {
        return min(max(1, $perPage), static::MAX_PER_PAGE);
    }

    /**
     * Resolve the current page from the query string using $_GET.
     *
     * Falls back to 1 when the parameter is absent or invalid.
     */
    private static function resolvePage(?int $explicit, string $pageName): int
    {
        if ($explicit !== null) {
            return max(1, $explicit);
        }

        $raw = filter_input(INPUT_GET, $pageName, FILTER_VALIDATE_INT);

        return ($raw !== false && $raw !== null && $raw > 0) ? (int) $raw : 1;
    }

    /**
     * Resolve the current request path using $_SERVER.
     */
    private static function resolveCurrentPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Strip query string to get the plain path.
        $path = (string) parse_url($uri, PHP_URL_PATH);

        return $path !== '' ? $path : '/';
    }

    /**
     * Build a LengthAwarePaginator with query-string appended.
     */
    private static function buildPaginator(
        mixed $items,
        int $total,
        int $perPage,
        int $page,
        string $pageName,
        array $options
    ): LengthAwarePaginator {
        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            array_merge([
                'path'     => static::resolveCurrentPath(),
                'pageName' => $pageName,
            ], $options)
        );

        return $paginator->withQueryString();
    }
}
