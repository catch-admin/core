<?php

declare(strict_types=1);

namespace Catch\Support\Macros;

use Catch\Support\Excel\Export;
use Catch\Support\Tree;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection as LaravelCollection;

class Collection
{
    /**
     * boot
     */
    public function boot(): void
    {
        $this->toOptions();

        $this->toTree();

        $this->export();
    }

    /**
     * collection to tree
     *
     * @return void
     */
    public function toTree(): void
    {
        LaravelCollection::macro(__FUNCTION__, function (int $pid = 0, string $pidField = 'parent_id', string $child = 'children') {
            return LaravelCollection::make(Tree::done($this->all(), $pid, $pidField, $child));
        });
    }

    /**
     * toOptions
     *
     * @return void
     */
    public function toOptions(): void
    {
        LaravelCollection::macro(__FUNCTION__, function () {
            return $this->transform(function ($item, $key) use (&$options) {
                if ($item instanceof Arrayable) {
                    $item = $item->toArray();
                }

                if (is_array($item)) {
                    $item = array_values($item);
                    return [
                        'value' => $item[0],
                        'label' => $item[1]
                    ];
                } else {
                    return [
                        'value' => $key,
                        'label' => $item
                    ];
                }
            })->values();
        });
    }

    /**
     * @return void
     */
    public function export(): void
    {
        LaravelCollection::macro(__FUNCTION__, function (array $header) {
             $items = $this->toArray();
             $export = new class($items, $header) extends Export {

                 /**
                  * @var array
                  */
                 protected array $items;

                 /**
                  * @param array $items
                  * @param array $header
                  */
                 public function __construct(array $items, array $header)
                 {
                     $this->items = $items;

                     $this->header = $header;
                 }

                 /**
                  * @return array
                  */
                 public function array(): array
                 {
                     // TODO: Implement array() method.
                     return $this->items;
                 }
             };

             return $export->export();
        });
    }
}
