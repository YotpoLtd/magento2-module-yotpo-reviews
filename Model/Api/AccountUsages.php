<?php

namespace Yotpo\Yotpo\Model\Api;

use Yotpo\Yotpo\Model\AbstractApi;

class AccountUsages extends AbstractApi
{
    const PATH = 'account_usages';

    /**
     * @method getMetrics
     * @param  array      $storeIds
     * @param  string     $fromDate
     * @param  string     $toDate
     * @return array
     */
    public function getMetrics(array $storeIds = [], string $fromDate = null, string $toDate = null)
    {
        try {
            $appKeys = [];
            $token = null;
            foreach ($storeIds as $storeId) {
                if (($appKey = $this->_yotpoConfig->getAppKey($storeId)) && ($secret = $this->_yotpoConfig->getSecret($storeId))) {
                    if (!in_array($appKey, $appKeys)) {
                        $appKeys[] = $appKey;
                    }
                    if (!$token) {
                        if (!($token = $this->oauthAuthentication($storeId))) {
                            throw new \Exception(__("Please make sure the APP KEY and SECRET you've entered are correct"));
                        }
                    }
                }
            }
            if (!$appKeys) {
                return;
            }
            $appKey = array_shift($appKeys);

            $params = [
                'utoken'            => $token,
                'platform'          => 'magento2',
                'extension_version' => $this->_yotpoConfig->getModuleVersion(),
            ];
            if ($appKeys) {
                $params['app_key'] = $appKeys;
            }
            if ($fromDate) {
                $params['since'] = $fromDate;
            }
            if ($toDate) {
                $params['until'] = $toDate;
            }

            $result = $this->sendApiRequest("apps/" . $appKey . "/" . self::PATH . "/metrics", $params, 'get', 60, null);
            if ($result['status'] == 200 && $result['body']->response) {
                return (array)$result['body']->response;
            }
        } catch (\Exception $e) {
            $this->_yotpoConfig->log("AccountUsages::getMetrics() - exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }
    }
}
