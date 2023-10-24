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

include "templates/header.php";
require_once "templates/menu.php";
require_once "auth/user.php";

if (isAuthenticated($con, $user, $name, $role)) {
    showMenu($user, $role);
    if (isset($_REQUEST['action'])) {
        if ($_REQUEST['action'] == "Change") {
            changeSettings($con, $user);
        }
    }
    else {
        editSettings($con, $user);
    }
    mysqli_close($con);
}
include "templates/footer.php";


function editSettings($con, $user)
{
    $query = "SELECT * FROM users WHERE user=" . $user;
    if ($result = mysqli_query($con, $query)) {
        if ($row = mysqli_fetch_array($result)) {
            echo "<div class=\"userList\">";
            echo "<form method=\"post\">";
            echo "<table>";
            echo "<tr><td>Account</td>";
            echo "<td>" . $row['name'] . "</td></tr>";
            echo "<tr><td>Name</td>";
            echo "<td><input type=\"text\" id=\"fullName\" name=\"fullName\" size=\"20\" value=\"". $row['fullName'] . "\"/></td></tr>";
            echo "<tr><td>Email</td>";
            echo "<td><input type=\"email\" id=\"userEmail\" name=\"userEmail\" size=\"20\" value=\"" . $row['email'] . "\"/></td></tr>";
            echo "<tr><td>Password</td>";
            echo "<td><input type=\"password\" id=\"userPass\" name=\"userPass\" size=\"20\" /></td></tr>";
            echo "</table>";
            echo "<input name=\"action\" type=\"submit\" id=\"change\" value=\"Change\"/>";
            echo "</form>";
            echo "</div>";
        }
        mysqli_free_result($result);
    }
}


function changeSettings($con, $user)
{
    $query = "SELECT * FROM users WHERE user=" . $user;
    if ($result = mysqli_query($con, $query)) {
        $newPassword = false;
        if ($row = mysqli_fetch_array($result)) {
            $salt = $row['salt'];
            $pass = $row['pass'];
            if (isset($_POST['userPass'])) {
                $newPass = $_POST['userPass'];
                if ($newPass != "") {
                    $newPassword = true;
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

            if (isset($_POST['fullName'])) {
                $newName = $_POST['fullName'];
                if ($newName != "") {
                    $name = $newName;
                }
            }

            $query = "UPDATE users SET salt='" . $salt . "',pass='" . $pass ."',email='" . $email . "',fullName='" . $name . "' WHERE user=". $user;
            if ($result = mysqli_query($con, $query)) {
                echo "<p>" . $name . ", your changes have been saved.</p>";
                if ($newPassword) {
                    echo "<p>Next time, please login with your new password.</p>";
                }
            }
            else {
                echo "<p>Could not save changed settings.</p>";
            }
        }
    }
}

?>

