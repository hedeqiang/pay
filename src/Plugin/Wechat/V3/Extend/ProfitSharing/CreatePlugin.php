<?php

declare(strict_types=1);

namespace Yansongda\Pay\Plugin\Wechat\V3\Extend\ProfitSharing;

use Closure;
use Yansongda\Artful\Contract\PluginInterface;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Artful\Exception\InvalidConfigException;
use Yansongda\Artful\Exception\InvalidParamsException;
use Yansongda\Artful\Exception\ServiceNotFoundException;
use Yansongda\Artful\Logger;
use Yansongda\Artful\Rocket;
use Yansongda\Pay\Exception\DecryptException;
use Yansongda\Pay\Exception\Exception;
use Yansongda\Pay\Pay;
use Yansongda\Supports\Collection;

use function Yansongda\Pay\encrypt_wechat_contents;
use function Yansongda\Pay\get_provider_config;
use function Yansongda\Pay\get_wechat_public_key;
use function Yansongda\Pay\get_wechat_serial_no;
use function Yansongda\Pay\get_wechat_type_key;

/**
 * @see https://pay.weixin.qq.com/docs/merchant/apis/profit-sharing/orders/create-order.html
 * @see https://pay.weixin.qq.com/docs/partner/apis/profit-sharing/orders/create-order.html
 */
class CreatePlugin implements PluginInterface
{
    /**
     * @throws ContainerException
     * @throws DecryptException
     * @throws InvalidConfigException
     * @throws InvalidParamsException
     * @throws ServiceNotFoundException
     */
    public function assembly(Rocket $rocket, Closure $next): Rocket
    {
        Logger::debug('[Wechat][Extend][ProfitSharing][CreatePlugin] 插件开始装载', ['rocket' => $rocket]);

        $params = $rocket->getParams();
        $config = get_provider_config('wechat', $params);
        $payload = $rocket->getPayload();

        if (is_null($payload)) {
            throw new InvalidParamsException(Exception::PARAMS_NECESSARY_PARAMS_MISSING, '参数异常: 缺少请求分账参数');
        }

        if (Pay::MODE_SERVICE === ($config['mode'] ?? Pay::MODE_NORMAL)) {
            $data = $this->service($payload, $params, $config);
        }

        $rocket->mergePayload(array_merge(
            [
                '_method' => 'POST',
                '_url' => 'v3/profitsharing/orders',
                '_service_url' => 'v3/profitsharing/orders',
            ],
            $data ?? $this->normal($payload, $params, $config),
        ));

        Logger::info('[Wechat][Extend][ProfitSharing][CreatePlugin] 插件装载完毕', ['rocket' => $rocket]);

        return $next($rocket);
    }

    /**
     * @throws ContainerException
     * @throws DecryptException
     * @throws InvalidConfigException
     * @throws InvalidParamsException
     * @throws ServiceNotFoundException
     */
    protected function normal(Collection $payload, array $params, array $config): array
    {
        $data = [
            'appid' => $config[get_wechat_type_key($params)] ?? '',
        ];

        if (!$payload->has('receivers.0.name')) {
            return $data;
        }

        return array_merge($data, $this->encryptSensitiveData($params, $config, $payload));
    }

    /**
     * @throws ContainerException
     * @throws DecryptException
     * @throws InvalidConfigException
     * @throws InvalidParamsException
     * @throws ServiceNotFoundException
     */
    protected function service(Collection $payload, array $params, array $config): array
    {
        $wechatTypeKey = get_wechat_type_key($params);

        $data = [
            'sub_mchid' => $payload->get('sub_mchid', $config['sub_mch_id'] ?? ''),
            'appid' => $config[$wechatTypeKey] ?? '',
        ];

        if ('PERSONAL_SUB_OPENID' === $payload->get('receivers.0.type')) {
            $data['sub_appid'] = $config['sub_'.$wechatTypeKey] ?? '';
        }

        if (!$payload->has('receivers.0.name')) {
            return $data;
        }

        return array_merge($data, $this->encryptSensitiveData($params, $config, $payload));
    }

    /**
     * @throws ContainerException
     * @throws DecryptException
     * @throws InvalidConfigException
     * @throws InvalidParamsException
     * @throws ServiceNotFoundException
     */
    protected function encryptSensitiveData(array $params, array $config, Collection $payload): array
    {
        $data['_serial_no'] = get_wechat_serial_no($params);

        $config = get_provider_config('wechat', $params);
        $publicKey = get_wechat_public_key($config, $data['_serial_no']);

        foreach ($payload->get('receivers', []) as $key => $list) {
            $data['receivers'][$key]['name'] = encrypt_wechat_contents($list['name'], $publicKey);
        }

        return $data;
    }
}
