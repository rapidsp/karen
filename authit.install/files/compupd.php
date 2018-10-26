<?php

$H = 'hname';
$PSSWD = 'pwroot';
$AUTHT = 'authtype';
$CACERT = 'cacert';
$CACRL = 'cacrl';
$DRVPTH = 'drvpth';
$ID = 'id';
$DESCR = 'descr';
$FORCELOGON = '';
$INSTDRV = '';
$RADSERVER = '';
$FULLPC = '';

$H = $_POST["hname"];
$PSSWD = $_POST["pwroot"];
$AUTHT = $_POST["authtype"];
$CACERT = $_POST["cacert"];
$CACRL = $_POST["cacrl"];
$DRVPTH = $_POST['drvpth'];
$ID = $_POST['id'];
$DESCR = $_POST['descr'];
$RADSERVER = $_POST['radserver'];
if ($_POST['forcelogon']) $FORCELOGON = "true"; else $FORCELOGON = "false";
if ($_POST['instdrv']) $INSTDRV = "true"; else $INSTDRV = "false";
if ($_POST['fullpc'] == "all")  $FULLPC = "true"; else $FULLPC = "false";
if ($_POST['fullpc'] == "all")  $FULLPC = "true"; else $FULLPC = "false";

$content = file ('/etc/authit.conf');
foreach ($content as $line) { // читаем построчно
    $result = explode ('=', $line); // разбиваем строку и записываем в массив
    if (trim($result[0]) == 'sqlpass') // проверка на совпадение
        $sqlpass = trim($result[1]);
}
$dbconn = pg_pconnect("host=localhost dbname=authit user=authit password=".$sqlpass);
$qwe = "UPDATE comps SET (name, authtype, cert, crl, drv, descr, forcelogon, instdrv, radserver, fullpc) = ('".$H ."', '".$AUTHT."', '".$CACERT."', '".$CACRL."', '".$DRVPTH."', '".$DESCR."', '".$FORCELOGON."', '".$INSTDRV."', '".$RADSERVER."', '".$FULLPC."') WHERE id=".$ID;
$r = pg_query($dbconn, $qwe);
echo pg_last_error();

# Службы pam.d

if ($FULLPC == "false") {
$qwe = pg_query($dbconn, "delete from srvuse where comp='".$ID."';");	
$zapros = pg_query($dbconn, "SELECT * FROM services");
	echo pg_last_error();
	$vsego_strok = pg_num_rows($zapros);
	while ($vyvod = pg_fetch_array($zapros)) {
		if ($_POST['srv'.$vyvod['id']]) {
			$qwe = "INSERT INTO srvuse (srv, comp) VALUES ('".$vyvod['id']."', '".$ID."');";
			$r = pg_query($dbconn, $qwe);
		}
	}		
}
echo '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">

</head>
<body bgcolor="lightgrey">
<big><b><table bgcolor="white" width="100%"><tr><td width="100"><img src="favicon.ico"></td><td>Authenticate It (2fucktor)</td>
</tr></table></b></big><br>';
echo "<b>Сохранено:</b><br>";

echo 	'<table>
		 <TR> <td>Имя: </td> <td><font color="green">'.$H.'</font></td> </TR>
		 <TR> <td>Описание: </td> <td><font color="green">'.$DESCR.'</font></td> </TR>
		 <TR><td>Тип аутентификации: </td> <td><font color="green">'.$AUTHT.'</font></td></TR>
		 <TR><td>Сервер Radius: </td> <td><font color="green">'.$RADSERVER.'</font></td></TR>
		 <TR><td>Сертификат УЦ:</td> <td><font color="green">'.$CACERT.'</font></td></TR>
		 <TR><td>Список отзыва:</td> <td><font color="green">'.$CACRL.'</font></td></TR>		 
		 <TR><td>Драйвер: </td><td><font color="green">'.$DRVPTH.'</font></td></TR>
		</table><BR>
		<form action="index.php">
		<input type="submit" value="OK">
		</form>';
?>
