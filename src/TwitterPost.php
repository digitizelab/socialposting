<?php

namespace SocialPosting;

class TwitterPost
{

    protected $session;
    protected $config;
    protected $connection;


    /**
     * This class requires abraham/twitteroauth to function properly!
     * Needs properly formatted $config array to be passed, more info on Readme.md
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->session = &$_SESSION;
    }

    /**
     * Check if the user has authorized the application previously
     * @return bool
     */
    public function validateTwitterCredentials()
    {
        if (!isset($this->session['twitter_access_token'])) {
            return false;
        }
        return true;
    }

    /**
     * If not authorized, build the URL to authorize the app
     *
     * @return string
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     */
    public function buildTwitterAuthorizeUrl()
    {
        $initialConnection = new \Abraham\TwitterOAuth\TwitterOAuth($this->config['CONSUMER_KEY'], $this->config['CONSUMER_SECRET']);
        $request_token = $initialConnection->oauth('oauth/request_token', array('oauth_callback' => $this->config['OAUTH_CALLBACK']));
        $this->session['twitter_oauth_token'] = $request_token['oauth_token'];
        $this->session['twitter_oauth_token_secret'] = $request_token['oauth_token_secret'];
        return $initialConnection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
    }

    /**
     * After authorize call back call this method with request parameters to get the Twitter access token
     * Once the token is recieved, it will be persisted in the session
     *
     * @param $oauth_token
     * @param $oauth_verifier
     * @return \Abraham\TwitterOAuth\TwitterOAuth
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     */
    public function setAccessToken($oauth_token, $oauth_verifier)
    {
        $initialConnection = new \Abraham\TwitterOAuth\TwitterOAuth($this->config['CONSUMER_KEY'], $this->config['CONSUMER_SECRET'], $this->session['twitter_oauth_token'], $this->session['twitter_oauth_token_secret']);
        $access_token = $initialConnection->oauth("oauth/access_token", ["oauth_token" => $oauth_token, "oauth_verifier" => $oauth_verifier]);
        $this->session['twitter_access_token'] = $access_token;
        return $this->setAuthenticatedConnection($access_token);
    }

    /**
     * Set & return the authenticated URL needed for Twitter calls
     * @return \Abraham\TwitterOAuth\TwitterOAuth
     */
    protected function setAuthenticatedConnection()
    {
        return $this->connection = new \Abraham\TwitterOAuth\TwitterOAuth($this->config['CONSUMER_KEY'], $this->config['CONSUMER_SECRET'], $this->session['twitter_access_token']['oauth_token'], $this->session['twitter_access_token']['oauth_token_secret']);
    }

    /**
     * Get currently authenticated user's profile details
     *
     * @return array|object
     */
    public function getUserProfile()
    {
        return $this->setAuthenticatedConnection()->get("account/verify_credentials");
    }

    /**
     * Post the status
     * @param String $status
     * @return array|object
     */
    public function setStatus($status){
        return $this->setAuthenticatedConnection()->post("statuses/update", ["status" => $status]);
    }
    
}