<?php

namespace App\Resources;

use App\Resources\Helpers\ImageHelper;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

class ResourceResponse implements Jsonable, JsonSerializable
{
    public function __construct(
        public string $type,
        public array $data,
        public ?ResourceResponse $addition = null,
        public array $statistics = []
    ) {
        if (isset($this->data['image']) && is_string($this->data['image'])) {
            $this->data['image'] = ImageHelper::thumbnail($this->data['image']);
        }
    }

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

    /**
     * 视频观看引导：两条嵌套的文本消息（不带统计）
     * 1) 视频编码（供用户复制粘贴到「真爱聆听」小程序）
     * 2) 引导文案
     */
    public static function videoGuide(string $code): self
    {
        $guide = self::text([
            'content' => config('x-resources.video_guide_text'),
        ]);

        return self::text(['content' => $code], [], $guide);
    }
}
