<?php


    $pages = array(
        1 => "id_1.html",
        2 => "id_2.html"
    );

    if (isset ($_GET ['page']))
    {
        if(is_numeric($_GET['page']))
        {
            echo file_get_contents($pages[$_GET['page']]);
        }
        else{
            echo "no way";
        }
    }
    else{
        echo "no get";
    }

?>