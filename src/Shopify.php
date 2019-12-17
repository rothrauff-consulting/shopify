<?php

namespace RothrauffConsulting\Shopify;

use RothrauffConsulting\Shopify\Request;

class Shopify
{
    protected $shop_url;
    protected $app_api_key;
    protected $app_password;
    protected $api_version;

    private $supported_versions = ['2019-10'];

    public function __construct($shop_url, $app_api_key, $app_password, $api_version = null)
    {
        $this->shop_url = $shop_url;
        $this->app_api_key = $app_api_key;
        $this->app_password = $app_password;
        $this->api_version = $api_version;

        if(!isset($this->api_version))
            $this->api_version = $this->supported_versions[0];

        if(!in_array($this->api_version, $this->supported_versions))
            throw new ConfigurationException('Unsupported Shopify API version');
    }

    private function buildURL($endpoint, $params = null)
    {
        $url = "https://{$this->app_api_key}:{$this->app_password}@{$this->shop_url}/admin/api/{$this->api_version}/{$endpoint}.json?";
        if(isset($params))
            foreach($params as $key => $value)
                $url .= $key.'='.urlencode($value).'&';
        return substr($url, 0, -1);
    }

    public function get($endpoint, Array $params = null)
    {
        if(!isset($params))
            $params = ['limit' => 250];
        elseif(!array_key_exists('limit', $params))
            $params['limit'] = 250;

        $results = [];
        
        do
        {
            $continue = false;
            $response = Request::get($this->buildURL($endpoint, $params));

            if(isset($response['body']))
                $results = array_merge_recursive($results, $response['body']);

            if($params['limit'] == 250 && array_key_exists('link', $response['headers']))
            {
                if(preg_match("/<([^>]*)>; rel=\"next\"/", $response['headers']['link'][0], $match))
                {
                    $continue = true;
                    $params = [];
                    foreach(explode('&', parse_url($match[1], PHP_URL_QUERY)) as $raw)
                    {
                        $exploded = explode('=', $raw);
                        $params[$exploded[0]] = $exploded[1];
                    }
                }
            }

        }while($continue);

        return $results;
    }

    public function post($endpoint, Array $data)
    {
        if(count($data) == 0)
            throw new ApiException('Cannot post an empty array');
        return Request::post($this->buildURL($endpoint), $data)['body'];
    }

    public function put($endpoint, Array $data)
    {
        if(count($data) == 0)
            throw new ApiException('Cannot put an empty array');
        return Request::put($this->buildURL($endpoint), $data)['body'];
    }

    public function delete($endpoint, Array $params = null)
    {
        Request::delete($this->buildURL($endpoint, $params));
    }
}
