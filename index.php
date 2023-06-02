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
    if ($user == "anonymous") {
        $db = new Database($con);
        include "templates/browser.html";
    }
    else {
        setLastLogin($con, $user);
        showMenu($user, $role);
    }
    mysqli_close($con);
}
else {
    showPasswordForm();
    echo "<div id=\"introFeatures\">";
    echo "<p>The Classification Workbench is a free and open source classification editor.</p>";
    echo "<h3>Features</h3>";
    echo "<ul><li>Free software, licensed under <a href=\"https://www.gnu.org/copyleft/gpl.html\" target=\"blank\">GNU GPLv3</a></li>";
    echo "<li>Self-hosted on your own webspace</li>";
    echo "<li>Supports:<ul>";
    echo "<li>multiple databases</li>";
    echo "<li>user right management</li>";
    echo "<li>multiple users per database</li>";
    echo "<li>import and export of classifications in the <a href=\"https://www.iso.org/obp/ui/#iso:std:iso:13120:ed-2:v1:en\" target=\"blank\">ISO 13120:2019 ClaML Standard</a></li></ul></ul>";
    echo "<h3>Third party software</h3>";
    echo "<p>The Classification Workbench uses:<ul>";
    echo "<li><a href=\"https://jquery.com\" target=\"blank\">jQuery</a></li>";
    echo "</ul>";
    echo "<h3>Sponsors</h3>";
    echo "<p>The work was sponsored by: <a href=\"https://icpc-3.info\" target=\"blank\">The WONCA ICPC Foundation</a></p>";
    echo "<h3>Source code</h3>";
    echo "<p><a href=\"https://github.com/egbrt/claw\">Download</a> the source for the Classification Workbench.</p>";
    echo "<p>If you need support installing the source or wish to contribute to the development of the Classification Workbench, send an email to: ";
    echo "<a href=\"mailto:claw@icpc-3.info\">claw at icpc-3.info<a></p>";
    echo "</div>";
    
}

include "templates/footer.php";
?>

