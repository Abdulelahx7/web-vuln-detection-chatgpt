<?php 
    if(isset($_POST['data'])){
        $xmlfile = $_POST['data'];
        libxml_disable_entity_loader(true);
        $dom = new DOMDocument();
        $dom->loadXML($xmlfile, LIBXML_NOENT);
        $creds = simplexml_import_dom($dom);
        $user = $creds->user;
        $pass = $creds->pass;
        echo "You have logged in as user ". htmlentities($user);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <form action="" method="post">
    <textarea rows = "5" cols = "60" name="data"></textarea><br/>
    <input type="submit" value="submit">
    </form>
</body>
</html>