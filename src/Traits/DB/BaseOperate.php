<?php

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2021 https://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/catchadmin-laravel/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace Catch\Traits\DB;

use Catch\Enums\Status;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;

/**
 * base operate
 */
trait BaseOperate
{
    use WithSearch, WithAttributes, WithEvents, WithRelations;

    /**
     * @return mixed
     */
    public function getList(): mixed
    {
        $builder = self::query()->select($this->fields)->quickSearch();

        // before list
        if ($this->beforeGetList instanceof Closure) {
            $builder = call_user_func($this->beforeGetList, $builder);
        }

        if (in_array($this->sortField, $this->getFillable())) {
            $builder = $builder->orderBy($this->sortField, $this->sortDesc ? 'desc' : 'asc');
        }

        $builder = $builder->orderByDesc($this->getKeyName());

        if ($this->isPaginate) {
            return $builder->paginate(Request::get('limit', $this->perPage));
        }

        // if set as tree, it will show tree data
        if ($this->asTree) {
            return $builder->get()->toTree();
        } else {
            return $builder->get();
        }
    }


    /**
     * save
     *
     * @param array $data
     * @return bool
     */
    public function storeBy(array $data): bool
    {
        if ($this->fill($this->filterData($data))->save()) {
            if ($this->getKey()) {
                $this->createRelations($data);
            }

            return true;
        }

        return false;
    }

    /**
     * create
     *
     * @param array $data
     * @return false|mixed
     */
    public function createBy(array $data): mixed
    {
        $model = $this->newInstance();

        if ($model->fill($this->filterData($data))->save()) {
            return $model->getKey();
        }

        return false;
    }

    /**
     * update
     *
     * @param $id
     * @param array  $data
     * @return mixed
     */
    public function updateBy($id, array $data): mixed
    {
        $updated = $this->where($this->getKeyName(), $id)->update($this->filterData($data));

        if ($updated) {
            $this->updateRelations($this->find($id), $data);
        }

        return $updated;
    }

    /**
     * filter data/ remove null && empty string
     *
     * @param array $data
     * @return array
     */
    protected function filterData(array $data): array
    {
        // 表单保存的数据集合
        $form = property_exists($this, 'form') ? $this->form : [];

        foreach ($data as $k => $val) {
            if (is_null($val) || (is_string($val) && ! $val)) {
                unset($data[$k]);
            }

            if (! empty($form) && ! in_array($k, $form)) {
                unset($data[$k]);
            }
        }

        return $data;
    }


    /**
     * get first by ID
     *
     * @param $value
     * @param null $field
     * @param string[] $columns
     * @return ?Model
     */
    public function firstBy($value, $field = null, array $columns = ['*']): ?Model
    {
        $field = $field ?: $this->getKeyName();

        $model = static::where($field, $value)->first($columns);

        if ($this->afterFirstBy) {
            $model = call_user_func($this->afterFirstBy, $model);
        }

        return $model;
    }

    /**
     * delete model
     *
     * @param $id
     * @param bool $force
     * @return bool|null
     */
    public function deleteBy($id, bool $force = false): ?bool
    {
        /* @var Model $model */
        $model = self::find($id);

        if ($force) {
            $deleted = $model->forceDelete();
        } else {
            $deleted = $model->delete();
        }

        if ($deleted) {
            $this->deleteRelations($model);
        }

        return $deleted;
    }

    /**
     * disable or enable
     *
     * @param $id
     * @param string $field
     * @return bool
     */
    public function toggleBy($id, string $field = 'status'): bool
    {
        $model = self::firstBy($id);

        $model->{$field} = $model->{$field} == Status::Enable->value() ? Status::Disable->value() : Status::Enable->value();

        if ($model->save() && in_array($this->parentId, $this->getFillable())) {
            $this->updateChildren($id, $field, $model->{$field});
        }

        return true;
    }


    /**
     * 递归处理
     *
     * @param int|array $parentId
     * @param string $field
     * @param int $value
     */
    public function updateChildren(mixed $parentId, string $field, mixed $value): void
    {
        if (! $parentId instanceof Arrayable) {
            $parentId = Collection::make([$parentId]);
        }

        $childrenId = $this->whereIn('parent_id', $parentId)->pluck('id');

        if ($childrenId->count()) {
            if ($this->whereIn('parent_id', $parentId)->update([
                $field => $value
            ])) {
                $this->updateChildren($childrenId, $field, $value);
            }
        }
    }

    /**
     * alias field
     *
     * @param string|array $fields
     * @return string|array
     */
    public function aliasField(string|array $fields): string|array
    {
        $table = $this->getTable();

        if (is_string($fields)) {
            return sprintf('%s.%s', $table, $fields);
        }

        foreach ($fields as &$field) {
            $field = sprintf('%s.%s', $table, $field);
        }

        return $fields;
    }


    /**
     * get updated at column
     *
     * @return string|null
     */
    public function getUpdatedAtColumn(): ?string
    {
        $updatedAtColumn = parent::getUpdatedAtColumn();

        if (! in_array(parent::getUpdatedAtColumn(), $this->getFillable())) {
            $updatedAtColumn = null;
        }

        return $updatedAtColumn;
    }

    /**
     * get created at column
     *
     * @return string|null
     */
    public function getCreatedAtColumn(): ?string
    {
        $createdAtColumn = parent::getCreatedAtColumn();

        if (! in_array(parent::getUpdatedAtColumn(), $this->getFillable())) {
            $createdAtColumn = null;
        }

        return $createdAtColumn;
    }
}
