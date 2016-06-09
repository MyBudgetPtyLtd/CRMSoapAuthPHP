<?php
namespace DynamicsCRM\Authentication;

class CrmUser
{
    private $userId;
    private $firstName;
    private $lastName;

    function __construct($userId, $firstName, $lastName)
    {

        $this->userId = $userId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

}