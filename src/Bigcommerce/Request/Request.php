<?php
/**
 * @category   Folio3
 * @package    MSWApp
 * @copyright  Copyright (c) 2020 Folio3 (https://www.folio3.com/)
 */
namespace Oseintow\Bigcommerce\Request;

use Illuminate\Support\Facades\Log;
use Oseintow\Bigcommerce\Bigcommerce;
use Bigcommerce\Api\Connection;

class Request
{
    /**
     * @var Bigcommerce
     */
    protected $bigcommerce;

    /**
     * @var BrandModel
     */
    protected $brandModel;

    /**
     * Request constructor.
     * @param Bigcommerce $bigcommerce
     */
    public function __construct(Bigcommerce $bigcommerce)
    {
        $this->bigcommerce = $bigcommerce;
        //$this->bigcommerce->setApiVersion('v3');
    }

    /**
     * @param $method
     * @param $params
     * @param string $apiVersion
     * @return string
     */
    public function send($method, $params, $apiVersion = 'v3')
    {
        $this->bigcommerce->setApiVersion($apiVersion);
        $response = "";
        try {
            Log::info("Requested Endpoint: " . $method . $params);
            $response = $this->bigcommerce->setStoreHash(env("BC_STORE_HASH"))
                ->setAccessToken(env("BC_ACCESS_TOKEN"))
                ->get($method . $params);
        } catch (Exception $e) {
            Log::critical("Endpoint: " . $method . $params . " | Exception " . $e->getMessage());
        }
        return $response;
    }

    /**
     * @param $method
     * @param $params
     * @param string $apiVersion
     * @return mixed
     */
    public function sendPost($method, $params, $apiVersion = 'v3')
    {
        $this->bigcommerce->setApiVersion($apiVersion);
        $response = "";
        try {
            Log::info("Requested Endpoint: " . $method . $params);
            $response = $this->bigcommerce->setStoreHash(env("BC_STORE_HASH"))
                ->setAccessToken(env("BC_ACCESS_TOKEN"))
                ->post($method, $params);

            Log::info("Response " . json_encode($response));
        } catch (Exception $e) {
            Log::critical("Endpoint: " . $method . json_encode($params) . " | Exception " . $e->getMessage());
        }
        return $response;
    }

    public function sendPut($method, $params, $apiVersion = 'v3')
    {
        $this->bigcommerce->setApiVersion($apiVersion);
        $response = "";
        try {
            Log::info("Requested Endpoint: " . $method . json_encode($params));
            $response = $this->bigcommerce->setStoreHash(env("BC_STORE_HASH"))
                ->setAccessToken(env("BC_ACCESS_TOKEN"))
                ->put($method, $params);

            Log::info("Response " . json_encode($response));
        } catch (Exception $e) {
            Log::critical("Endpoint: " . $method . json_encode($params) . " | Exception " . $e->getMessage());
        }
        return $response;
    }

    /**
     * @param $method
     * @param $params
     * @param string $apiVersion
     * @return string
     */
    public function delete($method, $params, $apiVersion = 'v3')
    {
        $this->bigcommerce->setApiVersion($apiVersion);
        $response = "";
        try {
            Log::info("Requested Endpoint: " . $method . $params);
            $response = $this->bigcommerce->setStoreHash(env("BC_STORE_HASH"))
                ->setAccessToken(env("BC_ACCESS_TOKEN"))
                ->delete($method, $params);

            Log::info("Response " . json_encode($response));
        } catch (Exception $e) {
            Log::critical("Endpoint: " . $method . json_encode($params) . " | Exception " . $e->getMessage());
        }
        return $response;
    }
}

