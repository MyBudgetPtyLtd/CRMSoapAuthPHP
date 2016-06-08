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

    public function getRequestXML() {
        return '
<s:Body>
	<Create xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
		<entity xmlns:a="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:b="http://schemas.datacontract.org/2004/07/System.Collections.Generic" xmlns:c="http://www.w3.org/2001/XMLSchema" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
			<a:Attributes>
				<a:KeyValuePairOfstringanyType>
					<b:key>leadqualitycode</b:key>
					<b:value i:type="a:OptionSetValue">
						<a:Value>2</a:Value>
					</b:value>
				</a:KeyValuePairOfstringanyType>
				<a:KeyValuePairOfstringanyType>
					<b:key>statuscode</b:key>
					<b:value i:type="a:OptionSetValue">
						<a:Value>1</a:Value>
					</b:value>
				</a:KeyValuePairOfstringanyType>
				<a:KeyValuePairOfstringanyType>
					<b:key>ownerid</b:key>
					<b:value i:type="a:EntityReference">
						<a:Id>{'.$this->userId.'}</a:Id>
						<a:LogicalName>systemuser</a:LogicalName>
						<a:Name i:nil="true" />
					</b:value>
				</a:KeyValuePairOfstringanyType>
				<a:KeyValuePairOfstringanyType>
					<b:key>subject</b:key>
					<b:value i:type="c:string">'.$this->firstName.' '.$this->lastName.'</b:value>
				</a:KeyValuePairOfstringanyType>
				<a:KeyValuePairOfstringanyType>
					<b:key>lastname</b:key>
					<b:value i:type="c:string">'.$this->lastName.'</b:value>
				</a:KeyValuePairOfstringanyType>
				<a:KeyValuePairOfstringanyType>
					<b:key>preferredcontactmethodcode</b:key>
					<b:value i:type="a:OptionSetValue">
						<a:Value>3</a:Value>
					</b:value>
				</a:KeyValuePairOfstringanyType>
				<a:KeyValuePairOfstringanyType>
					<b:key>donotemail</b:key>
					<b:value i:type="c:boolean">0</b:value>
				</a:KeyValuePairOfstringanyType>
				<a:KeyValuePairOfstringanyType>
					<b:key>donotbulkemail</b:key>
					<b:value i:type="c:boolean">0</b:value>
				</a:KeyValuePairOfstringanyType>
				<a:KeyValuePairOfstringanyType>
					<b:key>donotphone</b:key>
					<b:value i:type="c:boolean">0</b:value>
				</a:KeyValuePairOfstringanyType>
				<a:KeyValuePairOfstringanyType>
					<b:key>donotsendmm</b:key>
					<b:value i:type="c:boolean">0</b:value>
				</a:KeyValuePairOfstringanyType>
			</a:Attributes>
			<a:EntityState i:nil="true" />
			<a:FormattedValues />
			<a:Id>00000000-0000-0000-0000-000000000000</a:Id>
			<a:LogicalName>lead</a:LogicalName>
			<a:RelatedEntities />
		</entity>
	</Create>
</s:Body>';
    }

    public function createResponse(DOMDocument $document)
    {
        return new CreateEntityResponse($document);
    }
}