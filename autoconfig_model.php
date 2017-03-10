<?php
/*
     All Emoncms code is released under the GNU Affero General Public License.
     See COPYRIGHT.txt and LICENSE.txt.

     ---------------------------------------------------------------------
     Emoncms - open source energy visualisation
     Part of the OpenEnergyMonitor project:
     http://openenergymonitor.org

*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class AutoConfig
{
    private $mysqli;
    private $redis;
    private $devices;
    private $input;
    private $feed;

    public function __construct($mysqli,$redis,$devices,$input,$feed)
    {
        $this->mysqli = $mysqli;
        $this->redis = $redis;
        $this->devices = $devices;
        $this->input = $input;
        $this->feed = $feed;
    }
    
    // ------------------------------------------------------------------------------------------------------------------------------------------------------
    // GET DEVICE LIST
    // ------------------------------------------------------------------------------------------------------------------------------------------------------
    public function get_device_list($userid) {
        $userid = (int) $userid;
        $devices = $this->devices;
        $inputs = $this->input->getlist($userid);
        
        $detected_devices = array();
        
        foreach ($inputs as $inp) {
            $inp = (object) $inp;
            $node = $inp->nodeid;
            $name = $inp->name;
            
            foreach ($devices as $devicekey=>$device) {
                // Check if device has been configured
                $devices->$devicekey->configured = false;
                
                $stmt = $this->mysqli->prepare("SELECT configuration FROM autoconfig WHERE device=? AND userid=?");
                $stmt->bind_param("si", $devicekey, $userid);
                $stmt->execute();
                $stmt->store_result();
               
                if ($stmt->num_rows) {
                    $stmt->bind_result($configuration);
                    $stmt->fetch();
                    
                    $devices->$devicekey->configured = $configuration;
                }
                $stmt->close();
                
                // Load device inputs if present
                foreach ($devices->$devicekey->inputnames as $inputname) {
                    if ($devices->$devicekey->nodename==$node && $inputname==$name) {
                        $devices->$devicekey->inputs[$inputname] = array("id"=>$inp->id,"processList"=>$inp->processList);
                    }
                }
            }
        }

        // Check if device inputs match user inputs
        foreach ($devices as $devicekey=>$device) {
            $devices->$devicekey->detected = true;
            foreach ($devices->$devicekey->inputnames as $inputname) {
                if (!isset($devices->$devicekey->inputs[$inputname])) $devices->$devicekey->detected = false;
            }
        }
        
        $devices = $this->verify($userid,$devices);
        
        foreach ($devices as $devicekey=>$device) {
            if ($devices->$devicekey->detected) $detected_devices[$devicekey] = $devices->$devicekey;
        }
        
        return $detected_devices;
    }

    // ------------------------------------------------------------------------------------------------------------------------------------------------------
    // VERIFY
    // ------------------------------------------------------------------------------------------------------------------------------------------------------
    private function verify($userid,$devices) {
        $userid = (int) $userid;
        // Verify configuration
        foreach ($devices as $devicekey=>$device) {
            $verify = true;
            $verifylog = "";
            if ($devices->$devicekey->detected) {
                $selected_config = $devices->$devicekey->configured;
                if ($selected_config) {
                    $configuration = $device->configurations->$selected_config;
                    
                    foreach ($device->inputs as $inputname=>$inp) {
                        $processlist = explode(",",$inp['processList']);
                    
                        if (isset($configuration->inputprocessing->$inputname)) {
                            $i = 0;
                            foreach ($configuration->inputprocessing->$inputname as $process) 
                            { 
                                if (isset($processlist[$i]) && $processlist[$i]!=null) {
                                    $processparts = explode(":",$processlist[$i]);
                                    $processid = $processparts[0];
                                    
                                    if ($process->process=="log_to_feed") {
                                        if ($processid!=1) { $verify = false; $verifylog .= "- Expected log_to_feed (1) found processid:$processid<br>"; }
                                    }
                                    if ($process->process=="wh_accumulator") {
                                        if ($processid!=34) { $verify = false; $verifylog .= "- Expected wh_accumulator (34) found processid:$processid<br>"; }
                                    }
                                    if ($process->process=="power_to_kwh") {
                                        if ($processid!=4) { $verify = false; $verifylog = "- Expected power_to_kwh (4) found processid:$processid<br>"; }
                                    }
                                    if ($process->process=="subtract_input") {
                                        if ($processid!=22) { $verify = false; $verifylog .= "- Expected subtract_input (22) found processid:$processid<br>"; } 
                                    }
                                    if ($process->process=="allow_positive") {
                                        if ($processid!=24) { $verify = false; $verifylog .= "- Expected allow_positive (44) found processid:$processid<br>"; } 
                                    }
                                    if ($process->process=="multiply") {
                                        if ($processid!=2) { $verify = false; $verifylog .= "- Expected multiply (2) found processid:$processid<br>"; } 
                                    }
                                } else {
                                    $verify = false;
                                    $verifylog .= "- Missing process <b>".$process->process."</b> on input <b>$inputname</b><br>"; 
                                }
                                
                                if ($process->process=="log_to_feed") { 
                                    if (!$this->feed->get_id($userid,$process->feedname)) { $verify = false; $verifylog .= "- Missing feed <b>".$process->feedname."</b><br>"; } 
                                }
                                if ($process->process=="wh_accumulator") { 
                                    if (!$this->feed->get_id($userid,$process->feedname)) { $verify = false; $verifylog .= "- Missing feed <b>".$process->feedname."</b><br>"; } 
                                }
                                if ($process->process=="power_to_kwh") {
                                    if (!$this->feed->get_id($userid,$process->feedname)) { $verify = false; $verifylog .= "- Missing feed <b>".$process->feedname."</b><br>"; } 
                                }
                                
                                $i++;
                            }
                        }
                    }
                }
            }
            $devices->$devicekey->verified = $verify;
            $devices->$devicekey->verifylog = $verifylog;
        }
        
        return $devices;
    }
    
    // ------------------------------------------------------------------------------------------------------------------------------------------------------
    // CONFIGURE
    // ------------------------------------------------------------------------------------------------------------------------------------------------------
    public function configure($userid,$device,$configuration) {
        $userid = (int) $userid;
        $device_out = preg_replace('/[^\p{N}\p{L}_\s-]/u','',$device);
        if ($device_out!=$device) return false;
        $configuration_out = preg_replace('/[^\p{N}\p{L}_\s-]/u','',$configuration);
        if ($configuration_out!=$configuration) return false;
        
        // ------------------------------------------
        // Save configuration to autoconfig database
        
        $stmt = $this->mysqli->prepare("SELECT * FROM autoconfig WHERE device=? AND userid=?");
        $stmt->bind_param("si", $device, $userid);
        $stmt->execute();
        $stmt->store_result();
                
        if ($stmt->num_rows==1) {
            $stmt->close();
            $stmt = $this->mysqli->prepare("UPDATE autoconfig SET configuration=? WHERE device=? AND userid=?");
            $stmt->bind_param("ssi", $configuration, $device, $userid);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt->close();
            $stmt = $this->mysqli->prepare("INSERT INTO autoconfig (userid,device,configuration) VALUES (?,?,?)");
            $stmt->bind_param("iss", $userid, $device, $configuration);
            $stmt->execute();
            $stmt->close();
        }
        // ------------------------------------------
        
        $devices = $this->get_device_list($userid);
        $inputs = $this->input->getlist($userid);
        $devices[$device]->selected_config = $configuration;
        
        $log = "";
        
        // ------------------------------------------------------------------------------------------------------------------------------------------------------
        // For each present device with configuration set
        // ------------------------------------------------------------------------------------------------------------------------------------------------------
        foreach ($devices as $devicekey=>$device) {
            if ($device->detected && isset($device->selected_config)) {
            
                // Load device configuration
                $selconf = $device->selected_config;
                $configuration = $device->configurations->$selconf;
                
                // for each of the device inputs
                foreach ($device->inputs as $inputname=>$inp) {
                    $log .= $devicekey." ".$inputname." ".$inp['id']."\n";
                    
                    if ($inp['processList']!="") {
                        $this->input->set_processlist($inp['id'], "");
                        $inp['processList'] = "";
                    }
                    
                    // Where input is unconfigured
                    if ($inp['processList']=="") {
                        
                        // Load processes for input
                        if (isset($configuration->inputprocessing->$inputname)) {
                            foreach ($configuration->inputprocessing->$inputname as $process) 
                            {   
                                // ------------------------------------------------------------------------------------------------------------------------------------------------------
                                // Log to feed
                                // ------------------------------------------------------------------------------------------------------------------------------------------------------
                                if ($process->process=="log_to_feed") {
                                    // Check to see if feed exists
                                    if (!$feedid = $this->feed->get_id($userid,$process->feedname)) {
                                        $log .= "- creating feed ".$process->feedname.": ";
                                        
                                        // local emoncms
                                        $result = $this->feed->create($userid,"",$process->feedname,DataType::REALTIME,Engine::PHPFINA,(object) array("interval"=>10));
                                        // emoncms.org
                                        // $result = $this->feed->create($userid,$process->feedname,DataType::REALTIME,Engine::PHPFINA,(object) array("interval"=>10),0);
                                        
                                        if ($result->success) {
                                            $log .= "ok\n";
                                            $feedid = $result->feedid;
                                        }
                                    }
                                    $log .= "- add log_to_feed process to input: ";
                                    $result = $this->process_add($this->input,$inp['id'],1,$feedid); // processid:1 Log to feed
                                    if ($result->success) $log .= "ok\n";
                                }
                                
                                // ------------------------------------------------------------------------------------------------------------------------------------------------------
                                // Log to feed
                                // ------------------------------------------------------------------------------------------------------------------------------------------------------
                                if ($process->process=="wh_accumulator") {
                                    // Check to see if feed exists
                                    if (!$feedid = $this->feed->get_id($userid,$process->feedname)) {
                                        $log .= "- creating feed ".$process->feedname.": ";
                                        
                                        // local emoncms
                                        $result = $this->feed->create($userid,"",$process->feedname,DataType::REALTIME,Engine::PHPFINA,(object) array("interval"=>10));
                                        // emoncms.org
                                        // $result = $this->feed->create($userid,$process->feedname,DataType::REALTIME,Engine::PHPFINA,(object) array("interval"=>10),0);
                                        
                                        if ($result->success) {
                                            $log .= "ok\n";
                                            $feedid = $result->feedid;
                                        }
                                    }
                                    $log .= "- add wh_accumulator process to input: ";
                                    $result = $this->process_add($this->input,$inp['id'],34,$feedid); // processid:34 wh_accumulator
                                    if ($result->success) $log .= "ok\n";
                                }
                                
                                // ------------------------------------------------------------------------------------------------------------------------------------------------------
                                // power to kwh
                                // ------------------------------------------------------------------------------------------------------------------------------------------------------
                                if ($process->process=="power_to_kwh") {
                                    // Check to see if feed exists
                                    if (!$feedid = $this->feed->get_id($userid,$process->feedname)) {
                                        $log .= "- creating feed ".$process->feedname.": ";
                                        
                                        // local emoncms
                                        $result = $this->feed->create($userid,"",$process->feedname,DataType::REALTIME,Engine::PHPFINA,(object) array("interval"=>10));
                                        // emoncms.org
                                        // $result = $this->feed->create($userid,$process->feedname,DataType::REALTIME,Engine::PHPFINA,(object) array("interval"=>10),0);
                                        
                                        if ($result->success) {
                                            $log .= "ok\n";
                                            $feedid = $result->feedid;
                                        }
                                    }
                                    $log .= "- add power_to_kwh process to input: ";
                                    $result = $this->process_add($this->input,$inp['id'],4,$feedid); // processid:4 power_to_kwh
                                    if ($result->success) $log .= "ok\n";
                                }
                                
                                // ------------------------------------------------------------------------------------------------------------------------------------------------------
                                // subtract input
                                // ------------------------------------------------------------------------------------------------------------------------------------------------------
                                if ($process->process=="subtract_input") {
                                    $log .= "- add subtract_input process to input: ";
                                    $input_to_subtract = $device->inputs[$process->inputname]->id;
                                    $result = $this->process_add($this->input,$inp['id'],22,$input_to_subtract);
                                    if ($result->success) $log .= "ok\n";
                                }
                                
                                // ------------------------------------------------------------------------------------------------------------------------------------------------------
                                // allow_positive
                                // ------------------------------------------------------------------------------------------------------------------------------------------------------
                                if ($process->process=="allow_positive") {
                                    $log .= "- add allow_positive process to input: ";
                                    $result = $this->process_add($this->input,$inp['id'],24,null);
                                    if ($result->success) $log .= "ok\n";
                                }
                                
                                // ------------------------------------------------------------------------------------------------------------------------------------------------------
                                // multiply
                                // ------------------------------------------------------------------------------------------------------------------------------------------------------
                                if ($process->process=="multiply") {
                                    $log .= "- add multiply process to input: ";
                                    $result = $this->process_add($this->input,$inp['id'],2,$process->value);
                                    if ($result->success) $log .= "ok\n";
                                }
                                
                            } // foreach process
                        } // if processes attached
                    } // if unconfigured
                } // foreach input 
            } // if detected
        } // foreach device
        
        return $log;
    }
    
    private function process_add($input,$inputid,$processid,$arg)
    {
        $list = $input->get_processlist($inputid);
        if ($list) $list .= ',';
        $list .= $processid.':'.$arg;
        return $input->set_processlist($inputid, $list);
    }
}

