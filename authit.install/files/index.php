<!DOCTYPE html>
<html>
 <head>
  <meta charset="utf-8">
	<title>Authenticate It (2fucktor)</title>
<!--	<header><big><b>Authenticate It (2fucktor)</b></big></header> 
 </head> -->

<!-- Requirements
php-ssh2
-->	

<body bgcolor="lightgrey"> 
<big><b><table bgcolor="white" width="100%"><tr><td width="100"><img src="favicon.ico"></td><td>Authenticate It (2fucktor)</td>
</tr></table></b></big>	 
<br>
<form>

<!-- <select name="id" size="25"> -->


<table border="0"> <thead>
	<tr>
	<input type="submit" value="Добавить компьютер" formaction="comp.php"></tr>
	<tr style="font-weight:bold;">
		<td>ID</td>
		<td>Имя</td>
		<td>Описание</td>
		<td>Тип входа</td>
		<td>Путь к сертификату</td>
		<td>CRL</td>
		<td>Драйвер</td>
		<td>Radius</td>
	</tr> </thead>


<?php	

$content = file ('/etc/authit.conf');
foreach ($content as $line) { // читаем построчно
    $result = explode ('=', $line); // разбиваем строку и записываем в массив
    if (trim($result[0]) == 'sqlpass') // проверка на совпадение
        $sqlpass = trim($result[1]);
}
$dbconn = pg_pconnect("host=localhost dbname=authit user=authit password=".$sqlpass);
echo pg_last_error();

	$zapros = pg_query($dbconn, "SELECT * FROM comps ORDER BY name");
	echo pg_last_error();
	$vsego_strok = pg_num_rows($zapros);
	$i=1;
	while ($vyvod = pg_fetch_array($zapros)) {
		# Подсветка четных строк
		if ($i % 2) {
			$grcolor = ' bgcolor="lightyellow" ';}
		else {
			$grcolor = '';}	
		#$phplink = 'comp.php?id=\"'.strval($vyvod["id"]).'\"';
		#echo $grcolor.'<br>';	
		#$delstr = 'compdel.php?id='.$vyvod["id"]
		echo '<tr'.$grcolor.'><td>'.$vyvod["id"].'</td>
		<td><a href="comp.php?id='.$vyvod["id"].'">'.$vyvod["name"].'</a></td>
		<td><a href="comp.php?id='.$vyvod["id"].'">'.$vyvod["descr"].'</a></td>
		<td>'.$vyvod["authtype"].'</td>
		<td>'.$vyvod["cert"].'</td>
		<td>'.$vyvod["crl"].'</td>
		<td>'.$vyvod["drv"].'</td>
		<td>'.$vyvod["radserver"].'</td></tr>';
		$i++;
		} ?>
		</table>
<!--	</select> -->
	
	
</form>

</body>

<?php
function eval_string($str, $wdth) {
	$n = $wdth - strlen($str);
	return $str . str_repeat("&nbsp;", $n);
	}
	?>
