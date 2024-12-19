<?php

declare(strict_types=1);

namespace Catch\Support\Macros;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder as LaravelBuilder;

class Builder
{
    /**
     * boot
     */
    public function boot(): void
    {
        $this->whereLike();

        $this->quickSearch();

        $this->tree();
    }

    /**
     * where like
     *
     * @return void
     */
    public function whereLike(): void
    {
        LaravelBuilder::macro(__FUNCTION__, function ($filed, $value) {
            return $this->where($filed, 'like', "%$value%");
        });
    }


    /**
     * quick search
     *
     * @return void
     */
    public function quickSearch(): void
    {
        LaravelBuilder::macro(__FUNCTION__, function (array $params = []) {
            $params = array_merge(request()->all(), $params);

            if (! property_exists($this->model, 'searchable')) {
                return $this;
            }

            // filter null & empty string
            $params = array_filter($params, function ($value) {
                return (is_string($value) && strlen($value)) || is_numeric($value) || (is_array($value) && !empty($value));
            });

            $wheres = [];

            if (! empty($this->model->searchable)) {
                foreach ($this->model->searchable as $field => $op) {
                    // 临时变量
                    $_field = $field;
                    // contains alias
                    if (str_contains($field, '.')) {
                        [, $_field] = explode('.', $field);
                    }

                    if (isset($params[$_field]) && $searchValue = $params[$_field]) {
                        $operate = Str::of($op)->lower();
                        $value = $searchValue;
                        if ($operate->exactly('op')) {
                            $value = implode(',', $searchValue);
                        }

                        if ($operate->exactly('like')) {
                            $value = "%{$searchValue}%";
                        }

                        if ($operate->exactly('rlike')) {
                            $op = 'like';
                            $value = $searchValue. '%';
                        }

                        if ($operate->exactly('llike')) {
                            $op = 'like';
                            $value = '%' .$searchValue;
                        }

                        if (Str::of($_field)->endsWith('_at') || Str::of($_field)->endsWith('_time')) {
                            $value = is_string($searchValue) ? strtotime($searchValue) : $searchValue;
                        }

                        $wheres[] = [$field, strtolower($op), $value];
                    }
                }
            }

            // 组装 where 条件
            foreach ($wheres as $w) {
                [$field, $op, $value] = $w;
                if ($op == 'in') {
                    // in 操作的值必须是数组，所以 value 必须更改成 array
                    $this->whereIn($field, is_array($value) ? $value : explode(',', $value));
                } else if ($op == 'between') {
                    $this->whereBetween($field, $value);
                } else {
                    $this->where($field, $op, $value);
                }
            }

            return $this;
        });
    }

    /**
     * where like
     *
     * @time 2021年08月06日
     * @return void
     */
    public function tree(): void
    {
        LaravelBuilder::macro(__FUNCTION__, function (string $id, string $parentId, ...$fields) {
            $fields = array_merge([$id, $parentId], $fields);

            return $this->get($fields)->toTree(0, $parentId);
        });
    }
}
