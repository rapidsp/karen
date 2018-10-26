<?php
$srvname = '';
if(isset($_POST['srvname'])) $srvname = $_POST['srvname'];

$content = file ('/etc/authit.conf');
foreach ($content as $line) { // читаем построчно
    $result = explode ('=', $line); // разбиваем строку и записываем в массив
    if (trim($result[0]) == 'sqlpass') // проверка на совпадение
        $sqlpass = trim($result[1]);
}

$dbconn = pg_pconnect("host=localhost dbname=authit user=authit password=".$sqlpass);

echo pg_last_error();

$qwe = "INSERT INTO services (srv) VALUES ('".$srvname."')";
$r = pg_query($dbconn, $qwe);
echo pg_last_error();

echo '
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">

</head>
<body bgcolor="lightgrey">
<big><b><table bgcolor="white" width="100%"><tr><td width="100"><img src="favicon.ico"></td><td>Authenticate It (2fucktor)</td>
</tr></table></b></big><br>
Добавлена служба pam.d: <font color="green">'.$srvname.'</font><br>
<form action="index.php"><input type="submit" value="Назад"></form>
';
?>
