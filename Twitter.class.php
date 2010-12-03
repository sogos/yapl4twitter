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

    public function buildParams($url, $method, $twitter_params)
    {
        $oa = new OAuth($this->consumer_key, $this->consumer_secret);
        $nonce = sha1('nonce' + time());
        $auth_params = array(
                    'oauth_nonce' => $nonce,
                    'oauth_signature_method' => 'HMAC-SHA1',
                    'oauth_timestamp' => time(),
                    'oauth_consumer_key' => $this->consumer_key,
                    'oauth_token' => $this->oauth_token,
                    'oauth_version' => '1.0',
                  );

        $all_params = array_merge($auth_params, $twitter_params);

        $signature = $oa->buildSignature($this->consumer_secret . '&' . $this->oauth_token_secret, $method, $url, $all_params);
        $auth_params['oauth_signature'] = $signature;

        return array($auth_params, $all_params);
    }

    public function request($url, $method, $twitter_params)
    {
        $oa = new OAuth($this->consumer_key, $this->consumer_secret);
        list($auth_params, $all_params) = $this->buildParams($url, $method, $twitter_params);

        $auth_header = $oa->makeAuthorization($auth_params);

        $ret = $oa->request( $url . '?' . http_build_query($twitter_params, '', '&'), $method, NULL, $auth_header );

        return $ret;
    }

    public function getStreamSocket($cnx_params)
    {
        $twitter_params = array();
        $method = $cnx_params['method'];

        $scheme = ($cnx_params['scheme'] == 'http') ? 'tcp://' : 'ssl://';

        $url = $cnx_params['scheme'] . '://' . $cnx_params['host'] . $cnx_params['query'];
        list($auth_params, $all_params) = $this->buildParams($url, $method, $cnx_params['args']);

        $oa = new OAuth($this->consumer_key, $this->consumer_secret);
        $auth_header = $oa->makeAuthorization($auth_params);

        // Riped from Phirehose: http://code.google.com/p/phirehose/
        $ip = gethostbynamel($cnx_params['host']);
        $ip = $ip[rand(0, (count($ip) - 1))];

        if($cnx_params['method'] == 'POST')
        {
            $is_post = TRUE;
            $post_data = http_build_query($cnx_params['args']);
        }
        else
        {
            $is_port = FALSE;
        }

        $fp = fsockopen($scheme . $ip, $cnx_params['port'], $errno, $errstr, 5);

        $query = $method . " " . $cnx_params['query'] . " HTTP/1.0\r\n";
        $query.= "Host: " . $cnx_params['host'] . "\r\n";
        if(TRUE == $is_post)
        {
            $query.= "Content-Type: application/x-www-form-urlencoded\r\n";
            $query.= "Content-length: " . (strlen($post_data)) . "\r\n";
        }
        $query.= "Accept: */*\r\n";
        $query.= $auth_header . "\r\n";
        $query.= "User-Agent: Node authentication\r\n";
        $query.= "\r\n";

        if(TRUE == $is_post)
        {
            $query.= $post_data . "\r\n";
        }

        fwrite($fp, $query);

        // Retrieve headers
        list($httpVer, $httpCode, $httpMessage) = preg_split('/\s+/', trim(fgets($fp)), 3);
        if($httpCode != "200")
        {
            echo "Got error " . $httpCode . ": " . $httpMessage . "\n";
            fclose($fp);
            return NULL;
        }

        // Consume each header response line until we get to body
        while ($header = trim(fgets($fp, 4096))) {
            /* bla */
        }

        return $fp;
    }

    public function closeStreamSocket($fd)
    {
        fclose($fd);
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

    public function get_list_members($screen_name, $list_slug, $cursor = -1)
    {
        $url = 'http://api.twitter.com/1/' . $screen_name . '/' . $list_slug . '/members.json';
        $method = 'GET';

        $twitter_params = array('cursor' => $cursor);

        $rep = $this->request( $url, $method, $twitter_params );

        return $rep;
    }

    public function getFirehoseSample()
    {
        $url = 'http://stream.twitter.com/1/statuses/sample.json';

        $params = array(
                        'scheme' => 'http',
                        'host' => 'stream.twitter.com',
                        'port' => 80,
                        'method' => 'GET',
                        'query' => '/1/statuses/sample.json'
                       );

        $fd = $this->getStreamSocket($params);

        return $fd;
    }

    public function getUserStream()
    {
        $url = 'https://userstream.twitter.com/2/user.json';

        $fd = $this->getStreamSocket(array('scheme' => 'https', 'host' => 'userstream.twitter.com', 'port' => 443, 'method' => 'GET', 'query' => '/2/user.json'));

        return $fd;
    }

    public function getFirehoseFilter($filters)
    {
        $url = 'http://stream.twitter.com/1/statuses/filter.json';

        $params = array(
                        'scheme' => 'http',
                        'host' => 'stream.twitter.com',
                        'port' => 80,
                        'method' => 'POST',
                        'query' => '/1/statuses/filter.json'
                       );

        $params['args'] = $filters;
        $fd = $this->getStreamSocket($params);

        return $fd;
    }

};
