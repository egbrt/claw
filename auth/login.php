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

require_once "dbase.php";


/**
* startSecureSession
* starts a secure session, must be called at top of page where session is needed
*
* copied from http://www.wikihow.com/Create-a-Secure-Login-Script-in-PHP-and-MySQL
*/
function startSecureSession()
{
    ini_set('session.use_only_cookies', 1); // Forces sessions to only use cookies.
    
/*  here is how to make our own cookie, but we don't need a cookie
    $cookie_options = array (
                'expires' => time() + 60*60*24*30,  // 30 days
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => true,      // we are using https
                'httponly' => true,    // stops javascript from being able to access session id
                'samesite' => 'None'   // None || Lax  || Strict
                );
    setcookie('claw_session_id', '1', $cookie_options);
 */
    // set parameters for PHPSESSID cookie
    session_set_cookie_params(["SameSite" => "Strict"]);
    session_set_cookie_params(["Secure" => "true"]);
    session_set_cookie_params(["HttpOnly" => "true"]);
    session_start(); // Start the php session
}


/**
* showPasswordForm
* shows password form
*/
function showPasswordForm()
{
    $select = '';
    if (isset($_REQUEST['dbase'])) $select = $_REQUEST['dbase'];
    echo "<div class=\"loginForm\">";
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
    echo "<tr><td>Password</td>";
    echo "<td><input type=\"password\" id=\"userPass\" name=\"userPass\" size=\"20\" /></td></tr>";
    echo "</table>";
    echo "<input type=\"submit\" id=\"login\" value=\"Login\"/>";
    echo "</form>";
    
    echo "<hr><p style='color:red'><em>NB: Starting from version 2.10.00 (23-10-2023) password management has changed.<br/>You need to reset your password if this is the first time you login to this new version!</em></p><p>Don't remember your password?<br/> Then click the link below:</p>";
    $server = $_SERVER['SERVER_NAME'];
    if ($server == "localhost") $server .= "/claw";
    echo "<p><a href=\"https://" . $server . "/resetPassword.php\">Reset password</a></p>";

    echo "</div>";
}


/**
* isAuthenticated
* returns true when user is authenticated
*/
function isAuthenticated(&$con, &$user, &$name, &$role)
{
    $valid = false;
    if (array_key_exists("browser", DBASES)) {
        if (connectToDatabase("browser", $con)) {
            $user = "anonymous";
            $role = "anonymous";
            $name = "anonymous";
            $valid = true;
        }
    }
    elseif (isset($_SESSION['authenticated'])) {
        if (isset($_SESSION['dbase']) and connectToDatabase($_SESSION['dbase'], $con)) {
            if (isset($_SESSION['user'])) $user = $_SESSION['user'];
            if (isset($_SESSION['role'])) $role = $_SESSION['role'];
            if (isset($_SESSION['name'])) $name = $_SESSION['name'];
            $valid = true;
        }
    }
    elseif (isset($_POST['userDB']) and connectToDatabase($_POST['userDB'], $con)) {
        $_SESSION['dbase'] = $_POST['userDB'];
        if (isset($_POST['userName']) and isset($_POST['userPass'])) {
            $userName = $_POST['userName'];
            $userPass = $_POST['userPass'];
            $valid = isAuthenticatedUser($con, $userName, $userPass, $user, $name, $role);
        }            
    }
    else {
        //echo "not authenticated<br/>";
    }
    return $valid;
}


function isAuthenticatedUser($con, $userName, $userPass, &$user, &$name, &$role)
{
    $valid = false;
    $query = "SELECT * FROM users WHERE name='" . $userName . "'";
    if ($result = mysqli_query($con, $query)) {
        if ($row = mysqli_fetch_array($result)) {
            if (password_verify($userPass, $row['pass'])) {
                $_SESSION['authenticated'] = true;
                $_SESSION['user'] = $row['user'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['name'] = $row['name'];
                $role = $row['role'];
                $user = $row['user'];
                $name = $row['name'];
                $valid = true;
            }
        }
        mysqli_free_result($result);
    }
    return $valid;
}


function setLastLogin($con, $user)
{
	$query = "UPDATE users SET lastlogin=\"" . date("Y-m-d H:i:s") . "\" WHERE user=" . $user;
	//echo $query;
	mysqli_query($con, $query);
}


/**
* getCredentials
*
*/
function getCredentials(&$name, &$pass, &$email, &$role, &$fullName)
{
	$valid = false;
	if (isset($_POST['userName'])) {
		$name = $_POST['userName'];
		if (($name != "") && (isset($_POST['userPass']))) {
			$pass = $_POST['userPass'];
			if (($pass != "") && (isset($_POST['userEmail']))) {
				$email = $_POST['userEmail'];
				if (($email != "") && (isset($_POST['userRole']))) {
					$role = $_POST['userRole'];
					$valid = ($role != "");
					$fullName = "";
					if ($valid) {
                        if (isset($_POST['fullName'])) $fullName = $_POST['fullName'];
                    }
				}
			}
		}
	}
	return $valid;
}


?>
