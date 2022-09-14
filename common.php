<?php
$_dvmhost_conf_file = "/opt/DVM/bin/DVM.ini";
$_dvmhost_conf = parse_ini_file($_dvmhost_conf_file, true);

$systemCallsign = $_dvmhost_conf['General']['Callsign'];

$modeDMR = $_dvmhost_conf['DMR']['Enable'];
$modeP25 = $_dvmhost_conf['P25']['Enable'];
$networkEnable = $_dvmhost_conf['Network']['Enable'];

$dmrRFHang = 0;
if ($_dvmhost_conf['DMR']['CallHang']) { 
    $dmrHang = $_dvmhost_conf['DMR']['CallHang']; 
}
$dmrHang += $_dvmhost_conf['General']['ModeHang']; 

$p25Hang = 0;
if ($_dvmhost_conf['P25']['CallHang']) { 
    $p25Hang = $_dvmhost_conf['P25']['CallHang']; 
} 
$p25Hang += $_dvmhost_conf['General']['ModeHang']; 

function set_var(&$result, $var, $type, $multibyte = false) {
	settype($var, $type);
	$result = $var;

	if ($type == 'string') {
		$result = trim(htmlspecialchars(str_replace(array("\r\n", "\r"), array("\n", "\n"), $result), ENT_COMPAT, 'UTF-8'));

		if (!empty($result)) {
			// Make sure multibyte characters are wellformed
			if ($multibyte) {
				if (!preg_match('/^./u', $result)) {
					$result = '';
				}
			}
			else {
				// no multibyte, allow only ASCII (0-127)
				$result = preg_replace('/[\x80-\xFF]/', '?', $result);
			}
		}

		$result = stripslashes($result);
	}
}

function request_var($var_name, $default, $multibyte = false, $cookie = false)
{
	if (!$cookie && isset($_COOKIE[$var_name])) {
		if (!isset($_GET[$var_name]) && !isset($_POST[$var_name])) {
			return (is_array($default)) ? array() : $default;
		}
		$_REQUEST[$var_name] = isset($_POST[$var_name]) ? $_POST[$var_name] : $_GET[$var_name];
	}

    if (!isset($_REQUEST[$var_name]) || (is_array($_REQUEST[$var_name]) && !is_array($default)) || 
        (is_array($default) && !is_array($_REQUEST[$var_name]))) {
		return (is_array($default)) ? array() : $default;
	}

	$var = $_REQUEST[$var_name];
	if (!is_array($default)) {
		$type = gettype($default);
	}
	else {
		list($key_type, $type) = each($default);
		$type = gettype($type);
		$key_type = gettype($key_type);
		if ($type == 'array') {
			reset($default);
			list($sub_key_type, $sub_type) = each(current($default));
			$sub_type = gettype($sub_type);
			$sub_type = ($sub_type == 'array') ? 'NULL' : $sub_type;
			$sub_key_type = gettype($sub_key_type);
		}
	}

	if (is_array($var)) {
		$_var = $var;
		$var = array();

		foreach ($_var as $k => $v) {
			set_var($k, $k, $key_type);
			if ($type == 'array' && is_array($v)) {
				foreach ($v as $_k => $_v) {
					if (is_array($_v)) {
						$_v = null;
					}
					set_var($_k, $_k, $sub_key_type);
					set_var($var[$k][$_k], $_v, $sub_type, $multibyte);
				}
			}
			else {
				if ($type == 'array' || is_array($v)) {
					$v = null;
				}
				set_var($var[$k], $v, $type, $multibyte);
			}
		}
	}
	else {
		set_var($var, $var, $type, $multibyte);
	}

	return $var;
}

function stopDVM() {
    exec("sudo /opt/stop-watchdog.sh");
    exec("sudo /opt/stop-dvm.sh");
}

function startDVM() {
    exec("sudo /opt/restart-dvm.sh");
}

function updateINI($data, $filepath) {
    global $_dvmhost_conf_file;
    $content = "";

    // parse the ini file to get the sections
    // parse the ini file using default parse_ini_file() PHP function
    $parsed_ini = parse_ini_file($filepath, true);
    
    foreach($data as $section => $values) {
        // skip a "mode" section
        if ($section == 'mode') {
            continue;
        }

        // unbreak special cases
        $section = str_replace("_", " ", $section);
        $content .= "[" . $section . "]\n";
        
        // append the values
        foreach($values as $key => $value) {
            if ($value == '') { 
                $content .= $key . "=none\n";
            } else {
                $content .= $key . "=" . $value . "\n";
            }
        }
        $content .= "\n";
    }

    // write it into file
    if (!$handle = fopen($filepath, 'w')) {
        return false;
    }

    $success = fwrite($handle, $content);
    fclose($handle);
    
    // updates complete - copy the working file back to the proper location
    exec("sudo cp " . $filepath . " " . $_dvmhost_conf_file);
    exec("sudo chmod 644 " . $_dvmhost_conf_file);
    exec("sudo chown root:root " . $_dvmhost_conf_file);
    
    exec("sudo /opt/restart-dvm.sh");

    return $success;
}

function isProcessRunning($processname) {
	static $output;
	$apcu_field = "isProcessRunning_" . $processname;

	if (apcu_exists($apcu_field)) {
        $output = apcu_fetch($apcu_field); 
    }
	else {
		$pids = shell_exec("ps -ef | grep '" . $processname . "' | grep -v grep");
		if(empty($pids)) {
			// process not running!
			$output = false;
		} else {
			// process running!
			$output = true;
		}
    
        // Cache the output
	    apcu_add($apcu_field, $output, 10);
    }
    
	// Add the value to the Cache
	return $output;
}

function getMHz($freq) {
	return substr($freq,0,3) . "." . substr($freq,3,6) . " MHz";
}

function modeStatus($mode) {
	global $modeDMR;
	global $modeP25;

    static $output;
	$apcu_field = "modeStatus_" . $mode;

    if (apcu_exists($apcu_field)) { 
        $output = apcu_fetch($apcu_field); 
    }
	else {
        if ($mode == "DMR" && $modeDMR) {
            if (isProcessRunning("dvmhost")) {
                $pre = '<td class="table-col-success" style="color: #030; width: 50%;">';
            } else {
                $pre = '<td class="table-col-danger" style="color: #500; width:50%;">';
            }
        }
        elseif ($mode == "P25" && $modeP25) {
            if (isProcessRunning("dvmhost")) {
                $pre = '<td class="table-col-success" style="color: #030; width: 50%;">';
            } else {
                $pre = '<td class="table-col-danger" style="color: #500; width:50%;">';
            }
        }
        else {
            $pre = '<td style="background-color: #666; color: #bbb; width: 50%;">';
        }

        $post = '</td>';
    	$output = $pre . $mode . $post;
	    apcu_add($apcu_field, $output, 10);
    }
	return $output;
}

function modemStatus() {
    global $dmrHang, $p25Hang;
	static $output;

    if (apcu_exists('modemStatus')) {
		$output = apcu_fetch('modemStatus');
    }
    
	if ( $output !== null ) {
        return $output; 
    }
	else {
        // 00000000001111111111222222222233333333334444444444555555555566666666667777777777888888888899999999990000000000111111111122
        // 01234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901
        // M: 1969-01-01 00:00:00.000 P25, received network transmission from MW0MWZ to TG 10200
        // M: 1969-01-01 00:00:00.000 P25, network end of transmission, 3.1 seconds, 0% packet loss
        // M: 1969-01-01 00:00:00.000 DMR Slot 2, received network late entry from MW0MWZ to TG 9
        // M: 1969-01-01 00:00:00.000 DMR Slot 2, received network end of voice transmission, 25.3 seconds, 0% packet loss, BER: 0.0%
        // M: 2017-12-06 19:22:06.038 DMR Slot 2, RF voice transmission lost, 1.1 seconds, BER: 6.5%
        $logDVMNow = "/opt/DVM/log/DVM-" . gmdate("Y-m-d") . ".log";

        // Get the last log line that contains the mode and source.
        $logLine = shell_exec("tail -n 20 $logDVMNow | grep -E '(DMR|P25)' | grep -E '(RF|network)' | tail -n 1");
        $timeNow = date('H:i:s');
        $txrx = "OFFLINE";
        $source = "RF";
        $lastMode = '<td class="table-col-danger">OFFLINE</td>';
        if (isProcessRunning("dvmhost")) {
            $lastMode = '<td class="table-col-white">STARTUP</td>'; 
            $txrx = "ONLINE";
        }

        if ($txrx != "OFFLINE") {
            if ($logLine) {
                if (strpos($logLine, "end of") || strpos($logLine, "ended") || strpos($logLine, "transmission lost")) { 
                    $txrx = "RX"; 
                } 
                else { 
                    $txrx = "TX"; 
                }

                if (strpos($logLine, "network")) { 
                    $source = "NET"; 
                }
                if (strpos($logLine, "RF")) { 
                    $source = "RF"; 
                }
                
                // Explode the string
                $logLineParts = explode(' ', $logLine);
                $lastMode = str_replace(",", "", $logLineParts['3']);
                $logTime = substr($logLineParts['2'], 0, 8);
            }
            else {
                $txrx = "RX"; // default to RX
                $source = "";
            }

            // If we are in RX, check the timers
            $output = '<td class="table-col-success">Idle</td>'; 
            if ($txrx == "RX") {
                if ($source == "NET") {	// Network Sources
                    if (($lastMode == "DMR") && ($timeNow < $logTime + $dmrHang)) { 
                        $output = '<td class="table-col-info">Idle DMR</td>';  
                    }
                    if (($lastMode == "P25") && ($timeNow < $logTime + $p25Hang)) { 
                        $output = '<td class="table-col-info">Idle P25</td>';  
                    }
                }
                elseif ($source == "RF") {	// RF Sources
                    if (($lastMode == "DMR") && ($timeNow < $logTime + $dmrHang)) {
                        $output = '<td class="table-col-info">Idle DMR</td>';  
                    }
                    if (($lastMode == "P25") && ($timeNow < $logTime + $p25Hang)) { 
                        $output = '<td class="table-col-info">Idle P25</td>';  
                    }
                }
            }
            else if ($txrx == "TX") { 
                $output = '<td class="table-col-warn">TX ' . $lastMode . '</td>'; 
            }
        }
        else {
            $output = $lastMode;
        }

        apcu_add('modemStatus', $output, 1);
        return $output;
	}
}

function modemFirmware() {
    global $systemOffline;
	static $output;
    if (apcu_exists('modemFirmware')) {
		$output = apcu_fetch('modemFirmware');
    }

    if ($systemOffline) {
        apcu_delete('modemFirmware');
        $output = "";
    }

	if ( $output !== null ) { 
        return $output; 
    }
	else {
        // 00000000001111111111222222222233333333334444444444555555555566666666667777777777888888888899999999990000000000111111111122
        // 01234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901
        // I: 1969-01-01 00:00:00.000 DVM protocol version: 1, description: DVM 20170206 TCXO (DMR/P25/RSSI/CW Id)
        $logDVMNow = "/opt/DVM/log/DVM-" . gmdate("Y-m-d") . ".log";
        $logDVMPrevious = "/opt/DVM/log/DVM-" . gmdate("Y-m-d", time() - 86340) . ".log";
        $logSearchString = "DVM protocol version";
        $logLine = '';
        $modemFirmware = '';
        $logLine = shell_exec("tac $logDVMNow | grep \"" . $logSearchString . "\" -m 1 2>/dev/null");
        if (!$logLine) { 
            $logLine = shell_exec("tac $logDVMNow | grep \"" . $logSearchString . "\" -m 1 2>/dev/null"); 
        }

        if ($logLine) {
            if (strpos($logLine, 'description: DVM ')) {
                $modemFirmware = substr($logLine, 103, 20);
            }
        }

        $output = $modemFirmware;
        apcu_add('modemFirmware', $output, 300);
        return $output;
	}
}

function activity($numLines = 64) {
	static $output;

    // Check the cache
	if (apcu_exists('activity')) { 
        $output = apcu_fetch('activity'); 
    }
    
    // Function has already run
	if ($output !== null) { 
        return $output; 
    }
	else {
        // A: 1969-01-01 00:00:00.000 P25 RF received RF voice transmission from 1234 to TG 1
        // A: 1969-01-01 00:00:00.000 P25 RF received RF end of transmission, 5.0 seconds, BER: 0.0%
        // A: 1969-01-01 00:00:00.000 P25 Net received network transmission from 1234 to TG 1
        // A: 1969-01-01 00:00:00.000 P25 Net network end of transmission, 3.1 seconds, 0% packet loss
        // A: 1969-01-01 00:00:00.000 P25 RF transmission lost, 1.0 seconds, BER: 8.0%
        // A: 1969-01-01 00:00:00.000 DMR RF Slot 2, received RF end of voice transmission, 1.8 seconds, BER: 3.9%
        // A: 1969-01-01 00:00:00.000 DMR RF Slot 2, RF voice transmission lost, 1.1 seconds, BER: 6.5%
        // A: 1969-01-01 00:00:00.000 DMR Net Slot 1, received network late entry from 1234 to TG 1
        // A: 1969-01-01 00:00:00.000 DMR Net Slot 1, received network end of voice transmission, 25.3 seconds, 0% packet loss, BER: 0.0%
        // A: 1969-01-01 00:00:00.000 DMR Net Slot 2, received network data header from 1234 to TG 1, 5 blocks
        // A: 1969-01-01 00:00:00.000 DMR Net Slot 2, ended network data transmission
        // A: 1969-01-01 00:00:00.000 DMR Net Slot 2, network watchdog has expired, 5.4 seconds, 38% packet loss, BER: 0.0%
        $logDVMNow = "/opt/DVM/log/DVM-" . gmdate("Y-m-d") . ".activity.log";
        $logDVMPrevious = "/opt/DVM/log/DVM-" . gmdate("Y-m-d", time() - 86340) . ".activity.log";

        $tableEntries = $logLines = $logLines1 = $logLines2 = array();

        $logFile = file_get_contents($logDVMNow);
        $logLines1 = array_reverse(explode("\n", $logFile));
        $logFile = file_get_contents($logDVMPrevious);
        $logLines2 = array_reverse(explode("\n", $logFile));

        $logLines = array_filter(array_merge($logLines1, $logLines2));
        $count = 0;
        if ($logLines) {
            for ($i = 0; $i < count($logLines); $i++) {
                if ($count > $numLines) {
                    break;
                }

                if (preg_match('/(received network|received RF)/', $logLines[$i]) === 0) {
                    continue;
                }

                if (preg_match('/(end of)/', $logLines[$i]) !== 0) {
                    continue;
                }

                $count++;

                $logLineRaw = $logLines[$i];
                $logLine = str_replace(array("A: ", ","), "", $logLineRaw);
                $rawData = explode(" ", $logLine);

                $dateUTC = $rawData['0'] . " " . $rawData['1'];

                $utc_tz =  new DateTimeZone('UTC');
                $local_tz = new DateTimeZone(date_default_timezone_get ());
                $dt = new DateTime($dateUTC, $utc_tz);
                $dt->setTimeZone($local_tz);
                $dateLocal = $dt->format('H:i:s M jS Y');

                $rawMode = $rawData['2'];
                $mode = $rawData['2'];
                $src = $rawData['3'];

                if ($mode == "DMR") { 
                    $mode = $rawData['2'] . " TS" . $rawData['5']; 
                }
                
                if (strpos($logLine, "data header")) { 
                    $src = "SMS"; 
                } 

                $actData = explode("from ", $logLine);
                if (count($actData) <= 1) {
                    continue;
                }

                $actData = explode("to ", $actData['1']);
                $from = str_replace(" ", "", $actData['0']);
                $to = str_replace("  ", " ", $actData['1']);
                if (strpos($to, " ")) {
                    $toData = explode(" ", $to);
                    if (isset($toData['0']) && isset($toData['1'])) { 
                        $to = $toData['0']." ".$toData['1']; 
                    }

                    if (isset($toData['2']) && isset($toData['3'])) { 
                        $smsDur = $toData['2']." ".$toData['3']; 
                    }
                }
                
                if ($from == '') {
                    continue;
                }
                if ($to == '') {
                    continue;
                }

                $name = "&nbsp;(" . $from . ")";

                $durAndLoss = '<td colspan="3" class="table-col-disabled">No data or transmitting</td>';
                $endOfCount = 0;
                for ($j = $i - 1; $j < count($logLines); $j++) {
                    if ($endOfCount >= 4) {
                        break;
                    }
                    if (preg_match('/(end of|ended network data transmission|ended RF data transmission|watchdog has expired|transmission lost)/', $logLines[$j]) === 0) {
                        $endOfCount++;
                        continue;
                    }

                    $rawStats = explode(", ", $logLines[$j]);
                
                    if ($rawMode !== 'DMR') {
                        if (isset($rawStats['1'])) { 
                            $dur = '<td>' . str_replace(" seconds", "s", rtrim($rawStats['1'])) . '</td>'; 
                        }
                        else {
                            $dur = '<td>0s</td>'; 
                        }
                        
                        if (isset($rawStats['2'])) { 
                            $loss = str_replace(" packet loss", "", rtrim($rawStats['2'])); 
                        } 
                        else { 
                            $loss = '0%'; 
                        }

                        if (isset($rawStats['2'])) { 
                            $ber = str_replace("BER: ", "", rtrim($rawStats['2'])); 

                            // do checking for packet loss vs BER
                            if (strpos($ber, " packet loss") === false) {
                                $loss = 'N/A';
                            } else {
                                $ber = 'N/A';
                            }
                        } 
                        else { 
                            $ber = '0%'; 
                        }
                    } else if ($rawMode === 'DMR') {
                        if (isset($rawStats['2'])) { 
                            $dur = '<td>' . str_replace(" seconds", "s", rtrim($rawStats['2'])) . '</td>'; 
                        }
                        else {
                            $dur = '<td>0s</td>'; 
                        }
                        
                        if (isset($rawStats['3'])) { 
                            $loss = str_replace(" packet loss", "", rtrim($rawStats['3'])); 
                        } 
                        else { 
                            $loss = '0%'; 
                        }

                        if (isset($rawStats['3'])) { 
                            $ber = str_replace("BER: ", "", rtrim($rawStats['3'])); 

                            // do checking for packet loss vs BER
                            if (strpos($ber, " packet loss") === false) {
                                $loss = 'N/A';
                            } else {
                                $ber = 'N/A';
                            }
                        } 
                        else { 
                            $ber = '0%'; 
                        }
                    }

                    // Colour the loss field
                    if ($loss === 'N/A') {
                        $loss = '<td class="table-col-disabled">' . $loss . '</td>'; 
                    } else {
                        if ((floatval($loss) >= "0.0%") && (floatval($loss) <= "1.9%")) { 
                            $loss = '<td class="table-col-success">' . $loss . '</td>'; 
                        }
                        elseif ((floatval($loss) >= "2.0%") && (floatval($loss) <= "2.9%")) { 
                            $loss = '<td class="table-col-warn">' . $loss . '</td>'; 
                        }
                        elseif (floatval($loss) >= "3.0%") { 
                            $loss = '<td class="table-col-danger">' . $loss . '</td>'; 
                        }
                        else {
                            $loss = '<td>' . $loss . '</td>';
                        }
                    }

                    // Colour the BER field
                    if ($ber === 'N/A') {
                        $ber = '<td class="table-col-disabled">' . $ber . '</td>'; 
                    } else {
                        if ((floatval($ber) >= "0.0%") && (floatval($ber) <= "1.9%")) { 
                            $ber = '<td class="table-col-success">' . $ber . '</td>'; 
                        }
                        elseif ((floatval($ber) >= "2.0%") && (floatval($ber) <= "2.9%")) { 
                            $ber = '<td class="table-col-warn">' . $ber . '</td>'; 
                        }
                        elseif (floatval($ber) >= "3.0%") { 
                            $ber = '<td class="table-col-danger">' . $ber . '</td>'; 
                        }
                        else {
                            $ber = '<td>' . $ber . '</td>';
                        }
                    }

                    // Swap SMS Duration information
                    if ($src == "SMS") { 
                        $dur = '<td>' . $smsDur . '</td>'; 
                    }

                    $durAndLoss = $dur . $loss . $ber; 
                    break;
                }

                $entry = '<tr>';
                $entry .= '<td style="text-align: left;">' . $dateLocal . '</td>';
                $entry .= '<td>' . $mode . '</td>';
                $entry .= '<td style="text-align: left;">' . $from . '</td>';
                $entry .= '<td>' . $to . '</td>';

                if ($src == 'Net') {
                    $entry .= '<td class="table-col-info">Network</td>';
                } else if ($src == 'RF') {
                    $entry .= '<td class="table-col-success">RF</td>';
                } else {
                    $entry .= '<td class="table-col-disabled">Unknown</td>';
                }

                $entry .= $durAndLoss;
                $entry .= '</tr>';
                array_push($tableEntries, $entry);
            }
        }

        if (count($logLines) <= 0) {
            $output = '<tr><td colspan="8">No data available</td></tr>';
        } else {
            $tableEntries = array_unique($tableEntries);
            foreach ($tableEntries as $entry) {
                $output .= $entry;
            }
        }

        apcu_add('activity', $output, 5); 
        return $output; 
	}
}
?>
