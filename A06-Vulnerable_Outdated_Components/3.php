<?php
function getContext(){
    $ctx = stream_context_create([
        'ssl' => [
          'crypto_method' =>
            STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT
        ],
      ]);
    
    return $ctx;
}

if(isset($_GET["submit"])){
  $myfile = fopen("./secrets/secret.txt", "w") or die("Unable to open file!");
  fwrite($myfile, strval(getContext()));
  fclose($myfile);
}
?>