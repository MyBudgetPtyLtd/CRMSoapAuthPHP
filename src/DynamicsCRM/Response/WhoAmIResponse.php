<?php
namespace DynamicsCRM\Response;

use DOMDocument;

class WhoAmIResponse extends Response
{
    private $userId;

    public function getUserId()
    {
        return $this->userId;
    }

    protected function parseResponse(DOMDocument $document)
    {
        $values = $document->getElementsByTagName ( "KeyValuePairOfstringanyType" );

        $userId = null;

        foreach ( $values as $value ) {
            if ($value->firstChild->textContent == "UserId") {
                $userId = $value->lastChild->textContent;
            }
        }

        $this->userId = $userId;
    }
}