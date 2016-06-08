<?php
namespace DynamicsCRM\Requests;

use DOMDocument;

abstract class Request
{
    public abstract function getAction();
    public abstract function getRequestXML();
    public abstract function createResponse(DOMDocument $document);
}