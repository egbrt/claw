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

function getUserName($con, $user)
{
    $name = "unknown";
    $query = "SELECT * FROM users WHERE user=" . $user;
    if ($result = mysqli_query($con, $query)) {
        if ($row = mysqli_fetch_array($result)) {
            if ($row['fullName'] != "") {
                $name = $row['fullName'];
            }
            else {
                $name = $row['name'];
            }
        }
        mysqli_free_result($result);
    }
    return $name;
}

function getShortUserName($con, $user)
{
    $name = "unknown";
    $query = "SELECT * FROM users WHERE user=" . $user;
    if ($result = mysqli_query($con, $query)) {
        if ($row = mysqli_fetch_array($result)) {
            $name = $row['name'];
        }
        mysqli_free_result($result);
    }
    return $name;
}


function validateInput($text) {
  $text = trim($text);
  $text = stripslashes($text);
  $text = htmlspecialchars($text);
  return $text;
}



?>
