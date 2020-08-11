<?
// -----------------------------------------------------
// Auslesen einiger Informationen aus einem BWT Aqa Perla 

// -----------------------------------------------------
// Ab hier individuelle Parameter

$BWT_Vars = 25370;                                                  // ID einer Kategorie im Objektbaum unter dem die Variablen gespeichert werden (muss angelegt sein)

define('LOGIN_URL', 'https://192.168.178.xxx/users/login');                      // Das ist die URL bei der wir uns authentifizieren müssen
define('DATA_URL', 'https://192.168.178.xxx/home/actualizedata');                // Und hier gibt's die Daten

$post = http_build_query(array('STLoginPWField' => 'XXXXXX'));                  // Zugangsdaten 
                                                                                // (XXXXXX ersetzen durch den Login-Code den man nach der Produktaktivierung per Email erhält)
// Bis hier individuelle Parameter
// -----------------------------------------------------
define('COOKIE_FILE', 'BWT.cookie');                                            // Ablageort für Cookie-Informationen

define('USER_AGENT', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (K HTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36');
                                                                                // Wir geben uns als "ordentlicher" Browser aus


// -----------------------------------------------------
// Ab hier nur noch Code
// -----------------------------------------------------
// Teil 1 Cookie holen 
echo "BWT: Cookie holen\n";

$curl = curl_init();                                                            // los geht's

curl_setopt($curl, CURLOPT_URL, LOGIN_URL);                                     // URL zum Loginformular
curl_setopt($curl, CURLOPT_POST, true);                                         // Ein POST request soll es werden
curl_setopt($curl, CURLOPT_POSTFIELDS, $post);                                  // Die Infos als URL-Codierten String schicken
 
curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);                              // Hilft bei einer eventuellen Sessionvalidation auf Serverseite

curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);                                  // keine Prüfung ob Hostname im Zertifikat
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);                              // keine Überprüfung des Peerzertifikats
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);                              // Keinen redirects folgen

curl_setopt($curl, CURLOPT_COOKIEFILE, COOKIE_FILE);                            // Hier wird der Cookie für später gespeichert
//curl_setopt($curl, CURLOPT_COOKIESESSION, true);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);                               // Die Antwort bitte nicht an STDOUT
  
curl_exec($curl);                                                               // ok, jetzt ausführen

$curl_errno = curl_errno($curl);

if ($curl_errno > 0) {
    $curl_error = curl_error($curl);
    echo "BWT: cURL Error ($curl_errno): $curl_error\n";
} else {

// keine Fehler - weiter geht's
// Teil 2 Daten holen
    echo "BWT: Daten auslesen\n";

    curl_setopt($curl, CURLOPT_URL, DATA_URL);                                      // Daten-URL abrufen 
    curl_setopt($curl, CURLOPT_POST, false);                                        // Diesesmal kein POST request
    curl_setopt($curl, CURLOPT_COOKIEFILE, COOKIE_FILE);                            // Cookie mitgeben
 
    curl_setopt($curl, CURLOPT_USERAGENT, USER_AGENT);                              // Hilft bei einer eventuellen Sessionvalidation auf Serverseite
    curl_setopt($curl, CURLOPT_REFERER, LOGIN_URL);                                 // ist eigentlich nur Kür

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);                                  // keine Prüfung ob Hostname im Zertifikat
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);                              // keine Überprüfung des Peerzertifikats
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);                              // Keinen redirects folgen

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);                               // Die Antwort bitte als Rückgabewert von curl_exec

    $response = curl_exec($curl);                                                   // GET ausführen

    curl_close($curl);                                                              // cURL Session beenden


    echo $response."\n";

    $json = json_decode($response, true);

    //var_dump($json);
    //print_r($json);

    foreach ($json as $name => $value) {
        echo ($name." -> ".$value."\n");
        UpdateIPSvar ($name, $value, "integer" );
    }

    echo "\nSymcon Variablen aktualisiert\n";
}


// -----------------------------------------------------
// Variablen anlegen und/oder aktualisierien
// -----------------------------------------------------
function UpdateIPSvar($name, $value, $type) {
Global $BWT_Vars;

	$parent = $BWT_Vars;
	$ident = str_replace(array("-", "/"), "_", $name);
		
    switch ($type){
    case "boolean":
       	$ips_type=0;
        break;
	case "integer":			
        $ips_type=1;
        break;
    case "float":
        $ips_type=2;
        break;
    case "string":
        $ips_type=3;
        break;
    default:
        echo "Unbekannter Datentyp: ".$type.PHP_EOL;
        $ips_type=3;
        break;
    }
    $var_id = @IPS_GetObjectIDByIdent($ident, $parent);
	
    if ($var_id === false){
        $var_id = IPS_CreateVariable($ips_type);
        IPS_SetName($var_id, $name);
        IPS_SetIdent($var_id, $ident);
        IPS_SetParent($var_id, $parent);
    }
	
    switch ($ips_type){
        case 0:
           if (GetValueBoolean($var_id) <> (bool)$value){
              SetValueBoolean($var_id, (bool)$value);
           }
           break;
        case 1:
            if (GetValueInteger($var_id) <> (int)$value){
               SetValueInteger($var_id, (int)$value);
            }
            break;
        case 2:
            if (GetValueFloat($var_id) <> round((float)$value,2)){
               SetValueFloat($var_id, round((float)$value,2));
            }
            break;
        case 3:
            if (GetValueString($var_id) <> $value){
               SetValueString($var_id, $value);
            }
            break;
    }
}

?>