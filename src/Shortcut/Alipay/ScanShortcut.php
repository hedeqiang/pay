<?php

declare(strict_types=1);

namespace Yansongda\Pay\Shortcut\Alipay;

use Yansongda\Pay\Contract\ShortcutInterface;
use Yansongda\Pay\Plugin\Alipay\AddRadarPlugin;
use Yansongda\Pay\Plugin\Alipay\AddSignaturePlugin;
use Yansongda\Pay\Plugin\Alipay\FormatBizContentPlugin;
use Yansongda\Pay\Plugin\Alipay\Pay\Scan\PayPlugin;
use Yansongda\Pay\Plugin\Alipay\ResponsePlugin;
use Yansongda\Pay\Plugin\Alipay\StartPlugin;
use Yansongda\Pay\Plugin\Alipay\VerifySignaturePlugin;
use Yansongda\Pay\Plugin\ParserPlugin;

class ScanShortcut implements ShortcutInterface
{
    public function getPlugins(array $params): array
    {
        return [
            StartPlugin::class,
            PayPlugin::class,
            FormatBizContentPlugin::class,
            AddSignaturePlugin::class,
            AddRadarPlugin::class,
            VerifySignaturePlugin::class,
            ResponsePlugin::class,
            ParserPlugin::class,
        ];
    }
}