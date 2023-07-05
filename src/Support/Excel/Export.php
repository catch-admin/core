<?php
namespace Catch\Support\Excel;

use Catch\Exceptions\FailedException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * excel export abstract class
 */
abstract class Export implements
    FromArray,
    ShouldAutoSize,
    WithHeadings,
    WithColumnWidths
{
    /**
     *  when data length lt 20000
     *  it will change csv
     *
     * @var int
     */
    protected int $toCsvLimit = 20000;

    /**
     * data
     *
     * @var array
     */
    protected array $data;

    /**
     * 查询参数
     *
     * @var array
     */
    protected array $search;


    /**
     * excel header
     *
     * @var array
     */
    protected array $header = [];

    /**
     * filename
     *
     * @var string|null
     */
    protected ?string $filename = null;


    /**
     * @var bool
     */
    protected bool $unlimitedMemory = false;

    /**
     * export
     *
     * @return string
     */
    public function export(): string
    {
        try {
            // 内存限制
            if ($this->unlimitedMemory) {
                ini_set('memory_limit', -1);
            }

            // 写入文件类型
            $writeType = $this->getWriteType();

            // 文件保存地址
            $file = sprintf('%s/%s', $this->getExportPath(), $this->getFilename($writeType));

            // 保存
            Excel::store($this, $file, null, $writeType);

            // 导出事件
            Event::dispatch(\Catch\Events\Excel\Export::class);
            return $file;
        } catch (\Exception|\Throwable $e) {
            throw new FailedException('导出失败: ' . $e->getMessage() . $e->getLine());
        }
    }

    /**
     * download
     *
     * @param string|null $filename
     * @return BinaryFileResponse
     */
    public function download(string $filename = null): BinaryFileResponse
    {
        $filename = $filename ? : $this->getFilename();
        $writeType = $this->getWriteType();

        return Excel::download(
            $this,
            $filename,
            $writeType,
            [
                'filename' => $filename,
                'write_type' => $writeType
            ]
        );
    }

    /**
     * @param array $search
     * @return $this
     */
    public function setSearch(array $search): static
    {
        $this->search = $search;

        return $this;
    }

    /**
     * get search
     *
     * @return array
     */
    public function getSearch(): array
    {
        return $this->search;
    }

    /**
     * get write type
     *
     * @return string
     */
    protected function getWriteType(): string
    {
        if ($this instanceof WithCustomCsvSettings && count($this->array()) >= $this->toCsvLimit) {
            return \Maatwebsite\Excel\Excel::CSV;
        }

        return \Maatwebsite\Excel\Excel::XLSX;
    }


    /**
     * get export path
     *
     * @return string
     */
    public function getExportPath(): string
    {
        $path = sprintf('excel/export/%s', date('Ymd'));

        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new FailedException(sprintf('Directory "%s" was not created', $path));
        }

        return $path;
    }

    /**
     * set filename
     *
     * @param string $filename
     * @return $this
     */
    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }


    /**
     * get filename
     *
     * @param string|null $type
     * @return string
     */
    public function getFilename(string $type = null): string
    {
        if (! $this->filename) {
            return Str::random() . '.'. strtolower($type ? : $this->getWriteType());
        }

        return $this->filename;
    }

    /**
     * get excel header
     *
     * @return array
     */
    public function getHeader(): array
    {
        return $this->header;
    }


    /**
     * set excel header
     *
     * @param array $header
     * @return $this
     */
    public function setHeader(array $header): static
    {
        $this->header = $header;

        return $this;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $headings = [];

        foreach ($this->header as $k => $item) {
            if (is_string($k) && is_numeric($item)) {
                $headings[] = $k;
            }

            if (is_string($item)) {
                $headings[] = $item;
            }
        }

        return $headings;
    }


    /**
     * column width
     *
     * @return int[]
     */
    public function columnWidths(): array
    {
        $columns = [];

        $column = ord('A') - 1;

        foreach ($this->header as $k => $item) {
            $column += 1;
            if (is_string($k) && is_numeric($item)) {
                $columns[chr($column)] = $item;
            }
        }

        return $columns;
    }

    /**
     * @return array
     */
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
            'use_bom' => false,
        ];
    }
}
