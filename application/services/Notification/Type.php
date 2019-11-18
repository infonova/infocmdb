<?php

interface Notification_Type
{

    public static function handle($gateways, $parameter);
}