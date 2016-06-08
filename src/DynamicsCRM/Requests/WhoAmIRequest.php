<?php
namespace DynamicsCRM\Requests;

use DOMDocument;
use DynamicsCRM\Response\WhoAmIResponse;

class WhoAmIRequest extends Request
{
    public function getAction() {
        return 'Execute';
    }

    public function getRequestXML() {
        return '
<s:Body>
	<Execute xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
		<request i:type="c:WhoAmIRequest" xmlns:b="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance" xmlns:c="http://schemas.microsoft.com/crm/2011/Contracts">
			<b:Parameters xmlns:d="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
			<b:RequestId i:nil="true"/>
			<b:RequestName>WhoAmI</b:RequestName>
		</request>
	</Execute>
</s:Body>';
    }

    public function createResponse(DOMDocument $document)
    {
        return new WhoAmIResponse($document);
    }
}