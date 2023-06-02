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
require_once "auth/user.php";
require_once "templates/menu.php";

if (isAuthenticated($con, $user, $name, $role) and (($role == "writer") or ($role == "editor") or ($role == "admin"))) {
    showMenu($user, $role);
    $db = new Database($con);
    showAllowedKinds($db);
    include "templates/editor.html";
} 
else {
    header('Location: ./index.php');
}

include "templates/footer.php";


function showAllowedKinds($db)
{
    echo "<div id=\"allowedForRoot\" class=\"editorCommands\"><ul>";
    echo "<li id=\"meta_3\">_addSubclass</li>";
    echo "<li id=\"meta_4\">_deleteSubclass</li>";
    echo "</ul></div>";
    
    echo "<div id=\"allowedForAll\" class=\"editorCommands\"><ul>";
    echo "<li id=\"meta_1\">_code</li>";
    echo "<li id=\"meta_8\">_subclass</li>";
    echo "<ul id=\"subCommands\"><li id=\"meta_3\">add</li>";
    echo "<li id=\"meta_4\">delete</li>";
    echo "<li id=\"meta_7\">sort</li></ul>";
    echo "<li id=\"meta_2\">_classkind</li>";
    
    echo "<ul id=\"allowedCKinds\">";
    if ($rows = $db->ckinds->getAll()) {
        foreach ($rows as $row) {
            echo "<li id=\"ckind_" . $row['id'] . "\">" . $row['name'] . "</li>";
        }
    }
    echo "</ul>";

    echo "<li id=\"meta_5\">_modifier</li>";
    echo "<li id=\"meta_6\">_removeModifier</li>";
    echo "<li><hr/></li>";
    if ($rows = $db->rkinds->getAll()) {
        foreach ($rows as $row) {
            echo "<li id=\"rkind_" . $row['id'] . "\">" . $row['name'] . "</li>";
        }
    }
    echo "</ul></div>";
}

?>
