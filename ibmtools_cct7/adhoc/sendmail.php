#!/xxx/apache/php/bin/php -q
<?php
// 
// template.php
//
// AUTHOR: Greg Parkin
//

//
// Class autoloader - /xxx/www/cct/classes:/xxx/www/cct/servers:/xxx/www/cct/includes
// See: include_paths= in file /xxx/apache/php/php.ini
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

$ora = new dbms();
$lib = new library();

$fp = fopen("email_letter.txt", "r") or exit("Unable to open for reading: email_letter.txt");

$letter = "<html><body>";

if ($fp)
{
	while (($buffer = fgets($fp, 4096)) !== false)
	{
		$letter .= $buffer;
	}

	fclose($fp);
}

$letter .= "</body></html>";

if ($ora->sql("select * from cct6_email_list where mnet_email is not null order by mnet_name") == false)
{
	printf("%s\n", $ora->dbErrMsg);
	exit();
}

while ($ora->fetch())
{
	printf("Sending email to: %s @ %s\n", $ora->mnet_name, $ora->mnet_email);
	Mailer(
		'Kristy Estabine', 'kestabin@us.ibm.com', // FROM
		$ora->mnet_name, $ora->mnet_email,        // TO
		'CCT 6.0 Training and Issues',            // SUBJECT
		$letter);                                 // LETTER
}

printf("\nDONE!\n");

function Mailer($from_name, $from_email, $to_name, $to_email, $subject, $email_body)
{
	$to = sprintf("%s <%s>", $to_name, $to_email);
	
	//
	// Header entries must be separated by \r\n
	//
	$headers= sprintf("From: %s <%s>\r\n", $from_name, $from_email);
	$headers .= sprintf("Reply-To: %s <%s>\r\n", $from_name, $from_email);
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
	
	return mail($to, $subject, $email_body, $headers);  // Returns true if mail seccessfully sent, otherwise false.
	return true;
}



exit();
?>

