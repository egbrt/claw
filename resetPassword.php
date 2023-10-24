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

include "templates/header.php";
require_once "templates/menu.php";
require_once "auth/user.php";

if (isset($_REQUEST['userName']) and isset($_REQUEST['userDB'])) {
    $valid = false;
    if (connectToDatabase($_POST['userDB'], $con)) {
        $_SESSION['dbase'] = $_POST['userDB'];
        $valid = resetUser($con, $_REQUEST['userName']);
    }
    if ($valid) {
        echo "<p>An email has been sent to you with a link to reset your password.</p>";
    }
    else {
        echo "<p>Your account does not exist, or could not be re-activated.</p>";
    }
}
else {
    showResetForm();
}

include "templates/footer.php";


function showResetForm()
{
    $select = '';
    echo "<div class=\"loginForm\">";
    echo "<p>You forgot your password, to re-activate your account:<ul>";
    echo "<li>select the database that contains your account for which you have forgotten your password;</li>";
    echo "<li>enter the user name of your account;</li>";
    echo "<li>press the <em>Reset my password</em> button.</li></ul></p>";
    echo "<p>When your user name is present in the selected database you will receive an email to re-activate your account.</p>";
    echo "<form method=\"post\">";
    echo "<table>";
    echo "<tr><td>Database</td>";
    echo "<td><select id=\"userDB\" name=\"userDB\">";
    foreach (DBASES as $id => $params) {
        echo "<option value=\"" . $id . "\"";
        if ($id == $select) echo " selected";
        echo ">" . $id . "</option>";
    }
    echo "</td></tr>";
    echo "<tr><td>Name</td>";
    echo "<td><input type=\"text\" id=\"userName\" name=\"userName\" autofocus=\"autofocus\" size=\"20\" /></td></tr>";
    echo "</table>";
    echo "<input type=\"submit\" id=\"confirm\" value=\"Reset my password\"/>";
    echo "</form>";
}


function resetUser($con, $name)
{
    global $EMAIL_FROM, $EMAIL_GREETINGS, $EMAIL_SUBJECT;

    $valid = false;
    $query = "SELECT * FROM users WHERE name='" . $name ."'";
    if ($result = mysqli_query($con, $query)) {
        if ($row = mysqli_fetch_array($result)) {
            $email = $row['email'];
            $user = $row['user'];
            $fullName = $row['fullName'];
            $salt = random_bytes(20);
            $pass = random_bytes(20);
            $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
            $query = "UPDATE users SET pass='" . $hashedPass . "', salt='" . $salt . "' WHERE user=" . $user;
            if ($result = mysqli_query($con, $query)) {
                $headers = "From: " . $EMAIL_FROM;

                $message = "Dear " . $fullName . ",\n\n";
                $message .= "you can re-activate your account by clicking the following link\n";
                $message .= "https://" . $_SERVER['SERVER_NAME'] . "/newuser.php?dbase=" . $_SESSION['dbase'] . "&name=" . $name . "&fp=" . bin2hex($hashedPass) . "\n\n\n";
                $message .= $EMAIL_GREETINGS;
                $subject = $EMAIL_SUBJECT;
                if (mail($email, $subject, $message, $headers)) {
                    $valid = true;
                }
                else {
                    echo "Failed to send email<br/>";
                    echo $headers . "<br/>Subject: " . $subject . "<br/>" . $message . "<br/>";
                }
            }
        }
    }
    return $valid;
}

?>
