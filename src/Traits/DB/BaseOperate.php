<?php

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2021 https://catchadmin.vip All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/catchadmin-laravel/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace Catch\Traits\DB;

use Catch\Enums\Status;
use Catch\Exceptions\FailedException;
use Catch\Facade\Admin;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * base operate
 */
trait BaseOperate
{
    use WithEvents;
    use WithRelations;
    use WithSearch;

    public function getList(): mixed
    {
        $fields = property_exists($this, 'fields') ? $this->fields : ['*'];

        // 字段访问，获取可读字段
        if ($this->columnAccess) {
            $fields = $this->readable($fields);
        }

        $builder = static::select($fields);

        // 如果有创建人字段
        if (in_array($this->getCreatorIdColumn(), $this->getFillable())) {
            $builder = $builder->creator();
        }

        // 快速搜索
        if (! empty($this->searchable)) {
            $builder = $builder->quickSearch(callback: $this->quickSearchCallback);
        }

        // 数据权限
        if ($this->dataRange) {
            $builder = $builder->dataRange();
        }

        // before list
        if ($this->beforeGetList instanceof Closure) {
            $builder = call_user_func($this->beforeGetList, $builder);
        }

        // 排序
        if ($this->sortField && in_array($this->sortField, $this->getFillable())) {
            $builder = $builder->orderBy($this->aliasField($this->sortField), $this->getDefaultSortOrder());
        }

        // 动态排序
        $dynamicSortField = Request::get($this->dynamicQuerySortField);
        if ($dynamicSortField && $dynamicSortField != $this->sortField) {
            $builder = $builder->orderBy($this->aliasField($dynamicSortField), Request::get($this->dynamicQuerySortOrder, 'asc'));
        }
        $builder = $builder->orderByDesc($this->aliasField($this->getKeyName()));

        $limit = Request::get('limit', $this->perPage);
        // 如果使用回收站
        if ($this->isUseTrashed()) {
            return $builder->onlyTrashed()->paginate($limit);
        }

        // 分页
        if ($this->isPaginate) {
            return $builder->paginate($limit);
        }

        // 如果设置 asTree 属性为 true，将会返回树形结构
        return $builder->get()->when($this->asTree, function ($collection) {
            return $collection->toTree(id: $this->getKeyName(), pidField: $this->getParentIdColumn());
        });
    }

    /**
     * save
     */
    public function storeBy(array $data): mixed
    {
        if ($this->fill($this->filterData($data))->save()) {
            if ($this->getKey()) {
                $this->createRelations($data);
            }

            return $this->getKey();
        }

        return false;
    }

    /**
     * create
     *
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
     */
    public function updateBy($id, array $data): mixed
    {
        $model = $this->where($this->getKeyName(), $id)->first();

        $updated = $model->fill($this->filterData($data, true))->save();

        if ($updated) {
            $this->updateRelations($this->find($id), $data);
        }

        return $updated;
    }

    /**
     * @param  array<int|string>  $condition
     * @param  array<string, array<int|string>>  $data
     */
    public function batchUpdate(string $field, array $condition, array $data): bool
    {
        try {
            $batchSQL = 'UPDATE `'.withTablePrefix($this->getTable()).'` SET ';

            foreach ($data as $key => $values) {
                $batchSQL .= sprintf('`%s` = CASE ', $key);
                if (count($condition) != count($values)) {
                    continue;
                }

                foreach ($values as $index => $value) {
                    $batchSQL .= sprintf('WHEN %s = %s THEN "%s" ', $field, $condition[$index], $value);
                }

                $batchSQL .= 'ELSE '.$key.' END, ';
            }

            $where = ' WHERE '.$field.' IN ('.implode(',', $condition).')';

            $batchSQL = trim($batchSQL, ', ').$where;

            return DB::statement($batchSQL);
        } catch (\Exception|\Throwable $exception) {
            throw new FailedException('批量更新报错: '.$exception->getMessage());
        }
    }

    /**
     * filter data/ remove null && empty string
     */
    protected function filterData(array $data, $isUpdate = false): array
    {
        // 表单保存的数据集合
        $fillable = array_unique(array_merge($this->getFillable(), $this->getForm()));

        foreach ($data as $k => $val) {
            if ($this->autoNull2EmptyString && is_null($val)) {
                $data[$k] = '';
            }

            if (! empty($fillable) && ! in_array($k, $fillable)) {
                unset($data[$k]);
            }
        }

        // 如果设置了可写权限
        if ($this->columnAccess) {
            // 获取可写入的字段
            $keys = $this->writable(array_keys($data));
            foreach ($data as $key => $value) {
                // 删除 data 中不在可写入的字段
                if (! in_array($key, $keys)) {
                    unset($data[$key]);
                }
            }
        }

        // 写入创建时间和更新时间
        if (! $this->timestamps) {
            $createdAtColumn = $this->getCreatedAtColumn();
            if (! $isUpdate && $createdAtColumn) {
                $data[$createdAtColumn] = time();
            }

            // 如果是更新，删除 created_at 字段
            if ($isUpdate && isset($data[$createdAtColumn])) {
                unset($data[$createdAtColumn]);
            }
            // 更新时间字段
            if ($updatedAtColumn = $this->getUpdatedAtColumn()) {
                $data[$updatedAtColumn] = time();
            }
        }

        // 创建人
        $creatorColumn = $this->getCreatorIdColumn();
        if ($this->isFillCreatorId && in_array($creatorColumn, $this->getFillable())) {
            $creatorId = $data[$creatorColumn] ?? 0;
            if (! $creatorId && $id = Admin::id()) {
                $data[$creatorColumn] = $id;
            }
        }

        return $data;
    }

    /**
     * get first by ID
     *
     * @param  $value
     * @param  null  $field
     * @param  string[]  $columns
     * @return ?Model
     */
    public function firstBy($value, $field = null, array $columns = ['*']): ?Model
    {
        $field = $field ?: $this->getKeyName();

        if ($this->columnAccess) {
            $columns = $this->readable($columns);
        }

        $model = static::where($field, $value)->first($columns);

        if ($this->afterFirstBy) {
            $model = call_user_func($this->afterFirstBy, $model);
        }

        return $model;
    }

    /**
     * delete model
     */
    public function deleteBy($id, bool $force = false, bool $softForce = false): ?bool
    {
        /* @var Model $model */
        $model = static::find($id);

        if (in_array($this->getParentIdColumn(), $this->getFillable())
            && $this->where($this->getParentIdColumn(), $model->id)->first()
        ) {
            throw new FailedException('请先删除子级');
        }

        if ($force) {
            $deleted = $model->forceDelete();
        } else {
            $deleted = $model->delete();
        }

        if ($deleted && ! $softForce) {
            $this->deleteRelations($model);
        }

        return $deleted;
    }

    /**
     * 删除软删除数据
     */
    public function deleteTrash($id): mixed
    {
        return static::onlyTrashed()->find($id)->forceDelete();
    }

    /**
     * 批量删除
     *
     * @return true
     * @throws \Throwable
     */
    public function deletesBy(array|string $ids, bool $force = false, ?Closure $callback = null): bool
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        DB::transaction(function () use ($ids, $force, $callback) {
            // 删除软删除数据
            if ($this->isUseTrashed()) {
                foreach ($ids as $id) {
                    $this->deleteTrash($id);
                }
            } else {
                // 软删除
                foreach ($ids as $id) {
                    $this->deleteBy($id, $force);
                }
            }
            if ($callback) {
                $callback($ids);
            }
        });

        return true;
    }

    /**
     * 恢复
     */
    public function restoreBy(array|string $ids): bool|int
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (count($ids) === 1) {
            $model = $this->onlyTrashed()->find($ids[0]);

            return $model->restore();
        }

        $this->whereIn($this->getKeyName(), $ids)
            ->onlyTrashed()
            ->update([
                $this->getDeletedAtColumn() => 0,
            ]);

        return true;
    }

    /**
     * disable or enable
     */
    public function toggleBy($id, string $field = 'status'): bool
    {
        $model = $this->firstBy($id);

        $status = $model->getAttribute($field) == Status::Enable->value() ? Status::Disable->value() : Status::Enable->value();

        $model->setAttribute($field, $status);

        if ($model->save() && in_array($this->getParentIdColumn(), $this->getFillable()) && in_array($field, $this->syncParentFields)) {
            $this->updateChildren($id, $field, $model->getAttribute($field));
        }

        return true;
    }

    /**
     * @return true
     * @throws \Throwable
     */
    public function togglesBy(array|string $ids, string $field = 'status'): bool
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        DB::transaction(function () use ($ids, $field) {
            foreach ($ids as $id) {
                $this->toggleBy($id, $field);
            }
        });

        return true;
    }

    /**
     * 递归处理
     *
     * @param  int|array  $parentId
     * @param  int  $value
     */
    public function updateChildren(mixed $parentId, string $field, mixed $value): void
    {
        if (! $parentId instanceof Arrayable) {
            $parentId = Collection::make([$parentId]);
        }

        $childrenId = $this->whereIn($this->getParentIdColumn(), $parentId)->pluck('id');

        if ($childrenId->count()) {
            if ($this->whereIn($this->getParentIdColumn(), $parentId)->update([
                $field => $value,
            ])) {
                $this->updateChildren($childrenId, $field, $value);
            }
        }
    }

    /**
     * alias field
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
     */
    public function getUpdatedAtColumn(): ?string
    {
        $updatedAtColumn = parent::getUpdatedAtColumn();

        if (! in_array(parent::getUpdatedAtColumn(), $this->getFillable())) {
            $updatedAtColumn = null;
        }

        return $updatedAtColumn;
    }

    protected function isUseTrashed(): bool
    {
        $trashed = Request::get('trashed');

        return $trashed && in_array($this->getDeletedAtColumn(), $this->getFillable());
    }

    /**
     * get created at column
     */
    public function getCreatedAtColumn(): ?string
    {
        $createdAtColumn = parent::getCreatedAtColumn();

        if (! in_array(parent::getUpdatedAtColumn(), $this->getFillable())) {
            $createdAtColumn = null;
        }

        return $createdAtColumn;
    }

    public function getCreatorIdColumn(): string
    {
        return 'creator_id';
    }

    /**
     * @return $this
     */
    protected function setCreatorId(): static
    {
        $this->setAttribute($this->getCreatorIdColumn(), Admin::id());

        return $this;
    }

    /**
     * @return $this
     */
    public function setParentIdColumn(string $parentId): static
    {
        $this->parentIdColumn = $parentId;

        return $this;
    }

    /**
     * @return $this
     */
    protected function setSortField(string $sortField): static
    {
        $this->sortField = $sortField;

        return $this;
    }

    /**
     * @return $this
     */
    public function setPaginate(bool $isPaginate = true): static
    {
        $this->isPaginate = $isPaginate;

        return $this;
    }

    /**
     * whit form data
     *
     * @return $this
     */
    public function withoutForm(): static
    {
        if (property_exists($this, 'form') && ! empty($this->form)) {
            $this->form = [];
        }

        return $this;
    }

    public function getForm(): array
    {
        if (property_exists($this, 'form') && ! empty($this->form)) {
            return $this->form;
        }

        return [];
    }

    protected function getDefaultSortOrder(): string
    {
        if (property_exists($this, 'sortDesc')) {
            return $this->sortDesc ? 'desc' : 'asc';
        }

        return 'desc';
    }

    /**
     * get parent id
     */
    public function getParentIdColumn(): string
    {
        return $this->parentIdColumn;
    }

    public function getFormRelations(): array
    {
        if (property_exists($this, 'formRelations') && ! empty($this->form)) {
            return $this->formRelations;
        }

        return [];
    }

    /**
     * set data range
     *
     * @return $this
     */
    public function setDataRange(bool $use = true): static
    {
        $this->dataRange = $use;

        return $this;
    }

    /**
     * 设置字段访问
     *
     * @return $this
     */
    public function setColumnAccess(bool $use = true): static
    {
        $this->columnAccess = $use;

        return $this;
    }

    /**
     * @return $this
     */
    public function setAutoNull2EmptyString(bool $auto = true): static
    {
        $this->autoNull2EmptyString = $auto;

        return $this;
    }

    /**
     * @return $this
     */
    public function asTree(): static
    {
        $this->asTree = true;

        return $this;
    }

    /**
     * 禁用分页
     *
     * @return $this
     */
    public function disablePaginate(): static
    {
        $this->isPaginate = false;

        return $this;
    }

    /**
     * @param  bool  $is
     * @return $this
     */
    public function fillCreatorId(bool $is = true): static
    {
        $this->isFillCreatorId = $is;

        return $this;
    }

    /**
     * 设置需要同步的字段
     *
     * @param  array|null  $fields
     * @return $this
     */
    public function setSyncParentFields(?array $fields = []): static
    {
        if (is_null($fields)) {
            $this->syncParentFields = [];
        } else {
            $this->syncParentFields = array_merge($this->syncParentFields, $fields);
        }

        return $this;
    }
}
