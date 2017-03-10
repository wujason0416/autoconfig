<?php global $path; ?>
<style>

.device {
  width:300px;
  height:163px;
  float:right;
  background-image:url('<?php echo $path; ?>Modules/autoconfig/configurations/HomeEnergySmall.png');
  position:relative;
}

</style>

<br>
<div style="background-color:#fff; padding:20px;">

<h2>My Devices</h2>

<div style='font-size:18px' id="numberofdevices"></div><br>
<div id="devices"></div>
</div>

<script>

$("body").css('background-color','#eee');

var devices = {};
var selected_configuration = {};
var path = "<?php echo $path; ?>";

load();
var updater = setInterval(load,10000);

function load()
{
    $.ajax({ 
        url: path+"autoconfig/devicelist", 
        dataType: 'json', 
        async: true, 
        success: function(result) {
            devices = result;
            
            var devicenum = 0;
            for (var devicekey in devices) devicenum++;
            if (devicenum==0) $("#numberofdevices").html("No devices detected");
            if (devicenum==1) $("#numberofdevices").html("<b>1</b> device detected:");
            if (devicenum>1) $("#numberofdevices").html("<b>"+devicenum+"</b> devices detected:");
            
            var out = "";
            for (var devicekey in devices) {
                out += "<div style='background-color:#0699fa; color:#fff'>";
                out += "  <div style='padding:20px'>";
                out += "    <div style='font-size:24px'>"+devices[devicekey].name+"</div>";
                out += "    <div style='font-size:16px'>"+devices[devicekey].description+"</div>";
                out += "  </div>";
                
                out += "  <div style='padding:20px; background-color:rgba(255,255,255,0.4); font-size:16px; line-height:30px'>";
                out += "    <div class='device' device='"+devicekey+"'></div>";

                out += "    <div class='config-select-box' device='"+devicekey+"'>";
                out += "    <b>Select your configuration:</b><br>";
                for (var configkey in devices[devicekey].configurations) {
                    var configuration = devices[devicekey].configurations[configkey];
                    out += "    <input type='radio' name='"+devicekey+"' value='"+configkey+"' autocomplete='off' style='margin-top:-3px; margin-right:10px'>"+configuration.name+"<br>";
                }
                out += "    <br><button class='configure' device='"+devicekey+"'>Configure</button>";
                out += "    </div>";
                
                out += "    <div class='selected-config-box' device='"+devicekey+"' style='display:none'>";
                var configkey = devices[devicekey].configured;
                if (configkey) {
                var configuration = devices[devicekey].configurations[configkey];
                selected_configuration[devicekey] = configkey;
                out += "    <b>Selected configuration:</b> "+configuration.name;
                if (!devices[devicekey].verified) {
                    out += "<br><b>ERROR:</b> Device config verification failed:<br><div style='font-size:14px; line-height:20px'>"+devices[devicekey].verifylog+"</div>";
                    out += "<br><button class='configure' device='"+devicekey+"'>Reset and re-configure</button>";
                } else {
                    selected_configuration[devicekey] = configkey;
                    out += "<br><button class='changeconfiguration' device='"+devicekey+"'>Change configuration</button>";
                }
                }
                out += "    </div>";
                out += "    <div style='clear:both'></div>";
                out += "  </div>";
                
                out += "</div><br>";
            }
            
            $("#devices").html(out);
            
            for (var devicekey in devices) {
                var configkey = devices[devicekey].configured;
                var configuration = devices[devicekey].configurations[configkey];
                $(".device[device="+devicekey+"]").css("background-image","url('"+path+"Modules/autoconfig/configurations/"+configuration.image+"')");
                
                if (devices[devicekey].configured) {
                    $(".config-select-box[device="+devicekey+"]").hide();
                    $(".selected-config-box[device="+devicekey+"]").show();
                }
            }
        }    
    });
}

$("#devices").on("change","input[type=radio]",function(){
    var devicekey = $(this).attr("name");
    var configkey = $(this).attr("value");
    var configuration = devices[devicekey].configurations[configkey];
    selected_configuration[devicekey] = configkey;
    $(".device[device="+devicekey+"]").css("background-image","url('"+path+"Modules/autoconfig/configurations/"+configuration.image+"')");
    clearInterval(updater);
});

$("#devices").on("click",".configure",function(){
    var devicekey = $(this).attr("device");
    var configkey = selected_configuration[devicekey];
    if (configkey!=undefined) {
        $.ajax({ 
            url: path+"autoconfig/configure?device="+devicekey+"&configuration="+configkey, 
            dataType: 'text', 
            async: true, 
            success: function(result) {
                console.log(result);
                
                load();
                updater = setInterval(load,10000);
            }
        });
    }
});

$("#devices").on("click",".changeconfiguration",function(){
    var devicekey = $(this).attr("device");
    var configkey = selected_configuration[devicekey];
    
    $(".config-select-box[device="+devicekey+"]").show();
    $(".selected-config-box[device="+devicekey+"]").hide();
    clearInterval(updater);
});
</script>
