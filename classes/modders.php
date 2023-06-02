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

class Modders extends Table {
    
    function __construct($con)
    {
        $this->con = $con;
        $this->name = "modders";
    }

    function create()
    {
        $valid = true;
        $query = "CREATE TABLE `modders` (
                    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    `class` int(11) NOT NULL,
                    `modifier` int(11) NOT NULL
                    )";
        if (mysqli_query($this->con, $query)) {
            $query = "ALTER TABLE `modders`
                        ADD KEY `class` (`class`),
                        ADD KEY `modifier` (`modifier`)";
            if (!mysqli_query($this->con, $query)) {
                echo "TABLE modders: " . mysqli_error($this->con);
                $valid = false;
            }
        }
        else {
            echo "TABLE modders: " . mysqli_error($this->con);
            $valid = false;
        }
        return $valid;
    }


    function insert($class, $modifier)
    {
        $query = $this->con->prepare("INSERT INTO modders (class, modifier) VALUES (?, ?)");
        $query->bind_param("ii", $class, $modifier);
        $query->execute();
        $query->close();
    }


    function insertWhenNew($class, $modifier)
    {
        $query = $this->con->prepare("SELECT * FROM modders WHERE class = ? AND modifier = ?");
        $query->bind_param("ii", $class, $modifier);
        if ($query->execute() and ($query->num_rows > 0)) {
            // ignore
        }
        else {
            $this->insert($class, $modifier);
        }
        $query->close();
    }


    function delete2($class, $modifier)
    {
        $query = $this->con->prepare("SELECT * FROM modders WHERE class = ? AND modifier = ?");
        $query->bind_param("ii", $class, $modifier);
        if ($query->execute() and ($query->num_rows > 0)) {
            parent::delete($row['id']);
        }
        $query->close();
    }


    function select($class)
    {
        $modders = array();
        $query = $this->con->prepare("SELECT * FROM modders WHERE class = ?");
        $query->bind_param("i", $class);
        if ($query->execute()) {
            $modders = $query->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $query->close();
        return $modders;
    }


    function getModded($modifier)
    {
        $modded = array();
        $query = $this->con->prepare("SELECT * FROM modders WHERE modifier = ?");
        $query->bind_param("i", $modifier);
        if ($query->execute()) {
            $modded = $query->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $query->close();
        return $modded;
    }   
}


?>
