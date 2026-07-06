<?

function postVars($myKey) {

	// Gibt die HTTP-Post-Variablen zurück

	global $HTTP_POST_VARS;

	if (isset($HTTP_POST_VARS[$myKey])) {
		return ($HTTP_POST_VARS[$myKey]);
	}
	else {
		return ("");
	}
}

function quoted_printable_encode($input) {

	// MIME-Encoding

    $line_max = 76;
	$hex = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');
	$lines = split("\n", $input);
	$eol = "\n";
	$escape = "=";
	$output = "";

	for ($j=0;$j<count($lines);$j++) {
		$line = $lines[$j];
		$linlen = strlen($line);
		$newline = "";
		for($i = 0; $i < $linlen; $i++) {
			$c = substr($line, $i, 1);
			$dec = ord($c);
			if ( ($dec == 32) && ($i == ($linlen - 1)) ) { 
				$c = "=20"; 
			} elseif ( ($dec == 61) || ($dec==46) || ($dec < 32 ) || ($dec > 126) ) { 
				$h2 = floor($dec/16); $h1 = floor($dec%16); 
				$c = $escape.$hex["$h2"].$hex["$h1"]; 
			}
			if ( (strlen($newline) + strlen($c)) >= $line_max ) { 
				$output .= $newline.$escape.$eol; 
				$newline = "";
			}
			$newline .= $c;
		} 
		$output .= $newline;
		if ($j<count($lines)-1) $output .= $eol;
	}
	return trim($output);
}

function createHTMLMail() {

	// erzeugt eine HTML-Mail

	global $HTTP_POST_VARS;
	reset($HTTP_POST_VARS);
	
	$i=1;
	
	$mymail="";
	
	// Header
	
	$mymail.= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">\n";
	$mymail.= "<html>\n";
	$mymail.= "<head>\n";
	
	$mymail.="<META http-equiv=Content-Type content=text/html; charset=iso-8859-1>\n";
	
	$mymail.= "</head>\n";
	$mymail.= "<body bgcolor=\"#ffffff\" text=\"#333333\" link=\"#333333\">\n";

	$mymail.= "<style>\n";
	$mymail.= "  td {font-family : Tahoma, Verdana, Geneva, Arial, Helvetica, sans-serif; font-size : 11px; color : #333333; }\n";
	$mymail.= "</style>\n";
	$mymail.="<table width=100% border=0 cellpadding=4>\n";

	$mymail.="<tr>";
	$mymail.="<td colspan=2><strong>Se le ha enviado el siguiente mensaje:</strong></td>\n";
	$mymail.="</tr>\n";
	
	while (list($key, $val) = each ($HTTP_POST_VARS))
	{
		switch ($key)
		{
			case "x":
			case "y":
			case "fgwemail":
			case "fgwsubject":
			case "fgwreturnurl":
				break;
	   		default:
				$mymail.="<tr>";
				$mymail.="<td><strong>".$key.":</strong></td>\n";
				$mymail.="<td width=100%>".$val."</td>\n";
				$mymail.="</tr>\n";
		}
	}

	$mymail.="</table>\n";

	return $mymail;
}

function createTextMail() {

	global $HTTP_POST_VARS;
	reset($HTTP_POST_VARS);

	$mymail="";
	while (list($key, $val) = each ($HTTP_POST_VARS))
	{
		switch ($key)
		{
			case "x":
			case "y":
			case "fgwemail":
			case "fgwsubject":
			case "fgwreturnurl":
				break;
	   		default:
				$mymail.=$key." ";
				$mymail.=$val."\n";
		}
	}
	
	$mymail.="\n";

	return $mymail;
}
	
function createMimeMail() {
	$mymime="This is a multi-part message in MIME format.\n\n";
	$mymime.="----WEB2DATEGATEWAY\n";
	$mymime.="Content-Type: text/plain;\n\tcharset=\"iso-8859-1\"\n";
	$mymime.="Content-Transfer-Encoding: quoted-printable\n\n";
	$mymime.=quoted_printable_encode(createTextMail())."\n";
	$mymime.="----WEB2DATEGATEWAY\n";
	$mymime.="Content-Type: text/html;\n\tcharset=\"iso-8859-1\"\n";
	$mymime.="Content-Transfer-Encoding: quoted-printable\n\n";
	$mymime.=quoted_printable_encode(createHTMLMail())."\n";
	$mymime.="----WEB2DATEGATEWAY--\n";
	return $mymime;
}

function deSlash(&$element) {

	// Für Stripslash

	$element=stripslashes($element);
}

// Hauptprogramm

// Die magischen Quotes eliminieren...

reset($HTTP_POST_VARS);

if (get_magic_quotes_gpc()) {
	array_walk($HTTP_POST_VARS, "deSlash");
}

$fgwemail=postVars("fgwemail");
$fgwsubject=postVars("fgwsubject");
$fgwreturnurl=postVars("fgwreturnurl");

if (!$fgwemail) {
	die ("ERROR: NO RETURN-EMAIL-ADRESS");
}
if (!$fgwreturnurl) {
	die ("ERROR: NO RETURN-URL");
}

mail ($fgwemail, $fgwsubject, createMimeMail(), "MIME-Version: 1.0\nContent-Type: multipart/alternative;\n\tboundary=\"--WEB2DATEGATEWAY\"\nX-Mailer: web to date Gateway Version 1.0");
header("Location: ".$fgwreturnurl);

?>
