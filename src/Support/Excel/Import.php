<?php
/* *********************************************************************************************************
 *百家云 _ _     _ _
    /  _ _  \   \  \
  /  /     \  \   \  \    项目所有权解释权归百家云公司所有
 |  |  / \   \__\ /  /    非百家云授权的使用项目代码违法必究
  \  \ \   \ ____/  /
    \__\ \_ _ __ _/                                           ------------ 南京百家云公司所有
 ________  ________  ___            ___  ___  ________           ___    ___ ___  ___  ________
|\   __  \|\   __  \|\  \          |\  \|\  \|\   __  \         |\  \  /  /|\  \|\  \|\   ___\
\ \  \|\ /\ \  \|\  \ \  \          \ \  \ \  \ \  \|\  \        \ \  \/  / | \  \\\  \ \  \\ \  \
 \ \   __  \ \   __  \ \  \       __ \ \  \ \  \ \   __  \        \ \    / / \ \  \\\  \ \  \\ \  \
  \ \  \|\  \ \  \ \  \ \  \     |\  \\_\  \ \  \ \  \ \  \        \/  /  /   \ \  \\\  \ \  \\ \  \
   \ \_______\ \__\ \__\ \__\    \ \________\ \__\ \__\ \__\     __/  / /      \ \_______\ \__\\ \__\
    \|_______|\|__|\|__|\|__|     \|________|\|__|\|__|\|__|    |\___/ /        \|_______|\|__| \|__|
                                                                \|___|/
 *********************************************************************************************************
 */

namespace Catch\Support\Excel;

use Catch\Exceptions\FailedException;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Validators\ValidationException;

abstract class Import implements
    ToCollection,
    WithChunkReading,
    WithStartRow,
    WithValidation,
    WithEvents
{
    use Importable, RegistersEventListeners, SkipsFailures;

    /**
     * 错误信息
     *
     * @var array
     */
    protected array $err = [];

    /**
     * 总条数
     *
     * @var int
     */
    protected static int $total = 0;

    /**
     * @var array
     */
    protected array $params = [];


    /**
     * 默认的块大小
     *
     * @var int
     */
    protected int $size = 500;

    /**
     * 默认块数
     *
     * @var int
     */
    protected int $chunk = 0;

    /**
     * 默认开始行
     *
     * @var int
     */
    protected int $start = 2;

    /**
     * 导入最大行数
     *
     * @var int
     */
    protected static int $importMaxNum = 5000;


    /**
     * chunk size
     *
     * @var int
     */
    protected int $chunkSize = 200;

    /**
     * @param string|UploadedFile $filePath
     * @param string|null $disk
     * @param string|null $readerType
     * @return array|int
     */
    public function import(string|UploadedFile $filePath, string $disk = null, string $readerType = null): int|array
    {
        if (empty($filePath)) {
            throw new FailedException('没有上传导入文件');
        }

        if ($filePath instanceof UploadedFile) {
            $filePath = $filePath->store('excel/import/' . date('Ymd') . '/');
        }

        try {
            $this->getImporter()->import(
                $this,
                $filePath,
                $disk ?? $this->disk ?? null,
                $readerType ?? $this->readerType ?? null
            );
        } catch (ValidationException $e) {
            $failures = $e->failures();

            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = sprintf('第%d行错误:%s', $failure->row(), implode('|', $failure->errors()));
            }
            return [
                'error' => $errors,
                'total' => static::$total,
                'path' => $filePath
            ];
        }

        return static::$total;
    }


    /**
     * @param $params
     * @return $this
     */
    public function setParams($params): static
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @param BeforeImport $event
     * @return void
     */
    public static function beforeImport(BeforeImport $event): void
    {
        $total = $event->getReader()->getTotalRows()['Worksheet'];

        static::$total = $total;

        if ($total > static::$importMaxNum) {
            throw new FailedException(sprintf('最大支持导入数量 %d 条', self::$importMaxNum));
        }
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        // TODO: Implement startRow() method.
        return $this->start;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        // TODO: Implement rules() method.
        return [];
    }
}
