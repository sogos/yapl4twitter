<?php

require_once 'OAuth.class.php';

class Twitter
{
    const API_URL = 'http://api.twitter.com';
    const SEARCH_URL = 'http://search.twitter.com';
    const STREAM_URL = 'http://stream.twitter.com';
    const USERSTREAM_URL = 'https://userstream.twitter.com';

    public function Twitter($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret)
    {
        $this->oa = new OAuth($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);
    }

    public function request($url, $method, $twitter_params)
    {
        $auth_header = $this->oa->buildAuthorization($url, $method, $twitter_params);

        $request = $url . '?' . http_build_query($twitter_params, '', '&');
        $ret = $this->oa->request( $request, $method, NULL, $auth_header );

        return $ret;
    }

    public function getStreamSocket($cnx_params)
    {
        $twitter_params = array();
        $method = $cnx_params['method'];

        $scheme = ($cnx_params['scheme'] == 'http') ? 'tcp://' : 'ssl://';

        if(FALSE == array_key_exists('args', $cnx_params))
        {
            $cnx_params['args'] = array();
        }

        $url = $cnx_params['scheme'] . '://' . $cnx_params['host'] . $cnx_params['query'];

        $auth_header = $this->oa->buildAuthorization($url, $method, $cnx_params['args']);

        // Riped from Phirehose: http://code.google.com/p/phirehose/
        $ip = gethostbynamel($cnx_params['host']);
        $ip = $ip[rand(0, (count($ip) - 1))];

        $is_post = FALSE;
        if($cnx_params['method'] == 'POST')
        {
            $is_post = TRUE;
            $post_data = http_build_query($cnx_params['args']);
            /* Fix spaces (We want %20, not '+'). */
            $post_data = str_replace('+', '%20', $post_data);
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
        $query.= "Authorization: " . $auth_header . "\r\n";
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

    /* Timeline ressources */

    public function statuses_public_timeline($trim_user = FALSE, $include_entities = FALSE)
    {
        $url = Twitter::API_URL . "/1/statuses/public_timeline.json";
        $method = 'GET';

        $rep = $this->request( $url, $method, array('trim_user' => $trim_user, 'include_entities' => $include_entities) );

        return $rep;
    }

    public function statuses_home_timeline()
    {
        $url = Twitter::API_URL . "/1/statuses/home_timeline.json";
        $method = 'GET';

        $rep = $this->request( $url, $method, array() );

        return $rep;
    }

    public function user_timeline()
    {
        $url = Twitter::API_URL . "/1/statuses/user_timeline.json";
        $method = 'GET';

        $rep = $this->request( $url, $method, array() );

        return $rep;
    }

    /* Trends */
    public function trends()
    {
        $url = Twitter::API_URL . "/1/trends.json";
        $method = 'GET';

        $rep = $this->request( $url, $method, array() );

        return $rep;
    }

    public function trends_current($exclude_hash = FALSE)
    {
        $url = Twitter::API_URL . "/1/trends/current.json";
        $method = 'GET';

        $params = array();
        if(TRUE == $exclude_hash)
        {
            $params['exclude'] = 'hashtags';
        }

        $rep = $this->request( $url, $method, $params );

        return $rep;
    }

    /* Search */
    public function search($q, $opt_params = array())
    {
        $url = Twitter::SEARCH_URL . "/search.json";
        $method = 'GET';

        $params = array( 'q' => $q );
        $params = array_merge( $params, $opt_params );

        $rep = $this->request( $url, $method, $params );

        return $rep;
    }

    public function update($message)
    {
        $url = Twitter::API_URL . "/1/statuses/update.json";
        $method = 'POST';

        $twitter_params = array('status' => $message);

        $rep = $this->request( $url, $method, $twitter_params );

        return $rep;
    }

    public function users_show($user_id)
    {
        $url = Twitter::API_URL . "/1/users/show.json";
        $method = 'GET';

        $twitter_params = array('user_id' => $user_id);

        $rep = $this->request( $url, $method, $twitter_params );

        return $rep;
    }

    public function follow($screen_name)
    {
        $url = Twitter::API_URL . "/1/friendships/create.json";
        $method = 'POST';

        $twitter_params = array('screen_name' => $screen_name);

        $rep = $this->request( $url, $method, $twitter_params );

        return $rep;
    }

    public function unfollow($user_id)
    {
        $url = Twitter::API_URL . "/1/friendships/destroy.json";
        $method = 'POST';

        $twitter_params = array('user_id' => $user_id);

        $rep = $this->request( $url, $method, $twitter_params );

        return $rep;
    }

    public function get_friends($screen_name)
    {
        $url = Twitter::API_URL . "/1/friends/ids.json";
        $method = 'GET';

        $twitter_params = array('screen_name' => $screen_name);

        $rep = $this->request( $url, $method, $twitter_params );

        return $rep;
    }

    public function get_list_members($screen_name, $list_slug, $cursor = -1)
    {
        $url = Twitter::API_URL . "/1/" . $screen_name . "/" . $list_slug . "/members.json";
        $method = 'GET';

        $twitter_params = array('cursor' => $cursor);

        $rep = $this->request( $url, $method, $twitter_params );

        return $rep;
    }

    public function get_followers_ids($screen_name, $cursor = -1)
    {
        $url = Twitter::API_URL . "/1/followers/ids.json";
        $method = 'GET';

        $twitter_params = array('cursor' => $cursor);
        $twitter_params['screen_name'] = $screen_name;

        $rep = $this->request( $url, $method, $twitter_params );

        return $rep;
    }

    public function get_users_lookup($user_list)
    {
        $url = Twitter::API_URL . "/1/users/lookup.json";
        $method = 'GET';

        $twitter_params = array('user_id' => $user_list);

        $rep = $this->request( $url, $method, $twitter_params );

        return $rep;
    }

    public function getFirehoseSample()
    {
        $url = Twitter::STREAM_URL . "/1/statuses/sample.json";

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
        $url = Twitter::USERSTREAM_URL . "/2/user.json";

        $params = array(
                        'scheme' => 'https',
                        'host' => 'userstream.twitter.com',
                        'port' => 443,
                        'method' => 'GET',
                        'query' => '/2/user.json'
                       );

        $fd = $this->getStreamSocket($params);

        return $fd;
    }

    public function getFirehoseFilter($filters)
    {
        $url = Twitter::STREAM_URL . "/1/statuses/filter.json";

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
