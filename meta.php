<?php
/*
    Classification Workbench
    Copyright (c) 2020-2023, WONCA ICPC-3 Foundation

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
require_once "classes/ckinds.php";
require_once "classes/rkinds.php";
require_once "classes/changes.php";

if (isAuthenticated($con, $user, $name, $role) and (($role == "editor") or ($role == "admin"))) {
    showMenu($user, $role);
    $db = new Database($con);
    if (isset($_REQUEST['ckind_button'])) {
        $action = $_REQUEST['ckind_button'];
        if ($action == "add") {
            $db->insertCKind($_REQUEST['ckind_name'], $_REQUEST['ckind_display']);
        }
        elseif ($action == "change") {
            $db->changeCKind($_REQUEST['ckind_id'], $_REQUEST['ckind_name'], $_REQUEST['ckind_display']);
        }
        elseif ($action == "delete") {
            $db->deleteCKind($_REQUEST['ckind_id']);
        }
    }
    elseif (isset($_REQUEST['rkind_button'])) {
        $action = $_REQUEST['rkind_button'];
        if ($action == "add") {
            $db->insertRKind($_REQUEST['rkind_name'], $_REQUEST['rkind_display']);
        }
        elseif ($action == "change") {
            $db->changeRKind($_REQUEST['rkind_id'], $_REQUEST['rkind_name'], $_REQUEST['rkind_display']);
        }
        elseif ($action == "delete") {
            $db->deleteRKind($_REQUEST['rkind_id']);
        }
    }
    showCKinds($db);
    showRKinds($db);
    showEditors();
}
else {
    header('Location: ./index.php');
}

include "templates/footer.php";


function showCKinds($db)
{
    echo "<div id=\"ckinds\" class=\"kinds\"><ul>";
    if ($rows = $db->ckinds->getAll()) {
        foreach ($rows as $row) {
            echo "<li id=\"ckind_" . $row['id'] . "\">" . $row['name'] . ":" . $row['display'] . "</li>";
        }
    }
    echo "</ul></div>";
}


function showRKinds($db)
{
    echo "<div id=\"rkinds\" class=\"kinds\"><ul>";
    if ($rows = $db->rkinds->getAll()) {
        foreach ($rows as $row) {
            echo "<li id=\"rkind_" . $row['id'] . "\">";
            echo $row['name'] . ":" . $row['display'] . "</li>";
        }
    }
    echo "</ul></div>";
}

function showEditors()
{
    echo "<div id=\"editorOfCKinds\" class=\"kinds\">";
    echo "<form method=\"post\">";
    echo "<table>";
    echo "<tr style=\"display:none\"><td>id:</td><td><input type=\"text\" id=\"ckind_id\" name=\"ckind_id\"></td></tr>";
    echo "<tr><td>Name:</td><td><input type=\"text\" id=\"ckind_name\" name=\"ckind_name\"></td></tr>";
    echo "<tr><td>Display:</td><td><input type=\"text\" id=\"ckind_display\" name=\"ckind_display\"></td></tr>";
    echo "</table>";
    echo "<button type=\"submit\" name=\"ckind_button\" id=\"ckind_add\" value=\"add\" disabled>Add</button>";
    echo "<button type=\"submit\" name=\"ckind_button\" id=\"ckind_change\" value=\"change\" disabled>Change</button>";
    echo "<button type=\"submit\" name=\"ckind_button\" id=\"ckind_delete\" value=\"delete\" disabled>Delete</button>";
    echo "</form></div>";

    echo "<div id=\"editorOfRKinds\" class=\"kinds\">";
    echo "<form method=\"post\">";
    echo "<table>";
    echo "<tr style=\"display:none\"><td>id:</td><td><input type=\"text\" id=\"rkind_id\" name=\"rkind_id\"></td></tr>";
    echo "<tr><td>Name:</td><td><input type=\"text\" id=\"rkind_name\" name=\"rkind_name\"></td></tr>";
    echo "<tr><td>Display:</td><td><input type=\"text\" id=\"rkind_display\" name=\"rkind_display\"></td></tr>";
    echo "</table>";
    echo "<button type=\"submit\" name=\"rkind_button\" id=\"rkind_add\" value=\"add\" disabled>Add</button>";
    echo "<button type=\"submit\" name=\"rkind_button\" id=\"rkind_change\" value=\"change\" disabled>Change</button>";
    echo "<button type=\"submit\" name=\"rkind_button\" id=\"rkind_delete\" value=\"delete\" disabled>Delete</button>";
    echo "<p>Note: Start <em>Name</em> with . to create hidden rubrics.</p>";
    echo "</form></div>";
}
?>


