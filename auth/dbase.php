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

require_once 'config.php';

/**
* connect and open database
*
* @return bool true when connected to database
*/
function connectToDatabase($id, &$con)
{
    $valid = false;
    $con = mysqli_connect(DB_HOST,DBASES[$id][DB_USER],DBASES[$id][DB_PASSWORD]);
    if ($con) {
        mysqli_set_charset($con, 'utf8');
        if (mysqli_select_db($con, DBASES[$id][DB_NAME])) {
            $valid = true;
        }
        else {
            die('Could not select database: ' . mysqli_error($con));
        }
    }
    else {
        die('Could not connect, probably invalid user:' . mysqli_connect_error());
    }
    return $valid;
}


?>
