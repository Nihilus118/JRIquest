<?php

abstract class HTTPMethods
{
    const GET = "GET";
    const HEAD = "HEAD";
    const POST = "POST";
    const PUT = "PUT";
    const DELETE = "DELETE";
    const CONNECT = "CONNECT";
    const OPTIONS = "OPTIONS";
    const TRACE = "TRACE";
    const PATCH = "PATCH";
}

class JRIquest
{
    protected $ch;
    protected $uri;
    protected $params;
    protected $method;
    protected $headers;
    protected $body;
    protected $connect_timeout = 3;
    protected $timeout = 20;
    protected $http_version;
    protected $ssl_verify = 1;

    function __construct(string $uri, string $method = HTTPMethods::GET, array $params = [], array $headers = [], string $body = "")
    {
        $this->ch = curl_init();
        $this->uri = $uri;
        $this->method = $method;
        $this->params = http_build_query($params);
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * @return JRIsponse
     * Sends the request with the currently set parameters and returns the response.
     */
    public function send(): JRIsponse
    {
        if (substr($this->uri, strlen($this->uri), 1) !== "/") {
            $this->uri .= "/";
        }
        curl_setopt($this->ch, CURLOPT_URL, $this->uri . $this->params);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, ($this->ssl_verify ? 2 : 0));
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, ($this->ssl_verify ? 2 : 0));
        curl_setopt($this->ch, CURLOPT_HEADER, true);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->method);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
        if (count($this->headers) > 0) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->headers);
        }
        if ($this->body != "") {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->body);
        }
        $response = curl_exec($this->ch);
        $this->http_version = curl_getinfo($this->ch, CURLINFO_HTTP_VERSION);
        return new JRIsponse($this->ch, $response);
    }

    function __toString()
    {
        return $this->method . " " . $this->uri . $this->params . " HTTP/" . $this->http_version . "\n" .
            (count($this->headers) > 0 ? implode("\n", $this->headers) . "\n" : '') .
            ($this->body != "" ? "\n" . $this->body . "\n" : '');
    }

    /**
     * @param bool $ssl_verify
     * @return JRIquest
     * Value will be used to set CURLOPT_SSL_VERIFYHOST and CURLOPT_SSL_VERIFYPEER before sending the request.
     */
    public function setSslVerify(bool $ssl_verify): JRIquest
    {
        $this->ssl_verify = $ssl_verify;
        return $this;
    }

    /**
     * @param int $timeout
     * @return JRIquest
     * Sets the timeout for the request
     */
    public function setTimeout(int $timeout): JRIquest
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @param int $connect_timeout
     * @return JRIquest
     * Sets the connection timeout for the request. 
     */
    public function setConnectTimeout(int $connect_timeout): JRIquest
    {
        $this->connect_timeout = $connect_timeout;
        return $this;
    }

    /**
     * @param string $uri
     * @return JRIquest
     * Sets the uri for the requests. You can include parameters here or set them via JRIQuest->setParams()
     */
    public function setUri(string $uri): JRIquest
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @param array $params
     * @param string $numeric_prefix
     * @param string $arg_separator
     * @param int $encoding_type
     * @return JRIquest
     * Passed values will be appended to the uri via http_build_query.
     */
    public function setParams(
        array $params,
        string $numeric_prefix = "",
        string $arg_separator = "&",
        int $encoding_type = PHP_QUERY_RFC1738
    ): JRIquest {
        $this->params = http_build_query($params, $numeric_prefix, $arg_separator, $encoding_type);
        return $this;
    }

    /**
     * @param string $method
     * @return JRIquest
     * Set the request method either by passing a string or using a constant of the class HTTPMethods.
     */
    public function setMethod(string $method): JRIquest
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @param array $headers
     * @return JRIquest
     * Sets the headers for request.
     */
    public function setHeaders(array $headers): JRIquest
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @param string $body
     * @return JRIquest
     * Set the body for the request.
     */
    public function setBody(string $body): JRIquest
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @param int $curlopt
     * @param $value
     * @return bool
     * Set $curlopt to $value.
     */
    public function setCurlopt(int $curlopt, $value): bool
    {
        return curl_setopt($this->ch, $curlopt, $value);
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        parse_str($this->params, $result);
        return $result;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return int
     */
    public function getConnectTimeout(): int
    {
        return $this->connect_timeout;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @return string
     */
    public function getHttpVersion(): string
    {
        return ($this->http_version ?? false);
    }

    /**
     * @return int
     */
    public function getSslVerify(): int
    {
        return $this->ssl_verify;
    }
}

class JRIsponse
{
    protected $response_code;
    protected $content;
    protected $content_type;
    protected $headers = [];
    protected $error_code = 0;
    protected $error_msg;
    protected $total_time;
    protected $body_raw;
    protected $headers_raw;
    protected $parsing_error = false;

    function __construct($ch, $response)
    {
        if ($response === false) {
            $this->error_code = curl_errno($ch);
            $this->error_msg = curl_error($ch);
            return $this;
        }
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->headers_raw = substr($response, 0, $header_size);
        foreach (explode("\r\n", $this->headers_raw) as $i => $line) {
            if ($i === 0)
                $this->headers['Http-Code'] = $line;
            else {
                $tmp = explode(': ', $line);
                if (isset($tmp[0]) && isset($tmp[1])) {
                    $this->headers[$tmp[0]] = $tmp[1];
                }
            }
        }
        unset($tmp);
        $this->body_raw = substr($response, $header_size);
        $this->response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $this->total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000;
        $this->content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        if (strpos($this->content_type, 'application/json') !== false) {
            $this->content = json_decode($this->body_raw);
            if (json_last_error() != JSON_ERROR_NONE) {
                $this->parsing_error = json_last_error_msg();
            }
        } else if (strpos($this->content_type, 'application/xml') !== false) {
            libxml_use_internal_errors(true);
            $this->content = simplexml_load_string($this->body_raw, SimpleXMLElement::class, LIBXML_NOWARNING);
            if ($this->content === false) {
                $this->parsing_error = "Failed to parse XML: " . implode("\n", libxml_get_errors()) . "\n";
            }
            libxml_clear_errors();
        } else {
            $this->content = $this->body_raw;
        }
        return $this;
    }

    function __toString()
    {
        $str = ($this->isSuccess() ? "SUCCESS" : "ERROR") . " " . $this->response_code . "\nTime: " . $this->total_time . " ms\n\nHeaders:\n";
        foreach ($this->headers as $key => $value) {
            $str .= "\t" . $key . ": " . $value . "\n";
        }
        $str .= "\nContent:\n" . $this->body_raw;
        return $str;
    }

    /**
     * @return bool
     * Indicates a successfull request.
     */
    public function isSuccess(): bool
    {
        return ($this->error_code !== 0 || $this->response_code >= 200 && $this->response_code < 400);
    }

    /**
     * @return bool
     * Indicates that there was an error.
     */
    public function isError(): bool
    {
        return !$this->isSuccess();
    }

    /**
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->error_code;
    }

    /**
     * @return string
     */
    public function getErrorMsg(): string
    {
        return $this->error_msg;
    }

    /**
     * @return float|int
     */
    public function getTotalTime()
    {
        return $this->total_time;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->content_type;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getBodyRaw(): string
    {
        return $this->body_raw;
    }

    /**
     * @return mixed
     * Returns the response content parsed into an object or false if there was an error while parsing.
     */
    public function getContent()
    {
        if ($this->parsing_error !== false) {
            return false;
        }
        return $this->content;
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->response_code;
    }

    /**
     * @return string
     */
    public function getParsingError(): string
    {
        return $this->parsing_error;
    }

    /**
     * @return string
     * Returns the errorcode and errormessage as string.
     */
    public function getError(): string
    {
        if (!$this->isError()) {
            return false;
        }
        if ($this->error_code !== 0) {
            return "Code: " . $this->error_code . "\nMessage:\n" . $this->error_msg . "\n";
        } else {
            return "Code: " . $this->response_code . "\nHeaders:\n" . $this->headers_raw .  "Body:\n" . trim($this->body_raw) . "\n";
        }
    }

    /**
     * @return string
     * Returns the errorcode and errormessage as html-string.
     */
    public function getErrorHTML(): string
    {
        if (!$this->isError()) {
            return false;
        }
        if ($this->error_code !== 0) {
            return '<div><p><b>Code:</b>' . $this->error_code . "<br><b>Message:</b> " . $this->error_msg . '</p></div>';
        } else {
            return '<div><p><b>Code:</b>' . $this->response_code . "<br><b>Headers:</b> " . nl2br($this->headers_raw) . "<br><b>Body:</b> " . trim(strip_tags(nl2br($this->body_raw))) . '</p></div>';
        }
    }
}
