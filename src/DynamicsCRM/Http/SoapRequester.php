<?php
namespace DynamicsCRM\Http;
use DOMDocument;
use DOMElement;
use DynamicsCRM\Auth\Token\AuthenticationToken;
use DynamicsCRM\Requests\Request;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class SoapRequester
 *
 * Taken from Sixdg\DynamicsCRMConnector\Components\Soap\SoapRequester
 * @package DynamicsCRM\Http
 */
class SoapRequester
{
    public static $soapEnvelope = 'http://www.w3.org/2003/05/soap-envelope';

    public static $soapFaults = [
        'http://www.w3.org/2005/08/addressing/soap/fault',
        'http://schemas.microsoft.com/net/2005/12/windowscommunicationfoundation/dispatcher/fault',
        'http://schemas.microsoft.com/xrm/2011/Contracts/Services/IOrganizationService/ExecuteOrganizationServiceFaultFault',
        'http://schemas.microsoft.com/xrm/2011/Contracts/Services/IOrganizationService/CreateOrganizationServiceFaultFault',
        'http://schemas.microsoft.com/xrm/2011/Contracts/Services/IOrganizationService/RetrieveOrganizationServiceFaultFault',
        'http://schemas.microsoft.com/xrm/2011/Contracts/Services/IOrganizationService/UpdateOrganizationServiceFaultFault',
        'http://schemas.microsoft.com/xrm/2011/Contracts/Services/IOrganizationService/DeleteOrganizationServiceFaultFault',
        'http://schemas.microsoft.com/xrm/2011/Contracts/Services/IOrganizationService/RetrieveMultipleOrganizationServiceFaultFault',
    ];

    protected $timeout = 60;

    protected $responder = null;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $uri
     * @param Request|string $request
     *
     * @param AuthenticationToken $token
     * @return mixed
     * @throws Exception
     */
    public function sendRequest($uri, $xml)
    {
        $uri = rtrim ( $uri, "/" );


        $headers = $this->getHeaders($uri, $xml);

        $ch = $this->getCurlHandle($uri, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $this->logger->debug("Sending request to $uri");

        $domXml = new DOMDocument('1.0');
        $domXml->preserveWhiteSpace = false;
        $domXml->formatOutput = true;

        $domXml->loadXML($xml);
        $this->logger->debug("Headers :".var_export($headers));
        $this->logger->debug("Request :".$domXml->saveXML());

        $responseXml = curl_exec($ch);

        if ($responseXml != '') {
            $domXml->loadXML($responseXml);
            $this->logger->debug("Response :".$domXml->saveXML());
        }

        try {
            $this->hasError($ch, $responseXml);
        } catch (\Exception $ex) {
            throw $ex;
        }
        curl_close($ch);

        if ($this->responder) {
            $this->responder->loadXML($responseXml);

            return $this->responder;
        }

        return $responseXml;
    }

    /**
     * @param string $uri
     * @param string $request
     *
     * @return array
     */
    private function getHeaders($uri, $request)
    {
        var_export($uri);

        $urlDetails = parse_url($uri);

        var_export($urlDetails);

        return [
            "POST " . $urlDetails['path'] . " HTTP/1.1",
            "Host: " . $urlDetails['host'],
            'Connection: Keep-Alive',
            'Content-type: application/soap+xml; charset=UTF-8',
            'Content-length: ' . strlen($request)
        ];
    }

    /**
     * @param string $uri
     * @param array  $headers
     * @return resource
     */
    private function getCurlHandle($uri, $headers)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        return $ch;
    }

    /**
     * @param resource $ch
     * @param string   $responseXML
     *
     * @throws Exception
     */
    private function hasError($ch, $responseXML)
    {
        $this->testCurlResponse($ch, $responseXML);

        if ($responseXML) {
            $responseDOM = new \DOMDocument();
            $responseDOM->loadXML($responseXML);

            $this->testIsValidSoapResponse($responseDOM, $responseXML);
            $this->testIsValidSoapHeader($responseDOM, $responseXML);
            $this->testActionIsNotError($responseDOM);

            return;
        }

        throw new Exception('No response found');
    }

    /**
     * @param resource $ch
     * @param string   $responseXML
     *
     * @throws Exception
     */
    private function testCurlResponse($ch, $responseXML)
    {
        if ($responseXML === false) {
            throw new Exception('cURL Error: ' . curl_error($ch));
        }
    }

    /**
     * @param DOMDocument $responseDOM
     * @param string       $responseXML
     *
     * @throws Exception
     */
    private function testIsValidSoapResponse(DOMDocument $responseDOM, $responseXML)
    {
        if ($responseDOM->getElementsByTagNameNS(SoapRequester::$soapEnvelope, 'Envelope')->length < 1) {
            throw new Exception('Invalid SOAP Response: HTTP Response ' . $responseXML . PHP_EOL . PHP_EOL);
        }
    }

    /**
     * @param DOMDocument $responseDOM
     *
     * @return mixed
     */
    private function getEnvelope(DOMDocument $responseDOM)
    {
        return $responseDOM->getElementsByTagNameNS(SoapRequester::$soapEnvelope, 'Envelope')->item(0);
    }

    /**
     * @param DOMElement $envelope
     *
     * @return mixed
     */
    private function getHeader($envelope)
    {
        return $envelope->getElementsByTagNameNS(SoapRequester::$soapEnvelope, 'Header')->item(0);
    }

    /**
     * @param DOMElement $header
     *
     * @return mixed
     */
    private function getAction($header)
    {
        return $header->getElementsByTagNameNS('http://www.w3.org/2005/08/addressing', 'Action')->item(0);
    }

    /**
     * @param DOMDocument $responseDOM
     * @param string       $responseXML
     *
     * @throws Exception
     */
    private function testIsValidSoapHeader(DOMDocument $responseDOM, $responseXML)
    {
        $envelope = $this->getEnvelope($responseDOM);
        $header = $this->getHeader($envelope);

        if (!$header) {
            $domXml = new DOMDocument('1.0');
            $domXml->preserveWhiteSpace = false;
            $domXml->formatOutput = true;
            $domXml->loadXML($responseXML);
            $xml = $domXml->saveXML();
            echo $xml."\n";
            throw new Exception('Invalid SOAP Response: No SOAP Header!' . PHP_EOL . $xml . PHP_EOL);
        }
    }

    /**
     * @param DOMDocument $responseDOM
     *
     * @throws \Exception
     */
    private function testActionIsNotError(DOMDocument $responseDOM)
    {
        $envelope = $this->getEnvelope($responseDOM);
        $header = $this->getHeader($envelope);
        $actionString = $this->getAction($header)->textContent;

        if (in_array($actionString, self::$soapFaults)) {
            throw $this->getSoapFault($responseDOM);
        }
    }

    /**
     * @param DOMDocument $responseDOM
     *
     * @return \Exception
     */
    private function getSoapFault(DOMDocument $responseDOM)
    {
        return new \SoapFault($this->getSoapFaultCode($responseDOM), $this->getSoapFaultMessage($responseDOM));
    }

    /**
     * @param DomDocument $responseDOM
     *
     * @return string
     */
    private function getSoapFaultCode(DOMDocument $responseDOM)
    {
        /**
         * TODO Change to use xpath
         */
        $hierarchy = ['Envelope', 'Body', 'Fault', 'Code', 'Value'];
        $item = $responseDOM;
        foreach ($hierarchy as $currentLevel) {
            $item = $item->getElementsByTagNameNS('http://www.w3.org/2003/05/soap-envelope', $currentLevel)->item(0);
        }

        return $item->nodeValue;
    }

    /**
     * @param DOMDocument $responseDOM
     *
     * @return string
     */
    private function getSoapFaultMessage(DOMDocument $responseDOM)
    {
        /**
         * TODO Change to use xpath
         */
        $hierarchy = ['Envelope', 'Body', 'Fault', 'Reason', 'Text'];
        $item = $responseDOM;
        foreach ($hierarchy as $currentLevel) {
            $item = $item->getElementsByTagNameNS('http://www.w3.org/2003/05/soap-envelope', $currentLevel)->item(0);
        }

        return $item->nodeValue;
    }
}
