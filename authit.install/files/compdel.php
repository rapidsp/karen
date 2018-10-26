<?php

$H = 'hname';
$PSSWD = 'pwroot';
$AUTHT = 'authtype';
$CACERT = 'cacert';
$CACRL = 'cacrl';
$DRVPTH = 'drvpth';
$ID = 'id';

$H = $_POST["hname"];
$PSSWD = $_POST["pwroot"];
$AUTHT = $_POST["authtype"];
$CACERT = $_POST["cacert"];
$CACRL = $_POST["cacrl"];
$DRVPTH = $_POST['drvpth'];
$ID = $_POST['id'];

$content = file ('/etc/authit.conf');
foreach ($content as $line) { // читаем построчно
    $result = explode ('=', $line); // разбиваем строку и записываем в массив
    if (trim($result[0]) == 'sqlpass') // проверка на совпадение
        $sqlpass = trim($result[1]);
}
$dbconn = pg_pconnect("host=localhost dbname=authit user=authit password=".$sqlpass);
$qwe = "DELETE FROM comps WHERE id=".$ID;
$r = pg_query($dbconn, $qwe);
echo pg_last_error();

echo "<BR><b>Удалено:</b><br>";

echo 	'<table>
		 <TR> <td>Имя: </td> <td><font color="green">'.$H.'</font></td> </TR>
		 <TR><td>Тип аутентификации: </td> <td><font color="green">'.$AUTHT.'</font></td></TR>
		 <TR><td>Сертификат УЦ:</td> <td><font color="green">'.$CACERT.'</font></td></TR>
		 <TR><td>Список отзыва:</td> <td><font color="green">'.$CACRL.'</font></td></TR>		 
		 <TR><td>Драйвер: </td><td><font color="green">'.$DRVPTH.'</font></td></TR>
		</table><BR>
		<form action="index.php">
		<input type="submit" value="OK">
		</form>';
?>
