<?php

namespace App\Resources\Helpers;

use Illuminate\Support\Str;

class ImageHelper
{
    /**
     * 将封面图地址包装为缩略图代理地址。
     *
     * 微信侧的消息封面只接受较小的 JPEG：YouTube 带 sqp 签名的缩略图实际返回
     * WebP（文件名仍是 .jpg），播客源站直接给 .webp，图床原图又普遍接近 200KB，
     * 三者都会导致封面加载不出来。统一转成 JPEG 并限制宽度可一次解决。
     *
     * 代理域名留空时原样返回，便于在代理不可用时快速回退。
     */
    public static function thumbnail(string $url, ?int $width = null): string
    {
        if ($url === '') {
            return '';
        }

        $proxy = rtrim((string) config('x-resources.thumbnail_proxy'), '/');

        if ($proxy === '') {
            return $url;
        }

        $width ??= (int) config('x-resources.thumbnail_width');

        return $proxy.'/?url='.rawurlencode(self::sourceUrl($url, $proxy))
            .'&w='.$width.'&output=jpg&bg=white';
    }

    /**
     * YouTube 视频封面：固定使用 hqdefault，它是真 JPEG 且体积可控。
     *
     * maxresdefault 对不少视频并不存在（404），带 sqp 参数的地址则会返回 WebP。
     */
    public static function youtubeThumbnail(string $vid, ?int $width = null): string
    {
        return self::thumbnail("https://i.ytimg.com/vi/{$vid}/hqdefault.jpg", $width);
    }

    /**
     * 取出用于代理的原始地址：已经是代理地址时还原回源地址，
     * 避免重复包装，也让历史上手写的代理地址补齐尺寸与格式参数。
     */
    private static function sourceUrl(string $url, string $proxy): string
    {
        if (Str::startsWith($url, $proxy)) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $url = is_string($query['url'] ?? null) && $query['url'] !== '' ? $query['url'] : $url;
        }

        return Str::after(Str::after($url, 'https://'), 'http://');
    }
}
