<?php

namespace App\Resources;

use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

class ResourceResponse implements Jsonable, JsonSerializable
{
    public function __construct(
        public string $type,
        public array $data,
        public ?ResourceResponse $addition = null,
        public array $statistics = []
    ) {}

    /**
     * Convert the object to its JSON representation
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Specify data which should be serialized to JSON
     */
    public function jsonSerialize(): array
    {
        $result = [
            'type' => $this->type,
            'data' => $this->data,
        ];

        if ($this->addition !== null) {
            $result['addition'] = $this->addition->jsonSerialize();
        }

        if (! empty($this->statistics)) {
            $result['statistics'] = $this->statistics;
        }

        return $result;
    }

    /**
     * Create a text response
     */
    public static function text(array $data, array $statistics = [], ?ResourceResponse $addition = null): self
    {
        return new self('text', $data, $addition, $statistics);
    }

    /**
     * Create a music response
     */
    public static function music(array $data, array $statistics = [], ?ResourceResponse $addition = null): self
    {
        return new self('music', $data, $addition, $statistics);
    }

    /**
     * Create a link response
     */
    public static function link(array $data, array $statistics = [], ?ResourceResponse $addition = null): self
    {
        return new self('link', $data, $addition, $statistics);
    }
}
