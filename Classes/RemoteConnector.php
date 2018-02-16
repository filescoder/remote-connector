<?php

class RemoteConnector
{
    protected $url; // hold the URL
    protected $remoteFile;  // hold the content of the file
    protected $error; // hold the error message

    public function __construct ($url)
    {
        $this->url = $url;
        $this->checkURL();
    }

    protected function checkURL()
    {
        $flags = FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED;
        $urlOK = filter_var($this->url, FILTER_VALIDATE_URL, $flags);
        if (!$urlOK)
            throw new Exception($this->url . ' is not a valid URL');
    }
    
}