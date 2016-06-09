<?php
namespace DynamicsCRM\Requests;

use DOMDocument;
use DynamicsCRM\Response\WhoAmIResponse;

class WhoAmIRequest extends Request
{
    public function getAction() {
        return 'Execute';
    }

    public function createResponse(DOMDocument $document)
    {
        return new WhoAmIResponse($document);
    }
}