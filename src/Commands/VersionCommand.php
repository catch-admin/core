<?php

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 ~ now https://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/catchadmin/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

namespace Catch\Commands;

use Catch\CatchAdmin;
use Illuminate\Console\Command;
class VersionCommand extends Command
{
    protected $signature = 'catch:version';

    protected $description = 'show the version of catchadmin';

    public function handle(): void
    {
        $this->info(CatchAdmin::VERSION);
    }
}
