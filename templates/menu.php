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


function showMenu($user, $role)
{
    echo "<div id=\"menu\" class=\"menu\"><ul>";
    if ($role == "admin") {
        echo "<li><a href=\"auth/admin.php\">Users</a></li>";
        echo "<li><a href=\"claml.php\">ClaML</a></li>";
        echo "<hr/>";
    }
    if (($role == "editor") or ($role == "admin")) {
        echo "<li><a href=\"meta.php\">Meta Model</a></li>";
        echo "<li><a href=\"export.php\">Export</a></li>";
        echo "<hr/>";
    }
    if (($role == "writer") or ($role == "editor") or ($role == "admin")) {
        echo "<li><a href=\"browser.php\">Browser</a></li>";
        echo "<li><a href=\"editor.php\">Editor</a></li>";
        if ($role == "admin") echo "<li><a href=\"translate.php\">Translate</a></li>";
        echo "<li><a href=\"statistics.php\">Statistics</a></li>";
        echo "<li><a href=\"changelog.php\">Changelog</a></li>";
        echo "<hr/>";
    }
    if ($role == "reader") {
        echo "<li><a href=\"browser.php\">Browser</a></li>";
        echo "<hr/>";
    }
    echo "<li><a href=\"settings.php\">Settings</a></li>";
    echo "<li><a href=\"auth/logout.php\">Logout</a></li>";
    echo "<hr/>";
    if ($role != "reader") {
        echo "<li><a href=\"faq.php\">FAQ</a></li>";
    }
    echo "</ul></div>";
}

?>
