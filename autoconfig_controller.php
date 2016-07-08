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

function autoconfig_controller()
{
    global $mysqli,$redis,$session,$route,$feed_settings;
    
    require_once "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis,$feed_settings);

    require_once "Modules/input/input_model.php";
    $input = new Input($mysqli,$redis, $feed);
    
    require_once "Modules/autoconfig/devices.php";
    require_once "Modules/autoconfig/autoconfig_model.php";
    $autoconfig = new AutoConfig($mysqli,$redis,get_devices(),$input,$feed);
    
    $result = false;
    if (!$session['write']) return array('content'=>$result);

    // Main view
    if ($route->action=="") $result = view("Modules/autoconfig/autoconfig_view.php",array());

    if ($route->action=="devicelist") {
        $route->format = "json";
        $result = $autoconfig->get_device_list($session['userid']);
    }
    
    if ($route->action=="configure") {
        $route->format = "text";
        $result = $autoconfig->configure($session['userid'],get('device'),get('configuration'));
    }

    return array('content'=>$result);
}
