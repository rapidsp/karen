<?php

$os = "Не известно";
$drvpath = "Не известно";
$sctype = "Не известно";
if(isset($_POST['os'])) $os = $_POST['os'];
if (isset($_POST['drvpath'])) $drvpath = $_POST['drvpath'];
if (isset($_POST['sctype'])) $sctype = $_POST['sctype'];

$content = file ('/etc/authit.conf');
foreach ($content as $line) { // читаем построчно
    $result = explode ('=', $line); // разбиваем строку и записываем в массив
    if (trim($result[0]) == 'sqlpass') // проверка на совпадение
        $sqlpass = trim($result[1]);
}

$dbconn = pg_pconnect("host=localhost dbname=authit user=authit password=".$sqlpass);

echo pg_last_error();

$qwe = "INSERT INTO drvs (os, drvpath, sctype) VALUES ('".$os."','" . $drvpath . "','" . $sctype . "')";
$r = pg_query($dbconn, $qwe);
echo pg_last_error();

echo '<form action="drvedit.php">';
echo '<BR><B>Добавлено</B><BR>';
echo 'ОС: <font color="green">'.$os.'<BR></font>';
echo 'Драйвер: <font color="green">'.$drvpath.'<BR></font>';
echo 'Тип смарт-карты: <font color="green">'.$sctype.'<BR></font>';
echo '<input type="submit" value="OK">';
echo '</form>';

?>
