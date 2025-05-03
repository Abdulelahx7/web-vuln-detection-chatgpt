<?php
if(isset($_GET["aaaaa"])){
    $greet = ["hello","how", "are", "you"];
    $data = $_GET["aaaaa"];
    if(in_array($data, $greet)){
        eval("echo ".$data.";");
    }
}else{
    echo "no GET";
}
?>