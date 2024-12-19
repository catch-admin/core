<?php
namespace Catch\Traits\DB;

use Carbon\Carbon;
use DateTimeInterface;

trait DateformatTrait
{

    /**
     * @var string
     */
    protected string $timeFormat = 'Y-m-d H:i:s';

    /**
     * 设置时间格式
     *
     * @param string $timeFormat
     * @return $this
     */
    public function setTimeFormat(string $timeFormat): static
    {
        $this->timeFormat = $timeFormat;

        return $this;
    }

    /**
     * 重写 serializeDate
     */
    protected function serializeDate(DateTimeInterface|string $date): ?string
    {
        if (is_string($date)) {
            return $date;
        }

        return Carbon::instance($date)->setTimezone(config('app.timezone'))->format($this->timeFormat);
    }
}
