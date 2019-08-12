<?php
session_start();

include_once($dir . '/includes/config.php');
include_once(RASPI_CONFIG.'/raspap.php');
// include_once($dir . '/includes/locale.php');
include_once($dir . '/includes/functions.php');
include_once($dir . '/includes/dashboard.php');
include_once($dir . '/includes/admin.php');

$output = $return = 0;
// $page = $_GET['page'];

// if (empty($_SESSION['csrf_token'])) {
//     if (function_exists('mcrypt_create_iv')) {
//         $_SESSION['csrf_token'] = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
//     } else {
//         $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
//     }
// }
// $csrf_token = $_SESSION['csrf_token'];

/**
*
*
*/
    $status = new StatusMessages();
    $networks = array();

    // Find currently configured networks
    exec(' sudo cat ' . RASPI_WPA_SUPPLICANT_CONFIG, $known_return);

    $network = null;
    $ssid = null;

    foreach ($known_return as $line) {
        if (preg_match('/network\s*=/', $line)) {
            $network = array('visible' => false, 'configured' => true, 'connected' => false);
        } elseif ($network !== null) {

            // get pair key/value of "wpa_supplicant.conf"
            preg_match('/^\s([^=]*)=(.*)$/', $line, $lineArr);
            $conf_key = null;
            $conf_value = null;

            if (count($lineArr) > 0) {
                $conf_key = $lineArr[1];
                $conf_value = $lineArr[2];
            }


            if (preg_match('/^\s*}\s*$/', $line)) {
                $networks[$ssid] = $network;
                $network = null;
                $ssid = null;
            } elseif ($conf_key) {
                switch (strtolower($conf_key)) {
                    case 'ssid':
                        $ssid = trim($conf_value, '"');
                        break;
                    case 'psk':
                        if (array_key_exists('passphrase', $network)) {
                            break;
                        }
                    case '#psk':
                        $network['protocol'] = 'WPA';
                    case 'wep_key0': // Untested
                        $network['passphrase'] = trim($conf_value, '"');
                        break;
                    case 'key_mgmt':
                        if (! array_key_exists('passphrase', $network) && $conf_value === 'NONE') {
                            $network['protocol'] = 'Open';
                        }
                        break;
                    case 'priority':
                        $network['priority'] = trim($conf_value, '"');
                        break;
                }
            }
        }
    }

    if (isset($_POST['connect'])) {
        $result = 0;
        exec('sudo wpa_cli -i ' . RASPI_WPA_CTRL_INTERFACE . ' select_network '.strval($_POST['connect']));
    // } elseif (isset($_POST['client_settings']) && CSRFValidate()) {
    } elseif (isset($_POST['client_settings'])) {
        $tmp_networks = $networks;
        if ($wpa_file = fopen('/tmp/wifidata', 'w')) {
            fwrite($wpa_file, 'ctrl_interface=DIR=' . RASPI_WPA_CTRL_INTERFACE . ' GROUP=netdev' . PHP_EOL);
            fwrite($wpa_file, 'update_config=1' . PHP_EOL);

            foreach (array_keys($_POST) as $post) {
                if (preg_match('/delete(\d+)/', $post, $post_match)) {
                    unset($tmp_networks[$_POST['ssid' . $post_match[1]]]);
                } elseif (preg_match('/update(\d+)/', $post, $post_match)) {
                    // NB, at the moment, the value of protocol from the form may
                    // contain HTML line breaks
                    $tmp_networks[$_POST['ssid' . $post_match[1]]] = array(
                    'protocol' => ( $_POST['protocol' . $post_match[1]] === 'Open' ? 'Open' : 'WPA' ),
                    'passphrase' => $_POST['passphrase' . $post_match[1]],
                    'configured' => true
                    );
                    if (array_key_exists('priority' . $post_match[1], $_POST)) {
                        $tmp_networks[$_POST['ssid' . $post_match[1]]]['priority'] = $_POST['priority' . $post_match[1]];
                    }
                }
            }

            $ok = true;
            foreach ($tmp_networks as $ssid => $network) {
                if ($network['protocol'] === 'Open') {
                    fwrite($wpa_file, "network={".PHP_EOL);
                    fwrite($wpa_file, "\tssid=\"".$ssid."\"".PHP_EOL);
                    fwrite($wpa_file, "\tkey_mgmt=NONE".PHP_EOL);
                    if (array_key_exists('priority', $network)) {
                        fwrite($wpa_file, "\tpriority=".$network['priority'].PHP_EOL);
                    }
                    fwrite($wpa_file, "}".PHP_EOL);
                } else {
                    if (strlen($network['passphrase']) >=8 && strlen($network['passphrase']) <= 63) {
                        unset($wpa_passphrase);
                        unset($line);
                        exec('wpa_passphrase '.escapeshellarg($ssid). ' ' . escapeshellarg($network['passphrase']), $wpa_passphrase);
                        foreach ($wpa_passphrase as $line) {
                            if (preg_match('/^\s*}\s*$/', $line)) {
                                if (array_key_exists('priority', $network)) {
                                    fwrite($wpa_file, "\tpriority=".$network['priority'].PHP_EOL);
                                }
                                fwrite($wpa_file, $line.PHP_EOL);
                            } else {
                                fwrite($wpa_file, $line.PHP_EOL);
                            }
                        }
                    } else {
                        echo $network['passphrase'];
                        echo strlen($network['passphrase']);
                        $status->addMessage('WPA passphrase must be between 8 and 63 characters ' , 'danger');
                        $ok = false;
                    }
                }
            }

            if ($ok) {
                system('sudo cp /tmp/wifidata ' . RASPI_WPA_SUPPLICANT_CONFIG, $returnval);
                if ($returnval == 0) {
                    exec('sudo wpa_cli -i ' . RASPI_WIFI_CLIENT_INTERFACE . ' reconfigure', $reconfigure_out, $reconfigure_return);
                    if ($reconfigure_return == 0) {
                        $status->addMessage('Wifi settings updated successfully', 'success');
                        $networks = $tmp_networks;
                    } else {
                        $status->addMessage('Wifi settings updated but cannot restart (cannot execute "wpa_cli reconfigure")', 'danger');
                    }
                } else {
                    $status->addMessage('Wifi settings failed to be updated', 'danger');
                }
            }
        } else {
            $status->addMessage('Failed to update wifi settings', 'danger');
        }
    }

    exec('sudo wpa_cli -i ' . RASPI_WIFI_CLIENT_INTERFACE . ' scan');
    sleep(3);
    exec('sudo wpa_cli -i ' . RASPI_WIFI_CLIENT_INTERFACE . ' scan_results', $scan_return);

    array_shift($scan_return);

    // display output
    foreach ($scan_return as $network) {
        $arrNetwork = preg_split("/[\t]+/", $network);  // split result into array

        // Check if SSID is JSON compatible
        try {
            json_encode($arrNetwork[4]);
            // echo 'encode success';
        } catch (Exception $e) {
            // echo '>>>>encode failed<<<<';
            $arrNetwork[4] = null;
        }

        // If network is saved
        if ($arrNetwork[4] != null) {
            if (array_key_exists(4, $arrNetwork) && array_key_exists($arrNetwork[4], $networks)) {
                $networks[$arrNetwork[4]]['visible'] = true;
                $networks[$arrNetwork[4]]['channel'] = ConvertToChannel($arrNetwork[1]);
                // TODO What if the security has changed?
            } else {
                $networks[$arrNetwork[4]] = array(
                'configured' => false,
                'protocol' => explode('<br />', ConvertToSecurity($arrNetwork[3])),
                'channel' => ConvertToChannel($arrNetwork[1]),
                'passphrase' => '',
                'visible' => true,
                'connected' => false
                );
            }
        }

        // Save RSSI
        if (array_key_exists(4, $arrNetwork)) {
            $networks[$arrNetwork[4]]['RSSI'] = $arrNetwork[2];
        }

    }

    exec('iwconfig ' . RASPI_WIFI_CLIENT_INTERFACE, $iwconfig_return);
    foreach ($iwconfig_return as $line) {
        if (preg_match('/ESSID:\"([^"]+)\"/i', $line, $iwconfig_ssid)) {
            $networks[$iwconfig_ssid[1]]['connected'] = true;
        }
    }
