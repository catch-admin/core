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

/**
 * base operate
 */
trait WithAttributes
{
    /**
     * @var string
     */
    protected string $parentIdColumn = 'parent_id';

    /**
     * @var string
     */
    protected string $sortField = 'sort';

    /**
     * @var bool
     */
    protected bool $sortDesc = true;

    /**
     * as tress which is show in list as tree data
     *
     * @var bool
     */
    protected bool $asTree = false;

    /**
     * columns which show in list
     *
     * @var array
     */
    protected array $fields = [];


    /**
     * @var bool
     */
    protected bool $isPaginate = true;

    /**
     * @var array
     */
    protected array $form = [];

    /**
     * @var array
     */
    protected array $formRelations = [];

    /**
     * @var bool
     */
    protected bool $dataRange = false;

    /**
     *
     * @param string $parentId
     * @return $this
     */
    public function setParentIdColumn(string $parentId): static
    {
        $this->parentIdColumn = $parentId;

        return $this;
    }

    /**
     *
     * @param string $sortField
     * @return $this
     */
    protected function setSortField(string $sortField): static
    {
        $this->sortField = $sortField;

        return $this;
    }

    /**
     *
     * @return $this
     */
    protected function setPaginate(bool $isPaginate = true): static
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

    /**
     * @return array
     */
    public function getForm(): array
    {
        if (property_exists($this, 'form') && ! empty($this->form)) {
            return $this->form;
        }

        return [];
    }

    /**
     * get parent id
     *
     * @return string
     */
    public function getParentIdColumn(): string
    {
        return $this->parentIdColumn;
    }

    /**
     *
     * @return array
     */
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
     * @param bool $use
     * @return $this
     */
    public function setDataRange(bool $use = true): static
    {
        $this->dataRange = $use;

        return $this;
    }
}
