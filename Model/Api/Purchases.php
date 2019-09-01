<?php

namespace Yotpo\Yotpo\Model\Api;

use Yotpo\Yotpo\Model\AbstractApi;

class Purchases extends AbstractApi
{
    const PATH = 'purchases';

    /**
     * @method createOne
     * @param  array  $order   Order prepared by $this->prepareOrderData()
     * @param  int    $storeId
     * @return mixed
     */
    public function createOne(array $orderData, $storeId = null)
    {
        if (!($orderData['utoken'] = $this->oauthAuthentication($storeId))) {
            throw new \Exception(__("Please make sure the APP KEY and SECRET you've entered are correct"));
        }
        return $this->sendApiRequest("apps/" . $this->_yotpoConfig->getAppKey($storeId) . "/" . self::PATH, $orderData);
    }

    /**
     * @method massCreate
     * @param  array  $orders  Array of orders prepared by $this->prepareOrderData()
     * @param  mixed  $storeId
     * @return mixed
     */
    public function massCreate(array $orders, $storeId = null)
    {
        if (!($token = $this->oauthAuthentication($storeId))) {
            throw new \Exception(__("Please make sure the APP KEY and SECRET you've entered are correct"));
        }
        return $this->sendApiRequest(
            "apps/" . $this->_yotpoConfig->getAppKey($storeId) . "/" . self::PATH . "/mass_create",
            [
            'utoken'            => $token,
            'platform'          => 'magento2',
            'extension_version' => $this->_yotpoConfig->getModuleVersion(),
            'orders'            => $orders,
            ]
        );
    }
}
