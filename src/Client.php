<?php

namespace Electrum;

use Electrum\Request\Exception\BadRequestException;
use Electrum\Response\Exception\ElectrumResponseException;

/**
 * @author Pascal Krason <p.krason@padar.io>
 */
class Client
{
    /**
     * JSONRPC Host
     * @var string
     */
    private $host = '';

    /**
     * JSONRPC Port
     * @var int
     */
    private $port = 0;

    /**
     * JSONRPC User Name
     * @var string
     */
    private $username = null;

    /**
     * JSONRPC Password
     * @var string
     */
    private $password = null;

    /**
     * Last Message-ID
     * @var int
     */
    private $id = 0;

    /**
     * @param string $host
     * @param int    $port
     * @param int    $id
     * @param string $username
     * @param string $password
     */
    public function __construct(
        $host = 'http://127.0.0.1',
        $port = 7777,
        $id = 0,
        $username = null,
        $password = null
    ) {
        $this->setHost($host);
        $this->setPort($port);
        $this->setId($id);
        $this->setUsername($username);
        $this->setPassword($password);
    }

    /**
     * Execute JSONRPC Request
     *
     * @param       $method
     * @param array $params
     *
     * @return mixed
     * @throws BadRequestException
     * @throws ElectrumResponseException
     */
    public function execute($method, $params = [])
    {
        // Create request payload
        $request = $this->createRequest($method, $params);

        // Retrieve electrum api response
        $response = $this->executeCurlRequest($request);

        // Check if an error occured
        if(isset($response['error'])) {

            // ### Set message
            throw ElectrumResponseException::createFromElectrumResponse($response);
        }

        return $response['result'];
    }

    /**
     * Create request payload
     *
     * @param       $method
     * @param array $params
     *
     * @return mixed
     */
    private function createRequest($method, array $params)
    {
        // Build request string
        $request = json_encode([
            'method' => $method,
            'params' => $params,
            'id'     => $this->getNextId(),
        ]);

        // Replace braces
        return str_replace(['[{', '}]'], ['{', '}'], $request);
    }

    /**
     * Create curl instance & execute the request
     * @param $request
     *
     * @return mixed
     * @throws BadRequestException
     */
    private function executeCurlRequest($request)
    {
        // Create CURL instance
        $curl = curl_init(vsprintf(
            '%s:%s', [$this->getHost(), $this->getPort()]
        ));

        // Set some options we need
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $request,
        ]);

        // Authorization
        if ($this->getUsername() && $this->getPassword()) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $this->getUsername() . ":" . $this->getPassword());
        }

        // Execute request & convert data to array
        $response = curl_exec($curl);

        // Catch error if occured
        $error = curl_error($curl);

        // Check if request was successfull
        if ($error) {

            // Set last error, so user can catch it
            throw new BadRequestException($error);
        }

        // Return Data converted to an array
        return json_decode($response, true);
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     *
     * @return Client
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     *
     * @return Client
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return Client
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return Client
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getNextId()
    {
        return $this->id++;
    }

    /**
     * @param int $id
     *
     * @return Client
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
