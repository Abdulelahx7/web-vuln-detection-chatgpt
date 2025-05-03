<?php
if(isset($_GET['url'])){
    $url = $_GET['url'];

    $whitelist = ["compl.html"];

    $scheme = parse_url($url, PHP_URL_SCHEME);
    $user = parse_url($url, PHP_URL_USER);
    $pass = parse_url($url, PHP_URL_PASS);
    $host = parse_url($url, PHP_URL_HOST);
    $port = parse_url($url, PHP_URL_PORT);
    $path = parse_url($url, PHP_URL_PATH);
    $query = parse_url($url, PHP_URL_QUERY);
    $fragment = parse_url($url, PHP_URL_FRAGMENT);

    if($scheme != "http" || $user != null || $pass != null || $host != "localhost" || $port != 80 || in_array($path,$whitelist) != true || $fragment != null){
        echo "no way";
    }
    else{
        $homepage = file_get_contents($url);
        echo $homepage  . "<br>";
    }
}else{
    echo "no get" . "<br>" ;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="" method="get">
        <input type="text" name="url">
    </form>
</body>
</html>