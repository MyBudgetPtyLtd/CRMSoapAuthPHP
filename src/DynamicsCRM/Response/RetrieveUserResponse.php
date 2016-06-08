<?php

namespace DynamicsCRM\Response;

use DOMDocument;

class RetrieveUserResponse extends Response
{
    private $firstName;
    private $lastName;

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    protected function parseResponse(DOMDocument $document)
    {
        $firstName = "";
        $lastName = "";

        $values = $document->getElementsByTagName ( "KeyValuePairOfstringanyType" );

        foreach ( $values as $value ) {
            if ($value->firstChild->textContent == "firstname") {
                $firstName = $value->lastChild->textContent;
            }

            if ($value->firstChild->textContent == "lastname") {
                $lastName = $value->lastChild->textContent;
            }
        }

        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }
}