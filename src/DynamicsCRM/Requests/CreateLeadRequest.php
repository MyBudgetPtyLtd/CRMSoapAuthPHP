<?php
namespace DynamicsCRM\Requests;

use DOMDocument;
use DynamicsCRM\Response\CreateEntityResponse;

class CreateLeadRequest extends Request
{
    private $firstName;
    private $lastName;
    private $userId;

    public function setFirstName($firstName) {
        $this->firstName = $firstName;
        return $this;
    }

    public function setLastName($lastName) {
        $this->lastName = $lastName;
        return $this;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function getAction() {
        return 'Create';
    }

    public function createResponse(DOMDocument $document)
    {
        return new CreateEntityResponse($document);
    }
}