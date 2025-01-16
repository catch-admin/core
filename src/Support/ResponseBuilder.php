<?php
namespace Catch\Support;

use Catch\Enums\Code;
use Illuminate\Http\JsonResponse;

/**
 * Class ResponseBuilder
 */
class ResponseBuilder extends JsonResponse
{
    /**
     * @var array
     */
    protected array $bag = [];

    /**
     * @param mixed $data
     * @return $this
     */
    public function data(mixed $data): static
    {
        $this->bag['data'] = $data;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function with(string $key, mixed $value): static
    {
        $this->bag[$key] = $value;

        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function message(string $message): static
    {
       return $this->with('message', $message);
    }

    /**
     * @param int $code
     * @return $this
     */
    public static function code(int $code): static
    {
        $self = new self();

        return $self->with('code', $code);
    }

    /**
     * @param mixed $data
     * @return ResponseBuilder
     */
    public static function success(mixed $data = []): static
    {
       return static::code(Code::SUCCESS->value())

            ->message(Code::SUCCESS->message())

            ->data($data);
    }

    /**
     * @param mixed $data
     * @return ResponseBuilder
     */
    public static function fail(mixed $data = []): static
    {
        return static::code(Code::FAILED->value())

            ->message(Code::FAILED->message())

            ->data($data);
    }

    /**
     * 分页数据
     *
     * @param mixed $data
     * @return ResponseBuilder
     */
    public static function paginate(mixed $data = []): static
    {
        $data = format_response_data($data);

        $builder = static::code(Code::SUCCESS->value())

            ->message(Code::SUCCESS->message())

            ->data($data['data']);

        unset($data['data']);

        foreach ($data as $key => $value) {
            $builder->with($key, $value);
        }

        return $builder;
    }

    /**
     * @return void
     */
    public function __invoke(): void
    {
        $bag = $this->bag;

        $this->bag = [];

        $this->setData($bag);
    }
}
