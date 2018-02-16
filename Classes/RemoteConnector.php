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
        if (ini_get('allow_url_open')) {
            $this->accessDirect();
        } else if (function_exists('cur_init')) {
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
        $port = isset($this->urlParts['port']) ? $this->urlParts['port'] : 80;
        $remote = @ fsockopen($this->urlParts['host'], $port, $errno, $errstr, 30);

        if (!$remote) {
            $this->remoteFile = false;
            $this->error = "Couldn't create a socket connection ";
            if ($errstr)
                $this->error .= $errstr;
            else
                $this->error .= "check the domain name or IP address";
        } else {
            if (isset($this->urlParts['query'])) {
                $path = $this->urlParts['path'] . '?' . $this->urlParts['query']; 
            } else {
                $path = $this->urlParts['path'];
            }
            $out = "GET $path HTTP/1.1\r\n";
            $out .= "Host: {$this->urlParts['host']}\r\n";
            $out .= "Connection: Close\r\n\r\n";

            fwrite($remote, $out);

            $this->remoteFile = stream_get_contents($remote);
            if ($this->remoteFile)
                $this->removeHeaders();

            fclose($remote);
        }
    }

    protected function removeHeaders()
    {
        $parts = preg_split('#\r\n\r\n|\n\n#', $this->remoteFile); // Split the string based on double new line
        if (is_array($parts)) {
            $headers = array_shift($parts);
            $file = implode("\n\n", $parts);
            if (preg_match('#HTTP/1\.\d\s+(\d{3})#', $headers, $m)) {
                $this->status = $m[1];
            }
            if (preg_match('#Content-Type:([^\r\n]+)#i', $headers, $m)) {
                if (stripos($m[1], 'xml') !== false || stripos($m[1], 'html') !== false) {
                    if (preg_match('/<.+>/s', $file, $m)) {
                        $this->remoteFile = $m[0];
                    } else {
                        $this->remoteFile = trim($file);
                    }
                } else {
                    $this->remoteFile = trim($file);
                }
            } 
        }
    }

    public function getErrorMessage()
    {
        if (is_null($this->error)) {
            $this->setErrorMessage();
        } else {
            return $this->error;
        }
    }

    protected function setErrorMessage()
    {
        if ($this->status == 200 && $this->remoteFile) {
            $this->error = '';
        } else {
            switch ($this->status) {
                case 200:
                case 204:
                    $this->error = "Connection OK, but file is empty";
                    break;
                case 301:
                case 302:
                case 303:
                case 307:
                case 410:
                    $this->error = "File has been moved or does not exist";
                    break;
                case 305:
                    $this->error = "File must be accessed through a proxy";
                    break;
                case 400:
                    $this->error = "Malformed request";
                    break;
                case 404:
                    $this->error = "Fle not found";
                    break;
                case 407:
                    $this->error = "Proxy requires authentication";
                    break;
                case 408:
                    $this->error = "Reuqest timed out";
                    break;
                case 500:
                    $this->error = "The remote server encountered an internal error";
                    break;
                case 503:
                    $this->error = "The server cannot handle the request at the moment";
                    break;
                default:
                    $this->error = "Undefined error. Check URL and domain name";
                    break;
            }
        }
    }
    
}