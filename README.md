## CatchAdmin Core

This is the core package of catchadmin, which contains some very useful commands, as well as the core functions of module management. Modules are automatically registered, routes are automatically loaded, and it is better to use with catchadmin.


## How to Use
Please go to [catchadmin official](https://catchadmin.com/docs/3.0/intro)

## PR 提交规范
### Type
- `feat` 新功能 feature
- `fix`  修复 bug
- `docs`  文档注释
- `style`  代码格式(不影响代码运行的变动)
- `refactor`  重构、优化(既不增加新功能，也不是修复bug)
- `perf`  性能优化
- `test` 增加测试
- `chore`  构建过程或辅助工具的变动
- `revert` 回退
- `build`  打包
- `close` 关闭 issue

### scope 选填
`commit` 的作用范围

### subject
`commit` 的描述

> Type(scope): subject

例如修复 `CatchTable` 模块的 bug: **fix(scope?): 修复 some bugs **

## use in octane
设置 config/octane.php 中 `listeners`
```php
return [
 'listeners' => [
        // received 事件中新增
        RequestReceived::class => [
           Catch\Octane\RegisterExceptionHandler::class
        ],
    ],
]
```
