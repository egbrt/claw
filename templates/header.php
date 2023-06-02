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

require_once "auth/login.php";
startSecureSession();
require_once "classes/database.php";
?>

<!DOCTYPE HTML>
<html>
<head>
    <?php include "title.html" ?>
    <link type="text/css" rel="stylesheet" href="auth/auth.css" />
    <link type="text/css" rel="stylesheet" href="styles/claw.css" />
    <link type="text/css" rel="stylesheet" href="styles/rubrics.css" />
<?php if ((stripos($_SERVER["PHP_SELF"], "browser.php") > 0) or (array_key_exists("browser", DBASES))) { ?>
    <link type="text/css" rel="stylesheet" href="styles/colors.css" />
<?php } ?>
    <link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon" />
    <script src="scripts/jquery-3.6.0.min.js"></script>
    <script src="scripts/claw.js" type="module"></script>
</head>

<body>

<div class="header">
    <?php
        echo "<h1>" . $BANNER_TITLE . "</h1>";
        include "copyright.html";
        showCurrentClassification();
    ?>
</div>
<div class="main">

<?php
function showCurrentClassification()
{
    if (isAuthenticated($con, $user, $name, $role)) {
        if ($role != "anonymous") {
            echo "<span id=\"currentDBase\">currently opened database: " . $_SESSION['dbase'] . "</span>";
        }
        $db = new Database($con);
        $scheme = $db->getClassificationName();
        $schemeName = explode(',', $scheme)[0];
        echo "<span id=\"currentClassification\">currently loaded classification: " . $scheme . "</span>";
        if ($role != "anonymous") {
            echo "<img class=\"menuButton\" id=\"menuButton\" src=\"images/menu.png\" alt=\"Menu\"/>";
        }
        echo "<script>document.title=\"" . $schemeName . "\"</script>";
    }
}
?>

