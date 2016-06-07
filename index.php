<?php
include_once 'CrmAuth.php';
include_once 'CrmExecuteSoap.php';
include_once "CrmAuthenticationHeader.php";
include_once 'config.php';

// CRM Online
$crmAuth = new CrmAuth ();
$authHeader = $crmAuth->GetHeaderOnline ( $username, $password, $url );
// End CRM Online

// CRM On Premise - IFD
// $url = "https://org.domain.com/";
// //Username format could be domain\\username or username in the form of an email
// $username = "username";
// $password = "password";

// $crmAuth = new CrmAuth();
// $authHeader = $crmAuth->GetHeaderOnPremise($username, $password, $url);
// End CRM On Premise - IFD

$userid = WhoAmI ( $authHeader, $url );
if ($userid == null)
	return;

$name = CrmGetUserName ( $authHeader, $userid, $url );
print $name;

CreateEnquiry($authHeader, $url, $userid);
return;


function CreateEnquiry($authHeader, $url, $userid) {	
	$firstName = 'Steven';
	$lastName = 'Blom';
	
	$xml = '
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
						<a:Id>{'.$userid.'}</a:Id>
						<a:LogicalName>systemuser</a:LogicalName>
						<a:Name i:nil="true" />
					</b:value>
				</a:KeyValuePairOfstringanyType>
				<a:KeyValuePairOfstringanyType>
					<b:key>subject</b:key>
					<b:value i:type="c:string">'.$firstName.' '.$lastName.'</b:value>
				</a:KeyValuePairOfstringanyType>
				<a:KeyValuePairOfstringanyType>
					<b:key>lastname</b:key>
					<b:value i:type="c:string">'.$lastname.'</b:value>
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
	$executeSoap = new CrmExecuteSoap ();
	$response = $executeSoap->SendCreateSOAPRequest ( $authHeader, $xml, $url );
	
	$domxml = new DOMDocument('1.0');
	$domxml->preserveWhiteSpace = false;
	$domxml->formatOutput = true;
	$domxml->loadXML($response);
	$response = $domxml->saveXML();
	
	echo $response;
}

function WhoAmI($authHeader, $url) {
	$xml = '
<s:Body>
	<Execute xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
		<request i:type="c:WhoAmIRequest" xmlns:b="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance" xmlns:c="http://schemas.microsoft.com/crm/2011/Contracts">
			<b:Parameters xmlns:d="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
			<b:RequestId i:nil="true"/>
			<b:RequestName>WhoAmI</b:RequestName>
		</request>
	</Execute>
</s:Body>
	';
	
	$executeSoap = new CrmExecuteSoap ();
	$response = $executeSoap->SendExecuteSOAPRequest ( $authHeader, $xml, $url );
	
	$responsedom = new DomDocument ();
	$responsedom->loadXML ( $response );
	
	$values = $responsedom->getElementsbyTagName ( "KeyValuePairOfstringanyType" );
	
	foreach ( $values as $value ) {
		if ($value->firstChild->textContent == "UserId") {
			return $value->lastChild->textContent;
		}
	}
	
	return null;
}

function CrmGetUserName($authHeader, $id, $url) {
	$xml = '
<s:Body>
	<Execute xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
		<request i:type="a:RetrieveRequest" xmlns:a="http://schemas.microsoft.com/xrm/2011/Contracts">
			<a:Parameters xmlns:b="http://schemas.datacontract.org/2004/07/System.Collections.Generic">
				<a:KeyValuePairOfstringanyType>
					<b:key>Target</b:key>
					<b:value i:type="a:EntityReference">
						<a:Id>' . $id . '</a:Id>
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
	
	$executeSoap = new CrmExecuteSoap ();
	
	$response = $executeSoap->SendExecuteSOAPRequest ( $authHeader, $xml, $url );
	
	$responsedom = new DomDocument ();
	$responsedom->loadXML ( $response );
	
	$firstname = "";
	$lastname = "";
	
	$values = $responsedom->getElementsbyTagName ( "KeyValuePairOfstringanyType" );
	
	foreach ( $values as $value ) {
		if ($value->firstChild->textContent == "firstname") {
			$firstname = $value->lastChild->textContent;
		}
		
		if ($value->firstChild->textContent == "lastname") {
			$lastname = $value->lastChild->textContent;
		}
	}
	
	return $firstname . " " . $lastname;
}

?>