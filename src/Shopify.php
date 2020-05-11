<?php

namespace RothrauffConsulting\Shopify;

use RothrauffConsulting\Shopify\Request;

class Shopify
{
    protected $shop_url;
    protected $app_api_key;
    protected $app_password;
    protected $api_version;
    protected $retry_count;
    protected $max_retries;

    private $supported_versions = ['2019-10', '2020-01', '2020-04'];

    public function __construct($shop_url, $app_api_key, $app_password, $api_version = null)
    {
        $this->shop_url = $shop_url;
        $this->app_api_key = $app_api_key;
        $this->app_password = $app_password;
        $this->api_version = $api_version;
        $this->retry_count = 0;
        $this->max_retries = getenv('SHOPIFY_RETRY_COUNT') ? getenv('SHOPIFY_RETRY_COUNT') : 0;

        if(!isset($this->api_version))
            $this->api_version = $this->supported_versions[0];

        if(!in_array($this->api_version, $this->supported_versions))
            throw new ConfigurationException('Unsupported Shopify API version.');
    }

    private function buildURL($endpoint, $params = null)
    {
        $url = "https://{$this->app_api_key}:{$this->app_password}@{$this->shop_url}/admin/api/{$this->api_version}/{$endpoint}.json?";
        if(isset($params))
            foreach($params as $key => $value)
                $url .= $key.'='.$value.'&';
        return substr($url, 0, -1);
    }

    public function get($endpoint, Array $params = null)
    {
        if(isset($params) && array_key_exists('limit', $params))
            $limit = $params['limit'];

        if(!isset($params))
            $params = ['limit' => 250];
        elseif(!array_key_exists('limit', $params))
            $params['limit'] = 250;
        elseif($params['limit'] > 250)
            $params['limit'] = 250;


        $results = [];
        do
        {
            $continue = false;
            $response = $this->execute('get', $this->buildURL($endpoint, $params));
            
            if(isset($response['body']))
                if(is_array($response['body']))
                    $results = array_merge_recursive($results, $response['body']);
                else
                    return $response['body'];

            if(isset($limit))
                if(count($results) >= $limit)
                    return array_splice($results, 0, $limit);

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
        return $this->execute('post', $this->buildURL($endpoint), $data)['body'];
    }

    public function put($endpoint, Array $data)
    {
        if(count($data) == 0)
            throw new ApiException('Cannot put an empty array');
        return $this->execute('put', $this->buildURL($endpoint), $data)['body'];
    }

    public function delete($endpoint, Array $params = null)
    {
        $this->execute('delete', $this->buildURL($endpoint, $params));
    }

    private function execute($type, $url, $data = null)
    {
        try
        {
            switch($type)
            {
                case 'get':
                    return Request::get($url);
                case 'post':
                    return Request::post($url, $data);
                case 'put':
                    return Request::put($url, $data);
                case 'delete':
                    return Request::delete($url);
            }
        }
        catch(\Exception $e)
        {
            if($this->retry_count < $this->max_retries)
            {
                $this->retry_count++;
                if(getenv('SHOPIFY_TIMEOUT_SECONDS'))
                    usleep(getenv('SHOPIFY_TIMEOUT_SECONDS') * 1000000);
                return $this->execute($type, $url, $data);
            }
            else
                throw $e;
        }
    }
}
