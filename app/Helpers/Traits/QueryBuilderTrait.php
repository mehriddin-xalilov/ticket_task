<?php

namespace App\Helpers\Traits;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

trait QueryBuilderTrait
{
    protected function defaultQuery(Request $request, $model = null): QueryBuilder
    {
        $query = QueryBuilder::for($model ?? $this->modelClass);
        $query->allowedIncludes($request->filled('include') ? explode(',', $request->get('include')) : []);
        $query->allowedSorts($request->get('sort'));
        return $query;
    }

    public function allowIncludeAndAppend(Request $request, $model): void
    {
        if ($request->filled('append')) {
            $model->append(explode(',', $request->get('append')));
        }
        if ($request->filled('include')) {
            $model->load(explode(',', $request->get('include')));
        }
    }

    protected function withPagination($query, Request $request): JsonResponse
    {
        $query = $query->paginate($request->per_page ?? 30);

        $meta = [
            'current_page' => $query->currentPage(),
            'per_page' => $query->perPage(),
            'last_page' => $query->lastPage(),
            'total_pages' => $query->lastPage(),
            'total' => $query->total(),
        ];
        return okResponse($query->all(), meta: $meta);
    }



    protected function search(Request $request, QueryBuilder $query, $column = 'name'): void
    {
        if ($request->filled('search')) {
            $query->where($column, 'ilike', '%' . $request->get('search') . '%');
        }
    }

    public function filterBetweenDate($query, Request $request, string $column = 'created_at'): void
    {
        if ($request->filled('start')) {
            $start = $request->get('start');
            $start = Carbon::make($start)->startOfDay();
            if ($column == 'last_seen') {
                $start = $start->unix();
            }
            $query->where($column, '>=', $start);
        }

        if ($request->filled('end')) {
            $end = $request->get('end');
            $end = Carbon::make($end)->endOfDay();
            if ($column == 'last_seen') {
                $end = $end->unix();
            }
            $query->where($column, '<=', $end);
        }
    }

    public function defaultAllowFilter($query, Request $request): void
    {
        $filters = $request->get('filter');
        $filter = [];
        if (!empty($filters)) {
            foreach ($filters as $k => $item) {
                $filter[] = AllowedFilter::exact($k);
            }
        }
        $query->allowedFilters($filter);
    }

    public function multiColumnSearch($query, Request $request, array $columns = ['name'], string $key = 'search', string $table = ''): void
    {
        if ($request->filled($key)) {
            $search = $request->get($key);
            $query->where(function ($query) use ($search, $columns, $table) {
                foreach ($columns as $i => $column) {
                    $column = empty($table) ? $column : $table . '.' . $column;
                    if ($i == 0) {
                        $query->where($column, 'ILIKE', "%$search%");
                    } else {
                        $query->orWhere($column, 'ILIKE', "%$search%");
                    }
                }

            });
        }
    }

}
