<?php

namespace DynamicsCRM\Response;


use DOMDocument;

class CreateEntityResponse extends Response
{
    private $entityId;

    public function getEntityId()
    {
        return $this->entityId;
    }

    protected function parseResponse(DOMDocument $document)
    {
        $result = $document->getElementsByTagName ( "CreateResult" );
        $this->entityId = $result->item(0)->firstChild->textContent;
    }
}