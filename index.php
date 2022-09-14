<?php
include_once "common.php"; 

$mode = request_var('mode', '');

if ($mode == '') {
?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" lang="en">
    <head>
        <meta name="robots" content="index" />
        <meta name="robots" content="follow" />
        <meta name="language" content="English" />
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="cache-control" content="max-age=0" />
        <meta http-equiv="cache-control" content="no-cache, no-store, must-revalidate" />
        <meta http-equiv="expires" content="0" />
        <meta http-equiv="pragma" content="no-cache" />

        <title>Digital Voice Modem Dashboard</title>

        <link rel="stylesheet" type="text/css" href="style.css">
        <script type="text/javascript" src="jquery-3.2.1.min.js"></script>
        <script type="text/javascript">
            $.ajaxSetup({ cache: false });
        </script>
    </head>

    <body>
    <div class="container">
        <div class="header">
            <div style="font-size: 8px; text-align: left; padding-left: 8px; float: left;">Hostname: <?php echo shell_exec('cat /etc/hostname'); ?></div>
            <div style="font-size: 8px; text-align: right; padding-right: 8px; float: right;">
                <a href="/?mode=admin">Administration</a>&nbsp;&nbsp;&nbsp;
                <a href="/?mode=log">System Log</a>
            </div>
            <h1><?php echo $systemCallsign; ?> Repeater Dashboard</h1>
        </div>

        <div class="nav">
            <script type="text/javascript">
                function reloadRepeaterInfo() {
                    $("#repeaterInfo").load("/index.php?mode=repeaterinfo");
                    $(window).trigger('resize');
                }

                reloadRepeaterInfo();
                setInterval(function() { reloadRepeaterInfo() }, 1500);
            </script>
            <div id="repeaterInfo">
                <table>
                    <tr><th colspan="2">Loading ...</th></tr>
                </table>
            </div>
        </div>
        <div class="content">
            <script type="text/javascript">
                function reloadActivity() {
                    $("#activity").load("index.php?mode=activity");
                    $(window).trigger('resize');
                }
                
                reloadActivity();
                setInterval(function() { reloadActivity() }, 4500);
            </script>
            <div id="activity">
                <h3>System Activity</h3>
                <table>
                    <tr><th colspan="2">Loading system activity ... this may take some time!</th></tr>
                </table>
            </div>
        </div>
        <div class="footer"></div>
    </div>
    </body>
    </html>
<?php } elseif ($mode == 'repeaterinfo') { ?>
    <!-- Modes Panel -->
    <table>
        <tr><th colspan="2">Modes Enabled</th></tr>
        <tr><?php echo modeStatus("DMR"); ?><?php echo modeStatus("P25"); ?></tr>
    </table>

    <!-- Radio Info Panel -->
    <table>
        <tr><th colspan="2">Radio Info</th></tr>
        <tr><th>TRX</th><?php echo modemStatus(); ?></tr>
        <tr><th>TX</th><td class="table-col-white"><?php echo getMHz($_dvmhost_conf['Info']['TXFrequency']); ?></td></tr>
        <tr><th>RX</th><td class="table-col-white"><?php echo getMHz($_dvmhost_conf['Info']['RXFrequency']); ?></td></tr>
        <tr><th>TX Offset</th><td class="table-col-white"><?php echo $_dvmhost_conf['Info']['TXOffsetMhz']; ?></td></tr>
        <tr><th>Bandwidth</th><td class="table-col-white"><?php echo $_dvmhost_conf['Info']['BandwidthKhz']; ?></td></tr>
        <tr><th>Power (W)</th><td class="table-col-white"><?php echo $_dvmhost_conf['Info']['Power']; ?></td></tr>
        <tr><th>Lat</th><td class="table-col-white"><?php echo $_dvmhost_conf['Info']['Latitude']; ?></td></tr>
        <tr><th>Long</th><td class="table-col-white"><?php echo $_dvmhost_conf['Info']['Longitude']; ?></td></tr>
        <tr><th>Height (M)</th><td class="table-col-white"><?php echo $_dvmhost_conf['Info']['Height']; ?></td></tr>
        <tr><th>RID ACL</th>
        <?php
        if ($_dvmhost_conf['Radio Id']['ACL'] == 0) {
            echo '<td class="table-col-disabled">Disabled</td>';
        } else {
            echo '<td class="table-col-success">Enabled</td>';        
        }
        ?>
        </tr>
        <tr><th>TGID ACL</th>
        <?php
        if ($_dvmhost_conf['Talkgroup Id']['ACL'] == 0) {
            echo '<td class="table-col-disabled">Disabled</td>';
        } else {
            echo '<td class="table-col-success">Enabled</td>';        
        }
        ?>
        </tr>
        <?php
        if (modemFirmware()) {
            echo '<tr><th>F/W</th><td class="table-col-white">' . modemFirmware() . '</td></tr>';
        }
        ?>
    </table>

    <!-- Callsign Panel -->
    <table>
        <tr><th colspan="2">Callsign</th></tr>
        <tr><th>Callsign</th><td class="table-col-white"><?php echo $_dvmhost_conf['CW Id']['Callsign']; ?></td></tr>
        <tr><th>Interval</th><td class="table-col-white"><?php echo $_dvmhost_conf['CW Id']['Time']; ?></td></tr>
    </table>

    <?php if ($modeDMR) { ?>
        <!-- DMR Panel -->
        <table>
            <tr><th colspan="2">DMR</th></tr>
            <tr><th>ID</th><td class="table-col-white"><?php echo $_dvmhost_conf['DMR']['Id']; ?></td></tr>
            <tr><th>Roaming Beacons</th>
            <?php
            if ($_dvmhost_conf['DMR']['Beacons'] == 0) {
                echo '<td class="table-col-disabled">Disabled</td>';
            } else {
                echo '<td class="table-col-success">Enabled</td>';        
            }
            ?>
            </tr>
            <tr><th>Roaming Beacon Interval</th><td class="table-col-white"><?php echo $_dvmhost_conf['DMR']['BeaconInterval']; ?></td></tr>
            <tr><th>Roaming Beacon Duration</th><td class="table-col-white"><?php echo $_dvmhost_conf['DMR']['BeaconDuration']; ?></td></tr>
            <tr><th>Color Code</th><td class="table-col-white"><?php echo $_dvmhost_conf['DMR']['ColorCode']; ?></td></tr>
        </table>
    <?php } ?>

    <?php if ($modeP25) { ?>
        <!-- P25 Panel -->
        <table>
            <tr><th colspan="2">P25</th></tr>
            <tr><th>ID</th><td class="table-col-white"><?php echo $_dvmhost_conf['P25']['Id']; ?></td></tr>
            <tr><th>Control Data</th>
            <?php
            if ($_dvmhost_conf['P25']['ControlData'] == 0) {
                echo '<td class="table-col-disabled">Disabled</td>';
            } else {
                echo '<td class="table-col-success">Enabled</td>';        
            }
            ?>
            </tr>
            <tr><th>Control Data Interval</th><td class="table-col-white"><?php echo $_dvmhost_conf['P25']['ControlInterval']; ?></td></tr>
            <tr><th>Control Data Duration</th><td class="table-col-white"><?php echo $_dvmhost_conf['P25']['ControlDuration']; ?></td></tr>
            <tr><th>NAC</th><td class="table-col-white"><?php echo $_dvmhost_conf['P25']['NAC']; ?></td></tr>
            <tr><th>System</th><td class="table-col-white"><?php echo $_dvmhost_conf['P25']['SysId']; ?></td></tr>
            <tr><th>RFSS</th><td class="table-col-white"><?php echo $_dvmhost_conf['P25']['RfssId']; ?></td></tr>
            <tr><th>Site</th><td class="table-col-white"><?php echo $_dvmhost_conf['P25']['SiteId']; ?></td></tr>
            <tr><th>Channel</th><td class="table-col-white"><?php echo $_dvmhost_conf['P25']['ChannelId']; ?></td></tr>
            <tr><th>WACN</th><td class="table-col-white"><?php echo $_dvmhost_conf['P25']['NetId']; ?></td></tr>
        </table>
    <?php } ?>

    <?php if ($networkEnable) { ?>
        <!-- Network Panel -->
        <table>
            <tr><th colspan="2">Network</th></tr>
            <tr>
            <?php
            if ($_dvmhost_conf['Network']['Enable'] == 0) {
                echo '<td colspan="2" class="table-col-disabled">Disabled</td>';
            } else {
                echo '<td colspan="2" class="table-col-success">Enabled</td>';        
            }
            ?>
            </tr>
            <tr><th>Address</th><td class="table-col-white"><?php echo $_dvmhost_conf['Network']['Address']; ?></td></tr>
            <tr><th>DMR Slot 1</th>
            <?php
            if ($_dvmhost_conf['Network']['Slot1'] == 0) {
                echo '<td class="table-col-disabled">Disabled</td>';
            } else {
                echo '<td class="table-col-success">Enabled</td>';        
            }
            ?>
            </tr>
            <tr><th>DMR Slot 2</th>
            <?php
            if ($_dvmhost_conf['Network']['Slot2'] == 0) {
                echo '<td class="table-col-disabled">Disabled</td>';
            } else {
                echo '<td class="table-col-success">Enabled</td>';        
            }
            ?>
            </tr>
            <tr><th>Update Lookup ACL</th>
            <?php
            if ($_dvmhost_conf['Network']['UpdateLookups'] == 0) {
                echo '<td class="table-col-disabled">Disabled</td>';
            } else {
                echo '<td class="table-col-success">Enabled</td>';        
            }
            ?>
            </tr>
        </table>
    <?php } ?>
<?php } elseif ($mode == 'activity') { ?>
    <h3>System Activity</h3>
    <table>
        <tr>
            <th>Time (<?php echo date('T'); ?>)</th>
            <th>Mode</th>
            <th>Source</th>
            <th>Target</th>
            <th>Interface</th>
            <th>Duration</th>
            <th>Loss</th>
            <th>BER %</th>
        </tr>
        <?php echo activity(); ?>
    </table>
<?php } elseif ($mode == 'admin') { ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" lang="en">
    <head>
        <meta name="robots" content="index" />
        <meta name="robots" content="follow" />
        <meta name="language" content="English" />
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="cache-control" content="max-age=0" />
        <meta http-equiv="cache-control" content="no-cache, no-store, must-revalidate" />
        <meta http-equiv="expires" content="0" />
        <meta http-equiv="pragma" content="no-cache" />

        <title>Digital Voice Modem Dashboard - Administration</title>

        <link rel="stylesheet" type="text/css" href="style.css">
        <script type="text/javascript" src="jquery-3.2.1.min.js"></script>
        <script type="text/javascript">
            $.ajaxSetup({ cache: false });
        </script>
    </head>

    <body>
    <div class="container">
        <div class="header">
            <div style="font-size: 8px; text-align: left; padding-left: 8px; float: left;">Hostname: <?php echo shell_exec('cat /etc/hostname'); ?></div>
            <h1><a href="/"><?php echo $systemCallsign; ?> Repeater Dashboard</a></h1>
        </div>

        <div class="nav">
            <script type="text/javascript">
                function reloadRepeaterInfo() {
                    $("#repeaterInfo").load("/index.php?mode=repeaterinfo");
                    $(window).trigger('resize');
                }

                reloadRepeaterInfo();
                setInterval(function() { reloadRepeaterInfo() }, 1500);
            </script>
            <div id="repeaterInfo">
                <table>
                    <tr><th colspan="2">Loading ...</th></tr>
                </table>
            </div>
        </div>
        <div class="content">
            <h3>System Configuration</h3>
            <?php
                $filepath = '/tmp/DVM.ini.tmp';

                // do some file wrangling...
                exec("sudo cp " . $_dvmhost_conf_file . " " . $filepath);
                exec("sudo chown www-data:www-data " . $filepath);
                exec("sudo chmod 664 " . $filepath);

                // after the form submit
                if($_POST) {
                    $data = $_POST;
                    updateINI($data, $filepath);
                }

                // parse the ini file using default parse_ini_file() PHP function
                $parsed_ini = parse_ini_file($filepath, true);
            ?>
            <form action="index.php" method="POST">
                <?php foreach ($parsed_ini as $section => $values) { ?>
                <input type="hidden" value="admin" name="mode" />
                <input type="hidden" value="<?php echo $section; ?>" name="<?php echo $section; ?>" />
                <table>
                    <tr><th colspan="2"><?php echo $section; ?><div align="right"><input type="submit" value="Apply Section" /></div></th></tr>
                    <?php
                    // print all other values as input fields, so can edit. 
                    // note the name='' attribute it has both section and key
                    foreach($values as $key => $value) {
                        if (strpos($key, "#") === 0) {
                            ?><input type="hidden" name="<?php echo "{$section}[$key]"; ?>" value="<?php echo $value; ?>" /><?php
                        } else {
                    ?>
                        <tr>
                            <td align="right" width="30%"><?php echo $key; ?></td>
                            <td align="left"><input type="text" name="<?php echo "{$section}[$key]"; ?>" value="<?php echo $value; ?>" /></td>
                        </tr>
                    <?php 
                        }
                    } 
                    ?>
                </table>
                <?php } ?>
            </form>
        </div>
        <div class="footer"></div>
    </div>
    </body>
    </html>
<?php } elseif ($mode == 'log') { ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" lang="en">
    <head>
        <meta name="robots" content="index" />
        <meta name="robots" content="follow" />
        <meta name="language" content="English" />
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="cache-control" content="max-age=0" />
        <meta http-equiv="cache-control" content="no-cache, no-store, must-revalidate" />
        <meta http-equiv="expires" content="0" />
        <meta http-equiv="pragma" content="no-cache" />

        <title>Digital Voice Modem Dashboard - Log</title>

        <link rel="stylesheet" type="text/css" href="style.css">
        <script type="text/javascript" src="jquery-3.2.1.min.js"></script>
        <script type="text/javascript">
            $.ajaxSetup({ cache: false });
        </script>
    </head>

    <body>
    <div class="container">
        <div class="header">
            <div style="font-size: 8px; text-align: left; padding-left: 8px; float: left;">Hostname: <?php echo shell_exec('cat /etc/hostname'); ?></div>
            <h1><a href="/"><?php echo $systemCallsign; ?> Repeater Dashboard</a></h1>
        </div>

        <div class="nav">
            <script type="text/javascript">
                function reloadRepeaterInfo() {
                    $("#repeaterInfo").load("/index.php?mode=repeaterinfo");
                    $(window).trigger('resize');
                }

                reloadRepeaterInfo();
                setInterval(function() { reloadRepeaterInfo() }, 1500);
            </script>
            <div id="repeaterInfo">
                <table>
                    <tr><th colspan="2">Loading ...</th></tr>
                </table>
            </div>
        </div>
        <div class="content">
            <h3>System Log</h3>
            <table>
            <?php
                $logDVMNow = "/opt/DVM/log/DVM-" . gmdate("Y-m-d") . ".log";

                $logFile = file_get_contents($logDVMNow);
                $logLines = array_reverse(explode("\n", $logFile));
                if ($logLines) {
                    $output = '';
                    for ($i = 0; $i < count($logLines); $i++) {
                        $output .= '<tr><td align="left">' . $logLines[$i] . '</td></tr>';
                    }

                    echo $output;
                }
            ?>
            </table>
        </div>
        <div class="footer"></div>
    </div>
    </body>
    </html>
<?php } ?>
