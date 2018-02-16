<?php

class RemoteConnector
{
    protected $url; // hold the URL
    protected $remoteFile;  // hold the content of the file
    protected $error; // hold the error message

    public function __construct ($url)
    {
        $this->url = $url;
    }
    
}