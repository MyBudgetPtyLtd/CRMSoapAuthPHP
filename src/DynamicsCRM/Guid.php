<?php
namespace DynamicsCRM;

class Guid
{
    public static function newGuid() {
        // http://stackoverflow.com/questions/18206851/com-create-guid-function-got-error-on-server-side-but-works-fine-in-local-usin
        if (function_exists ( 'com_create_guid' )) {
            return com_create_guid ();
        } else {
            mt_srand ( ( double ) microtime () * 10000 ); // optional for php 4.2.0 and up.
            $charId = strtoupper ( md5 ( uniqid ( rand (), true ) ) );
            $hyphen = chr ( 45 ); // "-"
            $uuid =
                chr ( 123 ) . // "{"
                substr ( $charId, 0, 8 ) . $hyphen . substr ( $charId, 8, 4 ) . $hyphen . substr ( $charId, 12, 4 ) . $hyphen . substr ( $charId, 16, 4 ) . $hyphen . substr ( $charId, 20, 12 ) .
                chr ( 125 ); // "}"
            return $uuid;
            }
    }
}