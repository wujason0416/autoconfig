<?php

function get_devices() {
    global $path;
    return json_decode(file_get_contents("Modules/autoconfig/devices/devices.json"));
}
// energy.emoncms.org set configuration, nodename:emontx, configuration:solar
