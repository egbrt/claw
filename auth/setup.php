<?php
/*
    Classification Workbench
    Copyright (c) 2020-2021, WONCA ICPC-3 Foundation

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

require_once 'login.php';
startSecureSession();
?>

<html>
<head>
    <?php include "../templates/title.html" ?>
	<link type="text/css" rel="stylesheet" href="./auth.css" />
    <link type="text/css" rel="stylesheet" href="../styles/claw.css" />
</head>
<body>

<?php
include "../templates/head.html";

if (isset($_REQUEST['dbase'])) {
    echo "dbase = " . $_REQUEST['dbase'];
    if (connectToDatabase($_REQUEST['dbase'], $con)) {
        echo ", connected to dbase";
        if (isset($_POST['userName']) and isset($_POST['userPass'])) {
            echo ", creating admin";
            $name = $_POST['userName'];
            $fullName = $_POST['fullName'];
            $pass = $_POST['userPass'];
            $email = $_POST['userEmail'];
            $role = $_POST['userRole'];
            if (addNewUser($con, $name, $fullName, $pass, $email, $role)) {
                header('Location: ../index.php');
            }
            else {
                echo "something went wrong, please try again.";
            }
        }
        else {
            echo ", creating users table";
            createTable($con);
            echo "<div class=\"newUser\">";
            echo "<form method=\"post\">";
            echo "<table><tr><th colspan=2>Setup administrator</th></tr>";
            echo "<tr><td>Login name</td>";
            echo "<td><input type=\"text\" id=\"userName\" name=\"userName\" autofocus=\"autofocus\" size=\"20\" value=\"admin\" /></td></tr>";
            echo "<tr><td>Email</td>";
            echo "<td><input type=\"email\" id=\"userEmail\" name=\"userEmail\" size=\"20\" /></td></tr>";
            echo "<tr><td>Password</td>";
            echo "<td><input type=\"password\" id=\"userPass\" name=\"userPass\" size=\"20\" /></td></tr>";
            echo "<tr><td>Role</td>";
            echo "<td><select id=\"userRole\" name=\"userRole\"><option value=\"admin\">admin</option>";
            echo "</select></td></tr>";
            echo "<tr><td>Full name</td>";
            echo "<td><input type=\"text\" id=\"fullName\" name=\"fullName\" autofocus=\"autofocus\" size=\"20\" /></td></tr>";
            echo "</table>";
            echo "<input type=\"submit\" id=\"add\" value=\"Add\"/>";
            echo "</form>";
            echo "</div>";
        }
    }
    else {
        echo "Unable to connect to database: " . $_REQUEST['dbase'];
    }
}
else {
    echo "Usage: ..setup.php?dbase=id_of_dbase";
}
include "../templates/footer.php";


function createTable($con)
{
    echo ", drop table 'users' if it already exists";
    $query = "DROP TABLE IF EXISTS users";
    if (!mysqli_query($con, $query)) {
        echo mysqli_error($con);
    }
    echo ", create table 'users'";
    $query = "CREATE TABLE `users` (
                `user` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `name` varchar(40) NOT NULL,
                `pass` blob NOT NULL,
                `salt` blob NOT NULL,
                `email` varchar(40) NOT NULL,
                `lastlogin` timestamp NOT NULL DEFAULT current_timestamp(),
                `role` enum('admin','editor','writer','reader') NOT NULL,
                `fullName` varchar(80)
            )";
	if ($result = mysqli_query($con, $query)) {
        echo ", users table created";
        $valid = true;
    }
    else {
        echo ", could not create 'users'";
        die('Could not create table "users"');
    }
}

/**
* addNewUser
*
*/
function addNewUser($con, $name, $fullName, $pass, $email, $role)
{
    $valid = false;
    $salt = random_bytes(20);
    $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
    $query = "INSERT INTO users (name, pass, salt, email, role, fullName) VALUES ('" . $name . "','" . $hashedPass . "','" . $salt . "','" . $email . "','" . $role  . "','" . $fullName . "')";
    if ($result = mysqli_query($con, $query)) {
        $valid = true;
    }
    else {
        die('Could not add administrator: ' . mysqli_error($con));
	}
	return $valid;
}


?>
