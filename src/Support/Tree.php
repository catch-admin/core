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

namespace Catch\Support;

class Tree
{
    protected static string $pk = 'id';

    public static function setPk(string $pk): Tree
    {
        self::$pk = $pk;

        return new self;
    }

    /**
     * return done
     */
    public static function done(array $items, int $pid = 0, string $pidField = 'parent_id', string $child = 'children', $id = 'id'): array
    {
        self::$pk = $id;

        $childrenByPid = [];

        foreach ($items as $item) {
            $childrenByPid[(int) $item[$pidField]][] = $item;
        }

        return self::build($childrenByPid, $pid, $child);
    }

    /**
     * build tree
     */
    private static function build(array &$childrenByPid, int $pid, string $child): array
    {
        $tree = [];

        foreach ($childrenByPid[$pid] ?? [] as $item) {
            $children = self::build($childrenByPid, (int) $item[self::$pk], $child);

            if (count($children)) {
                $item[$child] = $children;
            }

            $tree[] = $item;
        }

        return $tree;
    }
}
