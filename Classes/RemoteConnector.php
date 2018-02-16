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

    public function __toString()
    {
        if (!$this->remoteFile)
            $this->remoteFile = '';
        return $this->remoteFile;
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
        $this->remoteFile = @ file_get_contents($this->url);
        $headers = @get_headers($this->url);
    }

    protected function useCurl()
    {
        if ($session = curl_init($this->url)) {
            // Suppress the HTTP headers
            curl_setopt($session, CURLOPT_HEADER, false);

            // Return the remote file as a string
            // rather than output it directly
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

            // Get the remote file and store it in the $remoteFile property
            $this->remoteFile = curl_exec($session);

            // Get the HTTP status
            $this->status = curl_getinfo($session, CURLINFO_HTTP_CODE);

            // Close the cURL session
            curl_close($session);        
        } else {
            $this->error = 'Cannot establish cURL session';
        }
    }

    protected function useSocket()
    {
        echo 'Will use a socket connection';
    }
    
}