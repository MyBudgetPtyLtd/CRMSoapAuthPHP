<?php
namespace DynamicsCRM\Requests;

use DOMDocument;

abstract class Request
{
    public abstract function getAction();
    public abstract function createResponse(DOMDocument $document);

    public function getRequestTemplateFilename() {
        return join('', array_slice(explode('\\', get_class($this)), -1));
    }
}