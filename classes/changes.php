<?php
/*
    Classification Workbench
    Copyright (c) 2020-2022, WONCA ICPC-3 Foundation

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

require_once "classes/table.php";
require_once "classes/record.php";

class Changes extends Table {
    
    function __construct($con)
    {
        $this->con = $con;
        $this->name = "changes";
    }

    function create()
    {
        $valid = true;
        $query = "CREATE TABLE `changes` (
                    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `user` int(11),
                    `class` varchar(40) NOT NULL COLLATE latin1_general_cs,
                    `what` varchar(200),
                    `oldText` mediumtext,
                    `newText` mediumtext
                )";
        if (!mysqli_query($this->con, $query)) {
            echo "TABLE changes: " . mysqli_error($this->con);
            $valid = false;
        }
        return $valid;
    }

    
    function write($class, $what, $old, $new)
    {
        if ($id = $this->isReversed($class, $what, $old, $new)) {
            $query = $this->con->query("DELETE FROM changes WHERE id = " . $id);
        }
        else {
            $user = 0;
            if (isset($_SESSION['user'])) $user = $_SESSION['user'];
            $query = $this->con->prepare("INSERT INTO changes (user, class, what, oldText, newText) VALUES(?, ?, ?, ?, ?)");
            $query->bind_param("issss", $user, $class, $what, $old, $new);
            $query->execute();
            $query->close();
        }
    }
    
    
    private function isReversed($class, $what, $old, $new)
    {
        $reversed = false;
        $parts = explode(" ", $what);
        if ($parts[0] == "New") {
            $what = $parts[1] . " " . "deleted";
        }
        elseif ($parts[1] == "deleted") {
            $what = "New " . $parts[0];
        }
        elseif ($parts[1] == "changed") {
            // $what stays the same
        }
        else {
            $what = "";
        }
        if ($what != "") {        
            $query = $this->con->prepare("SELECT id FROM changes WHERE what = ? AND class = ? AND oldText = ? AND newText = ? ORDER BY id DESC");
            $query->bind_param("ssss", $what, $class, $new, $old);
            if ($query->execute()) {
                if ($row = $query->get_result()->fetch_assoc()) {
                    $reversed = $row['id'];
                }
            }
        }
        return $reversed;
    }


    function selectLast()
    {
        $changes = array();
        if ($result = $this->con->query("SELECT SUBTIME(CURRENT_TIMESTAMP, \"0:10:00\")")) {
            if ($row = $result->fetch_row()) {
                $time = $row[0];
                if ($result2 = $this->con->query("SELECT * FROM changes WHERE time >= \"" . $time . "\" ORDER BY id DESC")) {
                    $changes = $result2->fetch_all(MYSQLI_ASSOC);
                }
            }
            $result->free_result();
        }
        return $changes;
    }


    function selectReverseOrder()
    {
        $changes = array();
        if ($result = $this->con->query("SELECT * FROM changes ORDER BY id DESC")) {
            $changes = $result->fetch_all(MYSQLI_ASSOC);
        }
        return $changes;
    }


    function selectInOrder()
    {
        $changes = array();
        if ($result = $this->con->query("SELECT * FROM changes ORDER BY id ASC")) {
            $changes = $result->fetch_all(MYSQLI_ASSOC);
        }
        return $changes;
    }


    function selectByCode()
    {
        $changes = array();
        if ($result = $this->con->query("SELECT * FROM changes ORDER BY class ASC, id, what")) {
            $changes = $result->fetch_all(MYSQLI_ASSOC);
        }
        return $changes;
    }


    function getNumber()
    {
        $count = 0;
        if ($result = $this->con->query("SELECT COUNT(id) FROM changes")) {
            if ($row = $result->fetch_row()) {
                $count = $row[0];
            }
            $result->free_result();
        }
        return $count;
    }
}
    
?>
