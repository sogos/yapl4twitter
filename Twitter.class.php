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

    public function request($url, $method, $twitter_params)
    {
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

        $params = array_merge($params, $twitter_params);

        $signature = $oa->buildSignature($this->consumer_secret . '&' . $this->oauth_token_secret, $method, $url, $params);
        $params['oauth_signature'] = $signature;

        $ret = $oa->request( $url . '?' . http_build_query($twitter_params, '', '&'), $method, NULL, $oa->makeAuthorization($params) );

        return $ret;

    }

    public function user_timeline()
    {
        $url = 'http://api.twitter.com/1/statuses/user_timeline.json';
        $method = 'GET';

        $rep = $this->request( $url, $method, array() );

        return $rep;
    }

    public function update($message)
    {
        $url = 'http://api.twitter.com/1/statuses/update.json';
        $method = 'POST';

        $twitter_params = array('status' => $message);

        $rep = $this->request( $url, $method, $twitter_params );

        return $rep;
    }

    public function users_show($user_id)
    {
        $url = 'http://api.twitter.com/1/users/show.json';
        $method = 'GET';

        $twitter_params = array('user_id' => $user_id);

        $rep = $this->request( $url, $method, $twitter_params );

        return $rep;
    }

    public function follow($screen_name)
    {
        $url = 'http://api.twitter.com/1/friendships/create.json';
        $method = 'POST';

        $twitter_params = array('screen_name' => $screen_name);

        $rep = $this->request( $url, $method, $twitter_params );

        return $rep;
    }

    public function unfollow($user_id)
    {
        $url = 'http://api.twitter.com/1/friendships/destroy.json';
        $method = 'POST';

        $twitter_params = array('user_id' => $user_id);

        $rep = $this->request( $url, $method, $twitter_params );

        return $rep;
    }

    public function get_friends($screen_name)
    {
        $url = 'http://api.twitter.com/1/friends/ids.json';
        $method = 'GET';

        $twitter_params = array('screen_name' => $screen_name);

        $rep = $this->request( $url, $method, $twitter_params );

        return $rep;
    }

    public function getSampleFirehose()
    {
        $url = 'http://stream.twitter.com/1/statuses/sample.json';
        $method = 'GET';

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

        $twitter_params = array();

        $params = array_merge($params, $twitter_params);

        $signature = $oa->buildSignature($this->consumer_secret . '&' . $this->oauth_token_secret, $method, $url, $params);
        $params['oauth_signature'] = $signature;

        $fullurl = $url . '?' . http_build_query($params, '', '&');

        $fd = fopen($fullurl, 'r');

        return $fd;
    }

    public function closeSampleFirehose($fd)
    {
        fclose($fd);
    }
};
