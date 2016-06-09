<?php
namespace DynamicsCRM\Authentication\Token;

use DynamicsCRM\Guid;

class OnPremisesAuthenticationToken extends AuthenticationToken {
    public $X509IssuerName;
    public $X509SerialNumber;
    public $SignatureValue;
    public $DigestValue;
    public $Created;
}