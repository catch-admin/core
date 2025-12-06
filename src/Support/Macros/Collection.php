<?php

declare(strict_types=1);

namespace Catch\Support\Macros;

use Catch\Support\Excel\Csv;
use Catch\Support\Excel\Export;
use Catch\Support\Excel\XlsWriterExport;
use Catch\Support\Tree;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

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

        $this->download();
    }

    /**
     * collection to tree
     */
    public function toTree(): void
    {
        LaravelCollection::macro(__FUNCTION__, function (int $pid = 0, string $pidField = 'parent_id', string $child = 'children', $id = 'id') {
            return LaravelCollection::make(Tree::done($this->all(), $pid, $pidField, $child, $id));
        });
    }

    /**
     * toOptions
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
                        'label' => $item[1],
                    ];
                } else {
                    return [
                        'value' => $key,
                        'label' => $item,
                    ];
                }
            })->values();
        });
    }

    public function export(): void
    {
        LaravelCollection::macro(__FUNCTION__, function (array $header) {
            $items = $this->toArray();

            $export = new class($items, $header) extends Export {
                protected array $items;

                public function __construct(array $items, array $header)
                {
                    $this->items = $items;

                    $this->header = $header;
                }

                public function array(): array
                {
                    // TODO: Implement array() method.
                    return $this->items;
                }
            };

            return $export->export();
        });
    }

    public function download(): void
    {
        LaravelCollection::macro(__FUNCTION__, function (array $header, array $fields = []) {
            $items = $this->toArray();
            // 自定字段重新组装数据
            $newItems = [];
            if (! empty($fields)) {
                foreach ($items as $item) {
                    $newItem = [];
                    foreach ($fields as $field) {
                        $newItem[] = $item[$field] ?? null;
                    }
                    $newItems[] = $newItem;
                }
            }
            if (count($newItems)) {
                $items = $newItems;
            }

            $export = new class($items, $header) extends Export {
                protected array $items;

                public function __construct(array $items, array $header)
                {
                    $this->items = $items;

                    $this->header = $header;
                }

                public function array(): array
                {
                    // TODO: Implement array() method.
                    return $this->items;
                }
            };

            return $export->download();
        });
    }
}
