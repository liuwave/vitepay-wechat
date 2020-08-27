<?php

namespace vitepay\wechat {

    function array2xml($arr, $root = 'xml')
    {
        $xml = "<$root>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    function xml2array($xml)
    {
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    function convert_key($key, $type)
    {
        $type = strtoupper($type);

        return "-----BEGIN {$type}-----\n" .
            wordwrap($key, 64, "\n", true) .
            "\n-----END {$type}-----";
    }

}
