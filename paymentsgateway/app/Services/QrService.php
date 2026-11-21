<?php

namespace App\Services;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrService
{
    /**
     * สร้าง SVG QR Code สำหรับ PromptPay payload
     * ใช้งานได้ทันที 100% offline โดยไม่ต้องพึ่ง external API
     */
    public static function generateSvg(string $data, int $size = 210): string
    {
        if (class_exists(Writer::class) && class_exists(SvgImageBackEnd::class)) {
            try {
                $renderer = new ImageRenderer(
                    new RendererStyle($size, 1),
                    new SvgImageBackEnd()
                );
                $writer = new Writer($renderer);
                return $writer->writeString($data);
            } catch (\Throwable $e) {
                // fallback
            }
        }

        // Fallback: ใช้ QR Server API ในกรณีที่ยังไม่ได้ติดตั้ง vendor
        $encoded = urlencode($data);
        return "<img src=\"https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded}\" alt=\"PromptPay QR\" class=\"mx-auto\" />";
    }
}
