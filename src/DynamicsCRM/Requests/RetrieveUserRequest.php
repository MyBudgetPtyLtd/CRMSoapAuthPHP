<?php
/**
 * Created by PhpStorm.
 * User: steblo
 * Date: 8/06/2016
 * Time: 4:39 PM
 */

namespace DynamicsCRM\Requests;


use DOMDocument;
use DynamicsCRM\Response\RetrieveUserResponse;

class RetrieveUserRequest extends Request
{
    /**
     * @var
     */
    public $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }


    public function getAction()
    {
        return "Execute";
    }

    public function createResponse(DOMDocument $document)
    {
        return new RetrieveUserResponse($document);
    }
}