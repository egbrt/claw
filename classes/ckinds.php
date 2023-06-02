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

class CKinds extends Table {
    private $indexedNames;
    private $indexedIds;
    
    function __construct($con)
    {
        $this->con = $con;
        $this->name = "ckinds";
        $this->indexedNames = array();
        $this->indexedIds = array();
    }

    function create()
    {
        $valid = true;
        $query = "CREATE TABLE `ckinds` (
                    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    `name` varchar(80) NOT NULL,
                    `display` varchar(200)
                )";
        if (!mysqli_query($this->con, $query)) {
            echo "TABLE ckinds: " . mysqli_error($this->con);
            $valid = false;
        }
        return $valid;
    }


    function getId($name)
    {
        if (array_key_exists($name, $this->indexedNames)) {
            $id = $this->indexedNames[$name];
        }
        else {
            $id = 0;
            $query = $this->con->prepare("SELECT * FROM ckinds WHERE name = ?");
            $query->bind_param("s", $name);
            if ($query->execute()) {
                if ($row = $query->get_result()->fetch_assoc()) {
                    $id = $row['id'];
                    $this->indexedNames[$name] = $id;
                }
            }
            $query->close();
        }
        return $id;
    }


    function getName($id)
    {
        if (array_key_exists($id, $this->indexedIds)) {
            $name = $this->indexedIds[$id];
        }
        else {
            $name = '';
            $query = $this->con->prepare("SELECT * FROM ckinds WHERE id = ?");
            $query->bind_param("i", $id);
            if ($query->execute()) {
                if ($row = $query->get_result()->fetch_assoc()) {
                    $name = $row['name'];
                    $this->indexedIds[$id] = $name;
                }
                $query->close();
            }
        }
        return $name;
    }


    function getNameAndDisplay($id, &$name, &$display)
    {
        $name = '';
        $display = '';
        $query = $this->con->prepare("SELECT * FROM ckinds WHERE id = ?");
        $query->bind_param("i", $id);
        if ($query->execute()) {
            if ($row = $query->get_result()->fetch_assoc()) {
                $display = $row['display'];
                $name = $row['name'];
            }
        }
        $query->close();
    }


    function insert($name, $display)
    {
        $query = $this->con->prepare("INSERT INTO ckinds (name, display) VALUES (?, ?)");
        $query->bind_param("ss", $name, $display);
        $query->execute();
        $query->close();
    }


    function change($id, $name, $display)
    {
        $query = $this->con->prepare("UPDATE ckinds SET name = ?, display = ? WHERE id = ?");
        $query->bind_param("ssi", $name, $display, $id);
        $query->execute();
        $query->close();
    }

}

?>
