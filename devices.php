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
    
    $devices["heatpumpmonitor"] = array(
        "name"=>"Heatpump Monitor",
        "description"=>"ESP WIFI Heatpump Monitor",
        "nodename"=>"0",
        "inputnames"=>array("OEMct1","OEMct1Wh","KSheat","KSflowT","KSreturnT","KSkWh","PulseIRDA"),
        "inputs"=>array(),
        // Available configurations
        "configurations"=>array(
            // Home Energy Monitoring 
            "hpconfig1"=>array(
                "name"=>"OEM CT1 + Kamstrup 402 MBUS", 
                "image"=>"HeatpumpMonitor.png", 
                "inputprocessing"=>array(
                    "OEMct1"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"heatpump_elec")
                    ),
                    "OEMct1Wh"=>array(
                        array("process"=>"multiply", "value"=>0.001),
                        array("process"=>"wh_accumulator", "feedname"=>"heatpump_elec_kwh")
                    ),
                    "KSheat"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"heatpump_heat"),
                        array("process"=>"power_to_kwh", "feedname"=>"heatpump_heat_kwh")
                    ),
                    "KSflowT"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"heatpump_flowT")
                    ),
                    "KSreturnT"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"heatpump_returnT")
                    ),
                    "KSkWh"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"KSkWh")
                    )
                )
            ),
            "hpconfig2"=>array(
                "name"=>"Elster A100C IRDA + Kamstrup 402 MBUS", 
                "image"=>"HeatpumpMonitor.png", 
                "inputprocessing"=>array(
                    "PulseIRDA"=>array(
                        array("process"=>"multiply", "value"=>0.001),
                        array("process"=>"wh_accumulator", "feedname"=>"heatpump_elec_kwh")
                    ),
                    "KSheat"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"heatpump_heat"),
                        array("process"=>"power_to_kwh", "feedname"=>"heatpump_heat_kwh")
                    ),
                    "KSflowT"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"heatpump_flowT")
                    ),
                    "KSreturnT"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"heatpump_returnT")
                    ),
                    "KSkWh"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"KSkWh")
                    )
                )
            ),
            "hpconfig3"=>array(
                "name"=>"Pulse Count + Kamstrup 402 MBUS", 
                "image"=>"HeatpumpMonitor.png", 
                "inputprocessing"=>array(
                    "PulseIRDA"=>array(
                        array("process"=>"multiply", "value"=>0.001),
                        array("process"=>"wh_accumulator", "feedname"=>"heatpump_elec_kwh")
                    ),
                    "KSheat"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"heatpump_heat"),
                        array("process"=>"power_to_kwh", "feedname"=>"heatpump_heat_kwh")
                    ),
                    "KSflowT"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"heatpump_flowT")
                    ),
                    "KSreturnT"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"heatpump_returnT")
                    ),
                    "KSkWh"=>array(
                        array("process"=>"log_to_feed", "feedname"=>"KSkWh")
                    )
                )
            )
        )
    );

    return $devices;
}
// energy.emoncms.org set configuration, nodename:emontx, configuration:solar


