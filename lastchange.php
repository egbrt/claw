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
require_once "auth/user.php";
require_once "classes/changes.php";

class CHANGED {
    public $time;
    public $user;
    public $where;
    public $what;
}


class INFO {
    public $currentCode;
    public $changes;
}

header('Content-Type: application/json; charset=utf-8');
$info = NEW INFO();
$info->changes = array();
if (isAuthenticated($con, $user, $name, $role)) {
    if (isset($_SESSION['currentCode'])) {
        $info->currentCode = $_SESSION['currentCode'];
    }
    
    $changes = new Changes($con);
    if ($rows = $changes->selectLast()) {
        $i = 0;
        foreach ($rows as $row) {
            $info->changes[$i] = new CHANGED();
            $info->changes[$i]->time = $row['time'];
            $info->changes[$i]->user = getShortUserName($con, $row['user']);
            $info->changes[$i]->where = $row['class'];
            $info->changes[$i]->what = $row['what'];
            $i++;
        }
    }
    mysqli_close($con);
}
echo json_encode($info);

?>

