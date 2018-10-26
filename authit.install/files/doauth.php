<?php
$H = 'hname';
$PSSWD = 'pwroot';
$AUTHT = 'authtype';
$CACERT = 'cacert';
$CACRL = 'cacrl';
$DRVPTH = 'drvpth';
$INSTDRV = '';
$FORCELOGON = '';
$RADSERVER = '';
$RADPASS = '';

$H = $_POST["hname"];
$PSSWD = $_POST["pwroot"];
$AUTHT = $_POST["authtype"];
$CACERT = $_POST["cacert"];
$CACRL = $_POST["cacrl"];
$DRVPTH = $_POST['drvpth'];
$RADSERVER = $_POST['radserver'];
$RADPASS = $_POST['radpass'];
if ($_POST['instdrv']) $INSTDRV = true; else $INSTDRV = false;
if ($_POST['forcelogon']) $FORCELOGON = '1'; else $FORCELOGON = '0';

#$dbconn = pg_pconnect("host=localhost dbname=authit user=authit password=qwerty");
#$qwe = "INSERT INTO comps (name, authtype, cert, crl, drv) VALUES ('".$H ."', '".$AUTHT."', '".$CACERT."', '".$CACRL."', '".$DRVPTH."')";
#$r = pg_query($dbconn, $qwe);
#echo pg_last_error();

echo '<!DOCTYPE html>';
echo '<html>';
echo '<head>';
echo '<meta charset="utf-8">';
echo '</head>';
echo '<body bgcolor="lightgrey">';

echo '<form action="index.php">';
$exitt = 0;

if ($AUTHT == "openssl") {
	# Проверка наличия файлов
	
	If (file_exists($CACERT)) {
		echo '<font color="green">Найден файл '.$CACERT.' </font><br>'; }
	else {
		echo '<font color="red">'.$CACERT.' не найден!</font><br>' ;
		$exitt = 1;}
	
	If (file_exists($CACRL)) {
		echo '<font color="green">Найден файл '.$CACRL.' </font><br>'; }
	else {
		echo '<font color="red">'.$CACRL.' не найден!</font><br>'; 
		$exitt = 1;}
	
	If (file_exists($DRVPTH)) {
		echo '<font color="green">Найден файл '.$DRVPTH.' </font><br>'; }
	else {
		echo '<font color="red">'.$DRVPTH.' не найден!</font><br>' ;
		$exitt = 1;}
}
	
# Проверка доступа по SSH
$sshtest = ssh2_connect($H, 22);
if ($sshtest)
	echo '<font color="green">'.$H.' доступен по SSH. OK!</font><br>';
else {
	echo '<font color="red">'.$H.' - нет доступа по SSH!</font><br>' ;
	$exitt = 1;}	
	
if ($exitt) {
	echo '<BR><input type="submit" value="Назад">'; 
	exit;
	}
$noerr = true;

$content = file ('/etc/authit.conf');
foreach ($content as $line) { // читаем построчно
    $result = explode ('=', $line); // разбиваем строку и записываем в массив
    if (trim($result[0]) == 'host_name') // проверка на совпадение
        $host_name = trim($result[1]);
}

if (ssh2_auth_password($sshtest, "root", $PSSWD)) {
	# Копирование файлов 
	# Необходимо решить вопрос с fqdn портала
	if ($AUTHT == "openssl") {
		$authout = ssh2_exec($sshtest, "wget http://".$host_name.":2222/".$CACERT." -P /etc/ssl/  > /tmp/auth-wget.log");
		if ($authout) {
			echo "Скопирован файл $CACERT<BR>";
			$sumres = $authout;
		}
		else echo '<font color="red">Ошибка копирования '.$CACERT.' </font><BR>';
		$authout = ssh2_exec($sshtest, "wget http://".$host_name.":2222/".$CACRL." -P /etc/ssl/crl/ >> /tmp/auth-wget.log");
		if ($authout) {
			echo "Скопирован файл $CACRL<BR>";
			$sumres = $sumres.$authout;
		}
		else echo '<font color="red">Ошибка копирования '.$CACRL.' </font><BR>';
	
		$authout = ssh2_exec($sshtest, "wget 'http://".$host_name.":2222/makessl.sh' -P /tmp/ >> /tmp/auth-wget.log");
		if ($authout) {
			echo "Скопирован файл makessl.sh<BR>";
		}
		else echo '<font color="red">Ошибка копирования makessl.sh </font><BR>';
		sleep(1);
		$authout = ssh2_exec($sshtest, "wget 'http://".$host_name.":2222/makesslend.sh' -P /tmp/ >> /tmp/auth-wget.log");
		sleep(1);
		if ($authout) {
			echo "Скопирован файл makesslend.sh<BR>";
		}
		else echo '<font color="red">Ошибка копирования makesslend.sh </font><BR>';
	
		if ($INSTDRV){
			$authout = ssh2_exec($sshtest, "wget 'http://".$host_name.":2222/".$DRVPTH."' -P /tmp/ >> /tmp/auth-wget.log");
			sleep(1);
			if ($authout) {
				echo "Скопирован файл $DRVPTH<BR>";
				$sumres = $sumres.$authout;
			}
			else echo '<font color="red">Ошибка копирования '.$DRVPTH.' </font><BR>';
		}
	}
	elseif ($AUTHT == "otp") {
		If ($RADSERVER) {
			$authout = ssh2_exec($sshtest, "wget 'http://".$host_name.":2222/makeotp.sh' -P /tmp/ >> /tmp/auth-wget.log");
			sleep(1);
			if ($authout) {
				echo "Скопирован файл makeotp.sh<BR>";
			}
		else {
			echo 'Не указан сервер Radius!';
			exit;
			}
		}	
	}

if ($AUTHT == "openssl") {	
	#Выполнение makessl
	echo '<br> bash /tmp/makessl.sh '.$DRVPTH.'<br>';
	$authout = ssh2_exec($sshtest, "bash /tmp/makessl.sh ".$DRVPTH);
	$errorStream = ssh2_fetch_stream($authout, SSH2_STREAM_STDERR);
	stream_set_blocking($authout, true);
	stream_set_blocking($errorStream, true);
	echo '<textarea readonly rows="20" cols="80">'.stream_get_contents($authout). '----Errors: '.stream_get_contents($errorStream).'</textarea> <br>';
	fclose($authout);
	fclose($errorStream);
	sleep(1);
	if ($authout) {
		echo "Выполнен makessl.sh<BR>";
	}
	else echo '<font color="red">Ошибка Выполнение makessl.sh </font><BR>';
	
	
	#Выполнение makesslend
	#echo '<br>'.$FORCELOGON.'<br>';
	$authout = ssh2_exec($sshtest, "bash /tmp/makesslend.sh ".$FORCELOGON);
	$errorStream = ssh2_fetch_stream($authout, SSH2_STREAM_STDERR);
	stream_set_blocking($authout, true);
	stream_set_blocking($errorStream, true);
	echo '<textarea readonly rows="20" cols="80">'.stream_get_contents($authout). '----Errors: '.stream_get_contents($errorStream).'</textarea> <br>';
	fclose($authout);
	fclose($errorStream);
	sleep(1);
	if ($authout) {
		echo "Выполнен makesslend.sh<BR>";
	}
	else echo '<font color="red">Ошибка Выполнение makesslend.sh </font><BR>';
}
elseif ($AUTHT == "otp") {
	#Выполнение makeotp
	$authout = ssh2_exec($sshtest, "bash /tmp/makeotp.sh ".$RADSERVER." ".$RADPASS);
	$errorStream = ssh2_fetch_stream($authout, SSH2_STREAM_STDERR);
	stream_set_blocking($authout, true);
	stream_set_blocking($errorStream, true);
	echo '<textarea readonly rows="20" cols="80">'.stream_get_contents($authout). '----Errors: '.stream_get_contents($errorStream).'</textarea> <br>';
	fclose($authout);
	fclose($errorStream);
	sleep(1);
	if ($authout) {
		echo "Выполнен makeotp.sh<BR>";
	}
	else echo '<font color="red">Ошибка Выполнение makeotp.sh </font><BR>';	
}
}
	
else echo '<br> Ошибка соединения SSH <br>';

echo '<BR><BR><input type="submit" value="Назад">';
echo '</form>';

echo '</BODY>';
echo '</HTML>';

?>
