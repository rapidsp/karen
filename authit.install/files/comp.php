<?php
echo '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">

</head>
<body bgcolor="lightgrey">
<big><b><table bgcolor="white" width="100%"><tr><td width="100"><img src="favicon.ico"></td><td>Authenticate It (2fucktor)</td>
</tr></table></b></big>';

#echo $argc.'<br>'.$argv[1].'<br>'.'<br>'.$_GET['id'];
#echo "SELECT * FROM comps WHERE id=".$_GET['id'];

$sslcheck = '';
$wincheck = '';
$otpcheck = '';

$content = file ('/etc/authit.conf');
foreach ($content as $line) { // читаем построчно
    $result = explode ('=', $line); // разбиваем строку и записываем в массив
    if (trim($result[0]) == 'sqlpass') // проверка на совпадение
        $sqlpass = trim($result[1]);
}
$dbconn = pg_pconnect("host=localhost dbname=authit user=authit password=".$sqlpass);
$allcheck = "";
$customcheck = "";

# Если это редактирование а не добавление
if ($_GET['id']) {
$zapros = pg_query($dbconn, "SELECT * FROM comps WHERE id=".$_GET['id']);
echo pg_last_error();
$vyvod = pg_fetch_array($zapros);
$name = $vyvod['name'];
$authtype = $vyvod['authtype'];
$cert = $vyvod['cert'];
$crl = $vyvod['crl'];
$drv = $vyvod['drv'];
$radserver = $vyvod['radserver'];
$descr = $vyvod['descr'];
if ($vyvod['forcelogon'] == 't') $forcelogon = "checked"; else $forcelogon = "";
if ($vyvod['instdrv'] == 't') $instdrv = "checked"; else $instdrv = "";
if ($vyvod['fullpc'] == 't') $allcheck = "checked"; else $customcheck = "checked";

if ($authtype == "openssl") $sslcheck = "checked";
elseif ($authtype == "windows") $wincheck = "checked";
elseif ($authtype == "otp") $otpcheck = "checked";

# Расставляем галочки для служб pam.d
$zapros = pg_query($dbconn, "SELECT * FROM services");
echo pg_last_error();
$vsego_strok = pg_num_rows($zapros);
while ($vyvod = pg_fetch_array($zapros)) {
	$qwe = pg_query($dbconn, "SELECT * FROM srvuse WHERE comp=".$_GET['id']." and srv = ".$vyvod['id']);
	if (pg_num_rows($qwe) > 0) $checksrv[$vyvod['id']] = "checked"; else $checksrv[$vyvod['id']] = '';
}

}

echo '<form name="getauth" action="doauth.php" method="post">
	<P> <img src="comp.png" width="2%" height="2%">
	<table><tr>
	<td><input type="hidden" name="id" value="'.$_GET['id'].'"> <B>Имя (IP) компьютера: </td><td> <INPUT type="text" name="hname" value="'.$name.'"> </B>
	Пароль root: <INPUT type="password" name="pwroot" > </td></tr><tr>
	<td>Описание </td><td><INPUT type="text" name="descr" value="'.$descr.'"></td></tr></table>
	<SMALL><font color="blue"> Необходимо на удаленном ПК <br>
	предоставить пользователю root право входа по SSH: <BR>
	В /etc/ssh/sshd_config установить <B>PermitRootLogin yes</B> </font></SMALL> </P> <BR>
	<hr>
	<P> <img src="auth.png" width="3%" height="3%">
	<font color="brown"><BR> <B>Выбор типа аутентификации:</B> <BR></font>
	<table><tr><td width="500">
	<input type="radio" name="authtype" value="openssl" '.$sslcheck.'> Сертификат OpenSSL<BR>
	<input type="radio" name="authtype" value="windows" '.$wincheck.'> Сертификат домена Windows <BR> 
	<input type="radio" name="authtype" value="otp" '.$otpcheck.'> Одноразовый пароль <BR>
	</td><td> Сервер Radius: <input type="text" name="radserver" value="'.$radserver.'"><br>
	Секрет сервера: <input type="text" name="radpass"></td>
	</tr></table>
	<input type="checkbox" name="forcelogon" '.$forcelogon.'> Исключить другие способы аутентификации 
	<font color="red"><small>(При выборе этой опции убедитесь в наличии актуального аутентификатора!)</small></font><br>
	<br><b>Настроить:</b><br>
	<input type="radio" name="fullpc" value="all" '.$allcheck.'> Для всего компьютера <br><br>
	<input type="radio" name="fullpc" value="custom" '.$customcheck.'> Для определенных служб: <br>';
	
# Список служб:
$zapros = pg_query($dbconn, "SELECT * FROM services");
	echo pg_last_error();
	$vsego_strok = pg_num_rows($zapros);
	echo '<table> <tr>';
	$i = 1;
	while ($vyvod = pg_fetch_array($zapros)) {
		if ($i > 4) {
			echo '</tr><tr>';
			$i=1;
			}
		echo '<td><input name="srv'.$vyvod["id"].'" type="checkbox" value="'.$vyvod["srv"].'" '.$checksrv[$vyvod['id']].'> '.$vyvod["srv"].'</td>';
		$i++;
		}	
	echo '</tr></table>';	
	echo '<input type="submit" formaction="srvadd.html" value="Добавить">';
	echo '<hr>';
	
echo	'</P><br>
	<P> <img src="cert.png" width="2%" height="2%"> <BR>
	<font color="brown"><B>Сертификаты УЦ:</B> <BR></font>
	Корневой сертификат: <INPUT type="text" name="cacert" value="'.$cert.'"> <BR>
	<SMALL> Файл должен находиться в /var/www/authit/ </SMALL> <BR> <BR>
	   Список отозванных <BR>сертификатов (CRL):&nbsp; &nbsp; <INPUT type="text" name="cacrl" value="'.$crl.'"> <BR>
	<SMALL> Файл должен находиться в /var/www/authit/ </SMALL> </P> <BR>
	<hr>
	<font color="brown"><input type="checkbox" name="instdrv" '.$instdrv.'><B>Установить драйвер:<B> </font>
			
	<select name="drvpth">';
# Список драйверов из каталога drv
echo "<p>";
$listdrv = scandir("drv");
foreach ($listdrv as $fdrv) { 
	if ($fdrv <> "." and $fdrv <> "..") {
		if ($fdrv == $drv) $seldrv = 'selected '; else $seldrv = '';
			echo '<option '.$seldrv.'value="'.$fdrv.'"'." > ".$fdrv.'</option>';
		}	
	}	
?>	
</select>
	</p>	<hr size="5" color="grey">	<p>
	<br><input type="submit" value="Выполнить настройку">
<?php	if (!$_GET['id']){ ?>
	<input type="submit" value="Добавить" formaction="compadd.php">
<?php }
else { ?>	
	<input type="submit" value="Сохранить" formaction="compupd.php">
	<input type="submit" value="Удалить" formaction="compdel.php">
<?php } ?>	
</p>

</form>

<!-- </CENTER> -->

</BODY>

</HTML>
