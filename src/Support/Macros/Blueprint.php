<?php

declare(strict_types=1);

namespace Catch\Support\Macros;

use Illuminate\Database\Schema\Blueprint as LaravelBlueprint;

class Blueprint
{
    /**
     * boot;
     */
    public function boot(): void
    {
        $this->createdAt();

        $this->updatedAt();

        $this->deletedAt();

        $this->status();

        $this->creatorId();

        $this->unixTimestamp();

        $this->parentId();

        $this->sort();
    }

    /**
     * created unix timestamp
     */
    public function createdAt(): void
    {
        LaravelBlueprint::macro(__FUNCTION__, function () {
            $this->unsignedInteger('created_at')->default(0)->comment('创建时间');
        });
    }

    /**
     * update unix timestamp
     */
    public function updatedAt(): void
    {
        LaravelBlueprint::macro(__FUNCTION__, function () {
            $this->unsignedInteger('updated_at')->default(0)->comment('更新时间');
        });
    }

    /**
     * soft delete
     */
    public function deletedAt(): void
    {
        LaravelBlueprint::macro(__FUNCTION__, function () {
            $this->unsignedInteger('deleted_at')->default(0)->comment('软删除');
        });
    }

    /**
     * unix timestamp
     */
    public function unixTimestamp(bool $softDeleted = true): void
    {
        LaravelBlueprint::macro(__FUNCTION__, function () use ($softDeleted) {
            $this->createdAt();
            $this->updatedAt();

            if ($softDeleted) {
                $this->deletedAt();
            }
        });
    }

    /**
     * creator id
     */
    public function creatorId(): void
    {
        LaravelBlueprint::macro(__FUNCTION__, function () {
            $this->unsignedInteger('creator_id')->default(0)->comment('创建人ID');
        });
    }

    /**
     * parent ID
     */
    public function parentId(): void
    {
        LaravelBlueprint::macro(__FUNCTION__, function () {
            $this->unsignedInteger('parent_id')->default(0)->comment('父级ID');
        });
    }

    /**
     * status
     */
    public function status(): void
    {
        LaravelBlueprint::macro(__FUNCTION__, function ($default = 1) {
            $this->tinyInteger('status')->default($default)->comment('状态:1=正常,2=禁用');
        });
    }

    /**
     * sort
     */
    public function sort(int $default = 1): void
    {
        LaravelBlueprint::macro(__FUNCTION__, function () use ($default) {
            $this->integer('sort')->comment('排序')->default($default);
        });
    }
}
