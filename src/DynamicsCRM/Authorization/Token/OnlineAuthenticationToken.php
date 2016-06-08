<?php
namespace DynamicsCRM\Authorization\Token;

use DynamicsCRM\Guid;

class OnlineAuthenticationToken extends AuthenticationToken {

    /**
     * Gets a CRM Online SOAP header.
     *
     * @return String The XML SOAP header to be used in future requests.
     * @param String $action
     *        	The Soap action being performed.
     */
    function CreateSoapHeader($action) {
        $xml = '
<s:Header xmlns:a="http://www.w3.org/2005/08/addressing">
	<a:Action s:mustUnderstand="1">http://schemas.microsoft.com/xrm/2011/Contracts/Services/IOrganizationService/'.$action.'</a:Action>
		<Security xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
			<EncryptedData Id="Assertion0" Type="http://www.w3.org/2001/04/xmlenc#Element" xmlns="http://www.w3.org/2001/04/xmlenc#">
				<EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#tripledes-cbc"/>
				<ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
					<EncryptedKey>
						<EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p"/>
						<ds:KeyInfo Id="keyinfo">
							<wsse:SecurityTokenReference xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
								<wsse:KeyIdentifier EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary" ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509SubjectKeyIdentifier">' . $this->KeyIdentifier . '</wsse:KeyIdentifier>
							</wsse:SecurityTokenReference>
						</ds:KeyInfo>
						<CipherData>
							<CipherValue>' . $this->Token1 . '</CipherValue>
						</CipherData>
					</EncryptedKey>
				</ds:KeyInfo>
				<CipherData>
					<CipherValue>' . $this->Token2 . '</CipherValue>
				</CipherData>
			</EncryptedData>
		</Security>
		<a:MessageID>urn:uuid:' . Guid::newGuid() . '</a:MessageID>
		<a:ReplyTo>
			<a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address>
		</a:ReplyTo>
	<a:To s:mustUnderstand="1">' . $this->Url . 'XRMServices/2011/Organization.svc</a:To>
</s:Header>
		';

        return $xml;
    }
}