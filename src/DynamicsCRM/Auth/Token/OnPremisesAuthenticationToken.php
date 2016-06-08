<?php
namespace DynamicsCRM\Auth\Token;

use DynamicsCRM\Guid;

class OnPremisesAuthenticationToken extends AuthenticationToken {
    public $X509IssuerName;
    public $X509SerialNumber;
    public $SignatureValue;
    public $DigestValue;
    public $Created;

    public function CreateSoapHeader($action)
    {
        $xml = '
<s:Header>
	<a:Action s:mustUnderstand="1">http://schemas.microsoft.com/xrm/2011/Contracts/Services/IOrganizationService/Execute</a:Action>
	<a:MessageID>urn:uuid:' . Guid::newGuid() . '</a:MessageID>
	<a:ReplyTo>
		<a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address>
	</a:ReplyTo>
	<a:To s:mustUnderstand="1">' . $this->Url . 'XRMServices/2011/Organization.svc</a:To>
	<o:Security xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
		<u:Timestamp xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" u:Id="_0">
			<u:Created>' . $this->Created . '</u:Created>
			<u:Expires>' . $this->Expires . '</u:Expires>
		</u:Timestamp>
		<xenc:EncryptedData Type="http://www.w3.org/2001/04/xmlenc#Element" xmlns:xenc="http://www.w3.org/2001/04/xmlenc#">
			<xenc:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes256-cbc"/>
			<KeyInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
				<e:EncryptedKey xmlns:e="http://www.w3.org/2001/04/xmlenc#">
					<e:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p">
						<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
					</e:EncryptionMethod>
					<KeyInfo>
						<o:SecurityTokenReference xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
							<X509Data>
								<X509IssuerSerial>
									<X509IssuerName>' . $this->X509IssuerName . '</X509IssuerName>
									<X509SerialNumber>' . $this->X509SerialNumber . '</X509SerialNumber>
								</X509IssuerSerial>
							</X509Data>
						</o:SecurityTokenReference>
					</KeyInfo>
					<e:CipherData>
						<e:CipherValue>' . $this->Token1 . '</e:CipherValue>
					</e:CipherData>
				</e:EncryptedKey>
			</KeyInfo>
			<xenc:CipherData>
				<xenc:CipherValue>' . $this->Token2 . '</xenc:CipherValue>
			</xenc:CipherData>
		</xenc:EncryptedData>
		<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
			<SignedInfo>
				<CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
				<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#hmac-sha1"/>
				<Reference URI="#_0">
					<Transforms>
						<Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
					</Transforms>
					<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
					<DigestValue>' . $this->DigestValue . '</DigestValue>
				</Reference>
			</SignedInfo>
			<SignatureValue>' . $this->SignatureValue . '</SignatureValue>
			<KeyInfo>
				<o:SecurityTokenReference xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
					<o:KeyIdentifier ValueType="http://docs.oasis-open.org/wss/oasis-wss-saml-token-profile-1.0#SAMLAssertionID">' . $this->KeyIdentifier . '</o:KeyIdentifier>
				</o:SecurityTokenReference>
			</KeyInfo>
		</Signature>
	</o:Security>
</s:Header>
		';

        return $xml;
    }
}