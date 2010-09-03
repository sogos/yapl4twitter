<?php

require_once 'OAuth.class.php';

class Twitter
{
    private $consumer_key = NULL;
    private $consumer_secret = NULL;

    private $oauth_token = NULL;
    private $oauth_token_secret = NULL;

    public function Twitter($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret)
    {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;

        $this->oauth_token = $oauth_token;
        $this->oauth_token_secret = $oauth_token_secret;
    }

    public function update($message)
    {
        $url = 'http://api.twitter.com/1/statuses/update.json';
        $method = 'POST';

        $oa = new OAuth($this->consumer_key, $this->consumer_secret);
        $nonce = sha1('nonce' + time());
        $params = array(
                    'oauth_consumer_key' => $this->consumer_key,
                    'oauth_nonce' => $nonce,
                    'oauth_signature_method' => 'HMAC-SHA1',
                    'oauth_timestamp' => time(),
                    'oauth_version' => '1.0',
                    'oauth_token' => $this->oauth_token,
                  );

        $twitter_params = array('status' => $message);

        $params = array_merge($params, $twitter_params);

        $signature = $oa->buildSignature($this->consumer_secret . '&' . $this->oauth_token_secret, $method, $url, $params);
        $params['oauth_signature'] = $signature;

        $ret = $oa->request( $url . '?' . http_build_query($twitter_params, '', '&'), 'POST', NULL, $oa->makeAuthorization($params) );

        return $ret;
    }
};
