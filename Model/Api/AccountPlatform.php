<?php

namespace Yotpo\Yotpo\Model\Api;

use Yotpo\Yotpo\Model\AbstractApi;

class AccountPlatform extends AbstractApi
{
    const PATH = 'account_platform';

    /**
     * @method updateMetadata
     * @param  int|null $storeId
     * @return array
     */
    public function updateMetadata($storeId = null)
    {
        $result = [];
        try {
            if (!($token = $this->oauthAuthentication($storeId))) {
                throw new \Exception(__("Please make sure the APP KEY and SECRET you've entered are correct"));
            }

            $result = $this->sendApiRequest(self::PATH . "/update_metadata", [
                'utoken'   => $token,
                'app_key'  => $this->_yotpoConfig->getAppKey($storeId),
                'metadata' => [
                    'platform'       => 'magento2',
                    'version'        => "{$this->_yotpoConfig->getMagentoPlatformVersion()} {$this->_yotpoConfig->getMagentoPlatformEdition()}",
                    'plugin_version' => $this->_yotpoConfig->getModuleVersion(),
                ],
            ]);
            if ($result['status'] !== 200) {
                throw new \Exception(__("Request to API failed! " . json_encode($result)));
            }
        } catch (\Exception $e) {
            $this->_yotpoConfig->log("AccountPlatform::updateMetadata() - exception: " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error", ['$storeId' => $storeId]);
        }
        return $result;
    }
}
