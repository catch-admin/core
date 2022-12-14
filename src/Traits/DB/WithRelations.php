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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * base operate
 */
trait WithRelations
{
    /**
     * when create
     *
     * @param array $data
     */
   protected function createRelations(array $data): void
   {
       foreach ($this->getRelationsData($data) as $relation => $relationData) {
           $isRelation = $this->{$relation}();

           if (! count($relationData)) {
               continue;
           }

           // BelongsToMany
           if ($isRelation instanceof BelongsToMany) {
               $isRelation->attach($relationData);
           }

           if ($isRelation instanceof HasMany || $isRelation instanceof HasOne) {
               $isRelation->create($relationData);
           }
       }
   }


    /**
     * when updated
     *
     * @param Model $model
     * @param array $data
     */
   public function updateRelations(Model $model, array $data): void
   {
       foreach ($this->getRelationsData($data) as $relation => $relationData) {
           $isRelation = $model->{$relation}();

           // BelongsToMany
           if ($isRelation instanceof BelongsToMany) {
               $isRelation->sync($relationData);
           }
       }
   }


    /**
     *
     * @param Model $model
     */
   public function deleteRelations(Model $model): void
   {
       $relations = $this->getRelations();
       foreach ($relations as $relation) {
           $isRelation = $model->{$relation}();
           // BelongsToMany
           if ($isRelation instanceof BelongsToMany) {
               $isRelation->detach();
           }
       }
   }


    /**
     * get relations data
     *
     * @param array $data
     * @return array
     */
   protected function getRelationsData(array $data): array
   {
       $relations = $this->getFormRelations();

       if (empty($relations)) {
           return [];
       }

       $relationsData = [];

       foreach ($relations as $relation) {
           if (!isset($data[$relation]) || !$this->isRelation($relation)) {
               continue;
           }

           $relationData = $data[$relation];

           $relationsData[$relation] = $relationData;
       }

       return $relationsData;
   }
}
