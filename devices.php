<?php

function get_devices() {

    $devices = array();

    $devices["emontx_v3"] = array(
        "name"=>"EmonTx v3",
        "description"=>"Energy Monitoring Node",
        "nodename"=>"10",
        "inputnames"=>array("1","2","3"),
        "inputs"=>array(),
        // Available configurations
        "configurations"=>array(
            // Home Energy Monitoring 
            "home"=>array(
                "name"=>"Home Energy Monitor", 
                "image"=>"HomeEnergySmall.png", 
                "inputprocessing"=>array(
                    "1"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"use"),
                        array("process"=>"power_to_kwh", "feedname"=>"use_kwh")
                    )
                ),
                "dashboards"=>array("myelectric")
            ),
            // Solar PV and household consumption
            "solar"=>array(
                "name"=>"Solar PV", 
                "image"=>"SolarEnergySmall.png", 
                "inputprocessing"=>array(
                    "1"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"use"),
                        array("process"=>"power_to_kwh", "feedname"=>"use_kwh"),
                        array("process"=>"subtract_input", "inputname"=>"2"),
                        array("process"=>"allow_positive"),
                        array("process"=>"log_to_feed", "feedname"=>"import"),
                        array("process"=>"power_to_kwh", "feedname"=>"import_kwh")
                    ),
                    "2"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"solar"),
                        array("process"=>"power_to_kwh", "feedname"=>"solar_kwh")
                    )
                ),
                "dashboards"=>array("myelectric","mysolarpv")
            )
        )
    );

    $devices["emonth"] = array(
        "name"=>"EmonTH",
        "description"=>"Room temperature and humidity node",
        "nodename"=>"18",
        "inputnames"=>array("1","2"),
        "inputs"=>array(),
        // Available configurations
        "configurations"=>array()
    );

    return $devices;
}
// energy.emoncms.org set configuration, nodename:emontx, configuration:solar


