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

require_once "login.php";
?>

<div class="menu">
<?php
if (isAuthenticated($con, $user, $name, $role)) {
    echo "<a href=\"admin.php\">Users</a>&nbsp;&nbsp";
    echo "<a href=\"../upload.php\">Upload ClaML</a>&nbsp;&nbsp";
    echo "<a href=\"logout.php\">Logout</a>&nbsp;&nbsp";
}
?>
</div>
