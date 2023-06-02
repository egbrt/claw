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

class Links extends Table {
    
    function __construct($con)
    {
        $this->con = $con;
        $this->name = "links";
    }

    function create()
    {
        $valid = true;
        $query = "CREATE TABLE `links` (
                    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    `tail` int(11) NOT NULL,
                    `head` int(11) NOT NULL
                    )";
        if (mysqli_query($this->con, $query)) {
            $query = "ALTER TABLE `links`
                        ADD KEY `tail` (`tail`),
                        ADD KEY `head` (`head`)";
            if (!mysqli_query($this->con, $query)) {
                echo "TABLE links: " . mysqli_error($this->con);
                $valid = false;
            }
        }
        else {
            echo "TABLE links: " . mysqli_error($this->con);
            $valid = false;
        }
        return $valid;
    }


    function insert($tail, $head)
    {
        $query = $this->con->prepare("INSERT INTO links (tail, head) VALUES (?, ?)");
        $query->bind_param("ii", $tail, $head);
        $query->execute();
        $query->close();
    }
    
    
    function remove($tail, $head)
    {
        $query = $this->con->prepare("DELETE FROM links WHERE tail = ? AND head = ?");
        $query->bind_param("ii", $tail, $head);
        $query->execute();
        $query->close();
    }


    function selectParents($child)
    {
        $rows = array();
        $query = $this->con->prepare("SELECT * FROM links WHERE tail = ?");
        $query->bind_param("i", $child);
        if ($query->execute()) {
            $rows = $query->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $query->close();
        return $rows;
    }


    function getParent($class)
    {
        $parent = 0;
        $query = $this->con->prepare("SELECT * FROM links WHERE tail = ?");
        $query->bind_param("i", $class);
        if ($query->execute()) {
            if ($row = $query->get_result()->fetch_assoc()) {
                $parent = $row['head'];
            }
        }
        $query->close();
        return $parent;
    }

}

?>
