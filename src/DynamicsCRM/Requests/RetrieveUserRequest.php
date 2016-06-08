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
    private $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }


    public function getAction()
    {
        return "Execute";
    }

    public function getRequestXML()
    {
        $xml = '
<s:Body>
	<Execute xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
		<request i:type="a:RetrieveRequest" xmlns:a="http://schemas.microsoft.com/xrm/2011/Contracts">
			<a:Parameters xmlns:b="http://schemas.datacontract.org/2004/07/System.Collections.Generic">
				<a:KeyValuePairOfstringanyType>
					<b:key>Target</b:key>
					<b:value i:type="a:EntityReference">
						<a:Id>' . $this->userId . '</a:Id>
						<a:LogicalName>systemuser</a:LogicalName>
						<a:Name i:nil="true" />
					</b:value>
				</a:KeyValuePairOfstringanyType>
				<a:KeyValuePairOfstringanyType>
					<b:key>ColumnSet</b:key>
					<b:value i:type="a:ColumnSet">
						<a:AllColumns>false</a:AllColumns>
						<a:Columns xmlns:c="http://schemas.microsoft.com/2003/10/Serialization/Arrays">
							<c:string>firstname</c:string>
							<c:string>lastname</c:string>
						</a:Columns>
					</b:value>
				</a:KeyValuePairOfstringanyType>
			</a:Parameters>
			<a:RequestId i:nil="true" />
			<a:RequestName>Retrieve</a:RequestName>
		</request>
	</Execute>
</s:Body>
	';
        return $xml;
    }

    public function createResponse(DOMDocument $document)
    {
        return new RetrieveUserResponse($document);
    }
}