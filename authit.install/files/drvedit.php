<?php
# postgresql

$content = file ('/etc/authit.conf');
foreach ($content as $line) { // читаем построчно
    $result = explode ('=', $line); // разбиваем строку и записываем в массив
    if (trim($result[0]) == 'sqlpass') // проверка на совпадение
        $sqlpass = trim($result[1]);
}
$dbconn = pg_pconnect("host=localhost dbname=authit user=authit password=".$sqlpass);

echo pg_last_error();

echo '<font color="grey"><b>Список драйверов:</b></font><BR> <BR>';

echo '<table border = "0">';
	echo '<tr> <B> <th>ОС</th> <th>Путь к драйверу</th> <th>Тип смарт-карты</th> </B> </TR>';
	$zapros = pg_query($dbconn, "SELECT * FROM drvs");
	echo pg_last_error();
	$vsego_strok = pg_num_rows($zapros);
	while ($vyvod = pg_fetch_array($zapros)) {
		echo '<TR>';
		echo "<td>".$vyvod['os']." ";
		echo '<td><font color="blue">'.$vyvod['drvpath']."</font></td>";
		echo "<td>".$vyvod['sctype']."</td>";
		echo "</TR>";
		}
		echo '<TR> <form action="index.php" method="post">';
		echo '<TD> <input type="text" name="os"></TD>';
		echo '<TD> <input type="text" name="drvpath" size="50"></TD>';
		echo '<TD> <input type="text" name="sctype"></TD>';
		echo '<TD> <input type="submit" value=">>" formaction="drvadd.php"></TD>';	
		echo '</TR>';
	echo "</table>";
	echo '<BR><input type="submit" value="<< Назад">';
	echo '</form>';


?>
