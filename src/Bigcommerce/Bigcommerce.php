<?php

namespace Oseintow\Bigcommerce;

use Config;
use Bigcommerce\Api\Connection as BigcommerceClient;
use Bigcommerce\Api\Client as BigcommerceCollectionResource;
use Oseintow\Bigcommerce\Exceptions\BigcommerceApiException;


class Bigcommerce
{
    protected $clientId;
    protected $clientSecret;
    protected $storeHash;
    protected $accessToken;

    protected $bigcommerce;
    protected $connection;
    protected $version = "v3";
    protected $authServiceUrl = "https://login.bigcommerce.com/";
    protected $baseApiUrl  =  "https://api.bigcommerce.com/";
    protected $redirectUrl;
    protected $resourceUri;

    public function __construct()
    {
        $this->setConnection(Config::get('bigcommerce.default'));
    }

    private function setConnection($connection)
    {
        $connections = ['oAuth', 'basicAuth'];

        if (!in_array($connection, $connections))
            throw new BigcommerceApiException("No connection set", 403);

        $this->connection = $connection;
        $this->$connection();
    }

    public function verifyPeer($option = false)
    {
        $this->bigcommerce->verifyPeer($option);

        return $this;
    }

    private function oAuth()
    {
        $this->bigcommerce = new BigcommerceClient();
        $this->clientId = Config::get('bigcommerce.' . $this->connection . '.client_id');
        $this->clientSecret = Config::get('bigcommerce.' . $this->connection . '.client_secret');
        $this->redirectUrl = Config::get('bigcommerce.' . $this->connection . '.redirect_url');
        $this->bigcommerce->addHeader("X-Auth-Client", $this->clientId);
    }

    private function basicAuth()
    {
        BigcommerceCollectionResource::configure([
            'store_url' => Config::get('bigcommerce.' . $this->connection . '.store_url'),
            'username'  => Config::get('bigcommerce.' . $this->connection . '.username'),
            'api_key'   => Config::get('bigcommerce.' . $this->connection . '.api_key')
        ]);
    }

    /*
     * Set store hash;
     */
    public function setStoreHash($storeHash)
    {
        $storeHash = explode("/", $storeHash);
        $this->storeHash = $storeHash[count($storeHash) - 1];

        return $this;
    }

    public function setApiVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    public function getAccessToken($code, $scope, $context)
    {
        $tokenUrl = $this->authServiceUrl . "oauth2/token";

        $response = $this->bigcommerce->post($tokenUrl, [
            "client_id" => $this->clientId,
            "client_secret" => $this->clientSecret,
            "redirect_uri" => $this->redirectUrl,
            "grant_type" => "authorization_code",
            "code" => $code,
            "scope" => $scope,
            "context" => $context
        ]);

        return $response;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        $this->bigcommerce->addHeader("X-Auth-Token", $accessToken);

        return $this;
    }

    /*
     *  $args[0] is for route uri and $args[1] is either request body or query strings
     */
    public function __call($method, $args)
    {
        $httpVerbs = ['get', 'post', 'put', 'delete'];
        if (in_array($method, $httpVerbs)) {
            return $this->makeHttpVerbRequest($method, $args[0], $args[1] ?? null);
        }

        return $this->makeBigcomerceCollectionRequest($method, $args);
    }

    public function makeHttpVerbRequest($httpVerb, $resource, $filters = null)
    {
        try {
            $data = $this->bigcommerce->$httpVerb($this->resourceUri($resource), $filters);

            if ($this->bigcommerce->getHeader("X-Retry-After")) {
                if ($this->bigcommerce->getHeader("X-Retry-After") > 0) {
                    sleep($this->bigcommerce->getHeader("X-Retry-After") + 5);

                    return $this->makeHttpVerbRequest($httpVerb, $resource, $filters);
                }
            }

            return $this->version == "v2" ?
                collect($data) : collect($data)->map(function ($value) {
                    return collect($value);
                });
        } catch (Exception $e) {
            throw new BigcommerceApiException($e->getMessage(), $e->getCode());
        }
    }

    public function makeBigcomerceCollectionRequest($method, $args)
    {
        try {
            if ($this->connection == "oAuth") {
                BigcommerceCollectionResource::configure([
                    'client_id'  => $this->clientId,
                    'auth_token' => $this->accessToken,
                    'store_hash' => $this->storeHash
                ]);
            }

            if ($this->version == "v3")
                throw new BigcommerceApiException("Bigcommerce collection does not support api version 3", 403);

            $data = call_user_func_array([BigcommerceCollectionResource::class, $method], $args);

            return $data;
        } catch (Exception $e) {
            throw new BigcommerceApiException($e->getMessage(), $e->getCode());
        }
    }

    public function getRequestErrors()
    {
        return collect($this->bigcommerce->getLastError());
    }

    public function resourceUri($resource)
    {
        $this->resourceUri = $this->baseApiUrl . "stores/" . $this->storeHash . "/{$this->version}/" . $resource;

        return $this->resourceUri;
    }

    public function addHeader($key, $value)
    {
        $this->bigcommerce->addHeader($key, $value);

        return $this;
    }

    public function removeHeader($header)
    {
        $this->bigcommerce->remove($header);
    }

    public function getStatus()
    {
        return $this->bigcommerce->getStatus();
    }

    public function getHeaders()
    {
        return $this->bigcommerce->getHeaders();
    }

    public function getHeader($header)
    {
        return $this->bigcommerce->getheader($header);
    }
}
