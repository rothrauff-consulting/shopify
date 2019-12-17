<?php

namespace RothrauffConsulting\Shopify;

class Request
{
    private static function init($url)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => [
                'Accept: */*',
                'Accept-Encoding: gzip, deflate',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Accept: application/json',
            ],
        ]);

        return $curl;
    }

    public static function get($url)
    {
        $curl = self::init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        return self::send($curl);
    }

    public static function post($url, Array $data)
    {
        $curl = self::init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        return self::send($curl);
    }

    public static function put($url, Array $data)
    {
        $curl = self::init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        return self::send($curl);
    }

    public static function delete($url)
    {
        $curl = self::init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        return self::send($curl);
    }

    private static function send($curl)
    {
        usleep(500000); //prevents more than 2 calls per second api error

        $headers = [];

        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$headers) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if(count($header) < 2) //ignore invalid headers
                return $len;
            $headers[strtolower(trim($header[0]))][] = trim($header[1]);
            return $len;
        });

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if($err)
            throw new CurlException($err);

        $response = json_decode($response, true);
        if(isset($response) && array_key_exists('errors', $response))
            if(is_array($response['errors']))
                throw new ApiException($response['errors'][array_keys($response['errors'])[0]]);
            else
                throw new ApiException($response['errors']);

        return [
            'headers' => $headers,
            'body' => isset($response) && count($response) > 0 ? $response[array_keys($response)[0]] : $response,
        ];
    }
}