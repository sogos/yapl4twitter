<?php

class OAuth
{
    private $consumer_key = NULL;
    private $consumer_secret = NULL;

    public function OAuth($consumer_key, $consumer_secret)
    {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
    }

    public function _urlencode_rfc3986($input)
    {
         return str_replace('+',' ',str_replace('%7E', '~', rawurlencode($input)));
    }

    public function request($url, $method = 'GET', $post_params = NULL, $headers = NULL)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURL_HTTP_VERSION_1_1, TRUE);

        $http_headers = array();

        if($method == 'POST')
        {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            $http_headers[] = 'Expect:';
        }

        if(NULL !== $post_params)
        {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);

            $http_headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        if(NULL !== $headers && is_array($headers))
        {
            $http_headers = array_merge($http_headers, $headers);
        }

        if(count($http_headers))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $http_headers);
        }

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        unset($ch);

        return array('code' => $code, 'response' => $response);
    }

    public function buildSignature($secret, $method, $url, $params)
    {
        $str = $method . '&';
        $str.= $this->_urlencode_rfc3986($url) . '&';

        ksort($params);
        $i = 0;

        $param_str = '';
        foreach($params as $key => $value)
        {
            $param_str .= $this->_urlencode_rfc3986($key) . '=' . $this->_urlencode_rfc3986($value);
            if(++$i != count($params))
                $param_str .= '&';
        }

        $str .= $this->_urlencode_rfc3986( $param_str );

        $signed = base64_encode(hash_hmac("SHA1", $str, $secret, true));

        return $signed;
    }

    public function makeAuthorization($params)
    {
        $args = array();
        foreach($params as $key => $value)
        {
            $args[] = $this->_urlencode_rfc3986( $key ). '="' . $this->_urlencode_rfc3986($value) . '"';
        }

        $str = implode($args, ', ');

        return(array('Authorization: OAuth ' . $str . "\n"));
    }

    public function parseTokens($str)
    {
        $tokens = explode('&', $str);
        $token_arr = array();
        foreach($tokens as $token)
        {
            list($field, $value) = explode('=', $token);
            $token_arr[ $field ] = $value;
        }
        return $token_arr;
    }

    public function getRequestToken($request_token_url, $callback_url = NULL)
    {
        $method = 'GET';
        $nonce = sha1('nonce' + time());
        $params = array(
                    'oauth_consumer_key' => $this->consumer_key,
                    'oauth_nonce' => $nonce,
                    'oauth_signature_method' => 'HMAC-SHA1',
                    'oauth_timestamp' => time(),
                    'oauth_version' => '1.0',
                  );

        if(NULL !== $callback_url)
        {
            $params['oauth_callback'] = $callback_url;
        }

        $signature = $this->buildSignature($this->consumer_secret . '&', $method, $request_token_url, $params);

        $params['oauth_signature'] = $signature;

        $rep_arr = $this->request($request_token_url, $method, NULL, $this->makeAuthorization($params));
        $rep = $rep_arr['response'];

        return $this->parseTokens( $rep );
    }

    public function getAccessToken($access_token_url, $oauth_token, $oauth_verifier)
    {
        $method = 'GET';

        $nonce = sha1('nonce' + time());
        $params = array(
                    'oauth_consumer_key' => $this->consumer_key,
                    'oauth_nonce' => $nonce,
                    'oauth_signature_method' => 'HMAC-SHA1',
                    'oauth_token' => $oauth_token,
                    'oauth_timestamp' => time(),
                    'oauth_version' => '1.0',
                    'oauth_verifier' => $oauth_verifier
                );

        $signature = $this->buildSignature($this->consumer_secret . '&', $method, $access_token_url, $params);
        $params['oauth_signature'] = $signature;

        $rep_arr = $this->request($access_token_url, $method, NULL, $this->makeAuthorization($params));
        $rep = $rep_arr['response'];

        return $this->parseTokens( $rep );
    }


}
