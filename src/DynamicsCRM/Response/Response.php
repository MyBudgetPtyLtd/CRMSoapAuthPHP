<?php
namespace DynamicsCRM\Response;

use DOMDocument;

abstract class Response
{
    function __construct(DOMDocument $document)
    {
        $this->parseResponse($document);
    }

    protected abstract function parseResponse(DOMDocument $document);

}