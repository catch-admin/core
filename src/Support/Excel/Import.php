<?php

namespace Catch\Support\Excel;

use Catch\Exceptions\FailedException;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Validators\ValidationException;

abstract class Import implements ToCollection, WithChunkReading, WithEvents, WithStartRow, WithValidation
{
    use Importable;
    use RegistersEventListeners;
    use SkipsFailures;

    /**
     * 错误信息
     */
    protected array $err = [];

    /**
     * 总条数
     */
    protected static int $total = 0;

    /**
     * @var array
     */
    protected array $params = [];

    /**
     * 默认的块大小
     */
    protected int $size = 500;

    /**
     * 默认块数
     */
    protected int $chunk = 0;

    /**
     * 默认开始行
     */
    protected int $start = 2;

    /**
     * 导入最大行数
     */
    protected static int $importMaxNum = 5000;

    /**
     * chunk size
     */
    protected int $chunkSize = 200;

    /**
     * @param string|UploadedFile $filePath
     * @param string|null $disk
     * @param string|null $readerType
     * @return int|array|static
     */
    public function import(string|UploadedFile $filePath, ?string $disk = null, ?string $readerType = null): int|array|static
    {
        if (empty($filePath)) {
            throw new FailedException('没有上传导入文件');
        }

        if ($filePath instanceof UploadedFile) {
            $filePath = $filePath->store('excel/import/'.date('Ymd').'/');
        }

        return $this->importData($filePath);
    }

    /**
     * @param $filePath
     * @return array|int
     */
    public function importData($filePath): array|int
    {
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
                'path' => $filePath,
            ];
        }

        return static::$total;
    }

    /**
     * @return $this
     */
    public function setParams($params): static
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param BeforeImport $event
     * @return void
     */
    public static function beforeImport(BeforeImport $event): void
    {
        $rows = $event->getReader()->getTotalRows();

        $total = array_sum($rows);

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

    public function rules(): array
    {
        // TODO: Implement rules() method.
        return [];
    }

    /**
     * async task
     */
    public function run(array $params): mixed
    {
        return $this->setParams($params)->import($this->params['path']);
    }
}
