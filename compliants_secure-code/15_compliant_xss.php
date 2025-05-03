<?php

include "../mysql_login.php";

$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

if(isset($_POST['password'])){

    if($_POST['password'] == '1234'){

        $stmt = $conn->prepare("SELECT * FROM USERS");
        $stmt->execute();
        

        foreach ($stmt as $row) {
            echo "id : " . $row['id'] . "<br>";
            echo htmlentities("name : " . $row['Name']) . "<br>";
            echo htmlentities("address : " . $row['Address']) . "<br>";
            echo "<br>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
</head>
<body>
    <form action="" method="POST">
        <div>Password for admin: </div>
        <input name="password" type="password" />
        <input name="submit" type="submit" />
    </form>
</body>
</html>