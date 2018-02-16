<?php

class RemoteConnector
{
    protected $url; // hold the URL
    protected $remoteFile;  // hold the content of the file
    protected $error; // hold the error message
    protected $urlParts;

    public function __construct ($url)
    {
        $this->url = $url;
        $this->checkURL();
        if (ini_get('allow_url_fopen')) {
            $this->accessDirect();
        } else if (function_exists('curl_init')) {
            $this->useCurl();
        } else {
            $this->useSocket();
        }
    }

    protected function checkURL()
    {
        $flags = FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED;
        $urlOK = filter_var($this->url, FILTER_VALIDATE_URL, $flags);
        $this->urlParts = parse_url($this->url);
        $domainOK = preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $this->url);
        if (!$urlOK || !$domainOK)
            throw new Exception($this->url . ' is not a valid URL');
    }

    protected function accessDirect()
    {
        echo 'allow_url_fopen is enabled';
    }

    protected function useCurl()
    {
        echo 'cURL is enabled';
    }

    protected function useSocket()
    {
        echo 'Will use a socket connection';
    }
    
}