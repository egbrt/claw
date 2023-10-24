<?php
/*
    Classification Workbench
    Copyright (c) 2020-2022, WONCA ICPC-3 Foundation

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
include "../templates/headAdmin.html";

if (isAuthenticated($con, $user, $name, $role)) {
    if ($role == "admin") {
        if (isset($_REQUEST['action'])) {
            if ($_REQUEST['action'] == "Add") {
                if (getCredentials($userName, $pass, $email, $role, $fullName)) {
                    addUser($con, $userName, $pass, $email, $role, $fullName);
                }
            }
            elseif ($_REQUEST['action'] == "Change") {
                changeUser($con);
            }
            elseif ($_REQUEST['action'] == "Delete") {
                deleteUser($con);
            }
            elseif ($_REQUEST['action'] == "Reset") {
                resetUser($con);
            }
        }
        showUsers($con);
    }
    else {
        echo "You're not an administrator";
        $_SESSION = array();
        showPasswordForm();
    }
}
else {
    showPasswordForm();
}
include "../templates/footer.php";


/**
* showUsers
* shows users
*/
function showUsers($con)
{
    $query = "SELECT * FROM users";
    if ($result = mysqli_query($con, $query)) {
        $role = "";
        $name = "";
        $email = "";
        $fullName = "";
        $selectedUser = -1;
        if (isset($_REQUEST['user'])) {
            $selectedUser = $_REQUEST['user'];
        }
        echo "<div class=\"userList\">";
        echo "<table>";
        echo "<tr><th>user</th><th>name</th><th>email</th><th>last login</th><th>role</th></tr>";
        while ($row = mysqli_fetch_array($result)) {
            echo "<tr>";
            echo "<td>" . $row['user'] . "</td>";
            echo "<td><a href=\"admin.php?user=" . $row['user'] . "\">" . $row['name'] . "</td>";
            //echo "<td>" . $row['pass'] . "</td>";
            //echo "<td>" . $row['salt'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . $row['lastlogin'] . "</td>";
            echo "<td>" . $row['role'] . "</td>";
            echo "</tr>";
            if ($row['user'] == $selectedUser) {
                $role = $row['role'];
                $name = $row['name'];
                $email = $row['email'];
                $fullName = $row['fullName'];
            }
        }
        echo "</table>";
        echo "</div>";
        mysqli_free_result($result);
    }
    

    echo "<div class=\"newUser\">";
    echo "<form method=\"post\">";
    echo "<table>";

    echo "<tr><td>Role</td>";
    echo "<td><select id=\"userRole\" name=\"userRole\">";
    echo "<option value=\"admin\"";
    if ($role == "admin") echo " selected=\"selected\"";
    echo ">admin</option>";
    echo "<option value=\"editor\"";
    if ($role == "editor") echo " selected=\"selected\"";
    echo ">editor</option>";
    echo "<option value=\"writer\"";
    if ($role == "writer") echo " selected=\"selected\"";
    echo ">writer</option>";
    echo "<option value=\"reader\"";
    if ($role == "reader") echo " selected=\"selected\"";
    echo ">reader</option>";
    echo "</select></td></tr>";

    echo "<tr><td>Name</td>";
    echo "<td><input type=\"text\" id=\"userName\" name=\"userName\" autofocus=\"autofocus\" size=\"20\" value=\"" . $name . "\"";
    if ($name != "") echo " disabled";
    echo "/></td></tr>";
    echo "<tr><td>Email</td>";
    echo "<td><input type=\"email\" id=\"userEmail\" name=\"userEmail\" size=\"20\" value=\"" . $email . "\"/></td></tr>";
    echo "<tr><td>Password</td>";
    echo "<td><input type=\"password\" id=\"userPass\" name=\"userPass\" size=\"20\" /></td></tr>";
    echo "<tr><td>Full Name</td>";
    echo "<td><input type=\"text\" id=\"fullName\" name=\"fullName\" size=\"20\" value=\"" . $fullName . "\"/></td></tr>";
    echo "</table>";
    echo "<input type=\"submit\" name=\"action\" value=\"Add\"/>";
    echo "<input type=\"submit\" name=\"action\" value=\"Change\"/>";
    echo "<input type=\"submit\" name=\"action\" value=\"Delete\"/>";
    echo "<input type=\"submit\" name=\"action\" value=\"Reset\"/><br/>(Reset sends email to user to reset password)";
    echo "</form>";
    echo "</div>";
}


function addUser($con, $name, $pass, $email, $role, $fullName)
{
    global $EMAIL_FROM, $EMAIL_GREETINGS, $EMAIL_SUBJECT;
    $query = "SELECT * FROM users WHERE name='" . $name . "'";
    if ($result = mysqli_query($con, $query)) {
        if ($row = mysqli_fetch_array($result)) {
            echo "user exists";
        }
        else {
            $salt = random_bytes(20);
            $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (name, pass, salt, email, role, fullName) VALUES ('" . $name . "','" . $hashedPass . "','" . $salt . "','" . $email . "','" . $role . "','" . $fullName . "')";
            if ($result = mysqli_query($con, $query)) {
                $headers = "From: " . $EMAIL_FROM;

                $message = "Dear " . $fullName . ",\n\n";
                $message .= "you can login to your new account by clicking the following link\n";
                $message .= "https://" . $_SERVER['SERVER_NAME'] . "/newuser.php?dbase=" . $_SESSION['dbase'] . "&name=" . $name . "&fp=" . bin2hex($hashedPass) . "\n\n\n";
                $message .= $EMAIL_GREETINGS;
                $subject = $EMAIL_SUBJECT;
                if (!mail($email, $subject, $message, $headers)) {
                    echo "Failed to send email<br/>";
                    echo $headers . "<br/>Subject: " . $subject . "<br/>" . $message . "<br/>";
                }
            }
        }
    }
}


function changeUser($con)
{
    $user = $_REQUEST['user'];
    $query = "SELECT * FROM users WHERE user=" . $user;
    if ($result = mysqli_query($con, $query)) {
        if ($row = mysqli_fetch_array($result)) {
            $salt = $row['salt'];
            $pass = $row['pass'];
            if (isset($_POST['userPass'])) {
                $newPass = $_POST['userPass'];
                if ($newPass != "") {
                    $salt = random_bytes(20);
                    $pass = password_hash($newPass, PASSWORD_DEFAULT);
                }
            }

            $email = $row['email'];
            if (isset($_POST['userEmail'])) {
                $newEmail = $_POST['userEmail'];
                if ($newEmail != "") {
                    $email = $newEmail;
                }
            }

            $role = $row['role'];	
            if (isset($_POST['userRole'])) {
                $newRole = $_POST['userRole'];
                if ($newRole != "") {
                    $role = $newRole;
                }
            }

            $name = $row['fullName'];
            if (isset($_POST['fullName'])) {
                $newName = $_POST['fullName'];
                if ($newName != "") {
                    $name = $newName;
                }
            }

            $query = "UPDATE users SET salt='" . $salt . "',pass='" . $pass ."',email='" . $email . "',role='" . $role . "',fullName='" . $name . "' WHERE user=". $user;
            mysqli_query($con, $query);
        }
        mysqli_free_result($result);
    }
}


function deleteUser($con)
{
    $user = $_REQUEST['user'];
    $query = "DELETE FROM users WHERE user=" . $user;
    mysqli_query($con, $query);
}


function resetUser($con)
{
    global $EMAIL_FROM, $EMAIL_GREETINGS, $EMAIL_SUBJECT;
    $user = $_REQUEST['user'];
    $query = "SELECT * FROM users WHERE user=" . $user;
    if ($result = mysqli_query($con, $query)) {
        if ($row = mysqli_fetch_array($result)) {
            $email = $row['email'];
            $name = $row['name'];
            $fullName = $row['fullName'];
            $salt = random_bytes(20);
            $pass = random_bytes(20);
            $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
            $query = "UPDATE users SET pass='" . $hashedPass . "', salt='" . $salt . "' WHERE user=" . $user;
            if ($result = mysqli_query($con, $query)) {
                $headers = "From: " . $EMAIL_FROM;

                $message = "Dear " . $fullName . ",\n\n";
                $message .= "you can reset your password by clicking the following link\n";
                $message .= "https://" . $_SERVER['SERVER_NAME'] . "/newuser.php?dbase=" . $_SESSION['dbase'] . "&name=" . $name . "&fp=" . bin2hex($hashedPass) . "\n\n\n";
                $message .= $EMAIL_GREETINGS;
                $subject = $EMAIL_SUBJECT;
                if (!mail($email, $subject, $message, $headers)) {
                    echo "Failed to send email";
                    echo $headers . "<br/>Subject: " . $subject . "<br/>" . $message . "<br/>";
                }
                mysqli_free_result($result);
            }
        }
    }
}
?>
