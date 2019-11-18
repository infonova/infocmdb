<?php

function response($status, $response)
{
    echo $status . "\n";
    echo base64_encode(serialize($response)), "\n";
}

function processresponse($string)
{
    $parts = explode("\n", $string);
    unset($string);
    $status = $parts[0];
    $data   = unserialize(base64_decode($parts[1]));
    unset($parts);
    return array("status" => $status, "data" => $data);
}