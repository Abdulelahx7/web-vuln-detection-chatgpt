<?php


if(isset($_GET['submit'])){

    $url = $_GET['url'];
    $escaped_url = escapeshellcmd($url);
    $escaped_url = htmlentities($escaped_url);
    echo shell_exec("ping -c 4 $escaped_url");
}
else{
    echo "no get";
}

?>

<html>

<form action="" method="get">
URL: <input type="text" name = "url">
<input type="submit" name="submit">
</form>
</html>