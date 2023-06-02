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

class Nodes extends Table {
    private $cache;
    
    function __construct($con)
    {
        $this->con = $con;
        $this->name = "classes";
        $this->cache = array();
    }

    function create()
    {
        $valid = true;
        $query = "CREATE TABLE `classes` (
                    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    `kind` tinyint(4) DEFAULT NULL,
                    `hasParent` tinyint(1) DEFAULT NULL,
                    `hasChildren` tinyint(1) NOT NULL DEFAULT 0,
                    `isModified` tinyint(1) NOT NULL DEFAULT 0,
                    `code` varchar(40) NOT NULL COLLATE latin1_general_cs
                )";
        if (mysqli_query($this->con, $query)) {
            $query = "ALTER TABLE `classes`
                        ADD KEY `code` (`code`)";
            if (!mysqli_query($this->con, $query)) {
                echo "TABLE classes: " . mysqli_error($this->con);
                $valid = false;
            }
        }
        else {
            echo "TABLE classes: " . mysqli_error($this->con);
            $valid = false;
        }
        return $valid;
    }

    function insert($kind, $code)
    {
        $id = false;
        if ($code != '') {
            $query = $this->con->prepare("INSERT INTO classes (kind, code) VALUES (?, ?)");
            $query->bind_param("is", $kind, $code);
            if ($query->execute()) {
                $id = $query->insert_id;
            }
            $query->close();
        }
        return $id;
    }

    // only called during import, finds or adds class
    function import($kind, $code)
    {
        $id = false;
        if ($node = $this->find($code)) {
            $id = $node->id;
            $query = $this->con->prepare("UPDATE classes SET kind = ? WHERE id = ?");
            $query->bind_param("ii", $kind, $id);
            $query->execute();
            $query->close();
        }
        else {
            $query = $this->con->prepare("INSERT INTO classes (kind, code) VALUES (?, ?)");
            $query->bind_param("is", $kind, $code);
            if ($query->execute()) {
                $id = $query->insert_id;
            }
        }
        return $id;
    }

    function changeCode($id, $code)
    {
        $query = $this->con->prepare("UPDATE classes SET code = ? WHERE id = ?");
        $query->bind_param("si", $code, $id);
        $query->execute();
        $query->close();
    }

    function changeKind($id, $ckind)
    {
        $query = $this->con->prepare("UPDATE classes SET kind = ? WHERE id = ?");
        $query->bind_param("ii", $ckind, $id);
        $query->execute();
        $query->close();
    }

    function selectClassWithCode($code)
    {
        $nodes = array();
        $query = $this->con->prepare("SELECT * FROM classes WHERE code= ?");
        $query->bind_param("s", $code);
        if ($query->execute()) {
            if ($result = $query->get_result()) {
                $nodes = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
        $query->close();
        return $nodes;
    }


    function selectRootClasses()
    {
        $nodes = array();
        $query = $this->con->prepare("SELECT * FROM classes WHERE hasParent IS NULL");
        if ($query->execute()) {
            if ($result = $query->get_result()) {
                $nodes = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
        $query->close();
        return $nodes;
    }


    function selectParents($class)
    {
        $nodes = false;
        $query = $this->con->prepare("SELECT classes.* FROM links INNER JOIN classes ON classes.id = links.head WHERE links.tail = ?");
        $query->bind_param("i", $class);
        if ($query->execute()) {
            if ($result = $query->get_result()) {
                $nodes = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
        $query->close();
        return $nodes;
    }
    
    
    function selectChildren($parent)
    {
        $nodes = false;
        $query = $this->con->prepare("SELECT classes.* FROM links INNER JOIN classes ON classes.id = links.tail WHERE links.head = ?");
        $query->bind_param("i", $parent);
        if ($query->execute()) {
            if ($result = $query->get_result()) {
                $nodes = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
        $query->close();
        return $nodes;
    }
    
    
    function sortChildren($parent)
    {
        if ($result = $this->con->query("SELECT links.id AS linkId, classes.id AS classId FROM links INNER JOIN classes ON classes.id = links.tail WHERE links.head = " . $parent . " ORDER BY classes.code")) {
            $links = array();
            $classes = array();
            while ($row = $result->fetch_assoc()) {
                $classes[] = $row['classId'];
                $links[] = $row['linkId'];
            }
            sort($links);

            $i = 0;
            $query = $this->con->prepare("UPDATE links SET tail = ? WHERE id = ?");
            $query->bind_param("ii", $child, $link);
            while ($i < count($links)) {
                $child = $classes[$i];
                $link = $links[$i];
                $query->execute();
                $i++;
            }
            $query->close();
        }
    }

    
    function get($id)
    {
        $node = false;
        if ($row = parent::get($id)) {
            $node = $this->row2node($row);
        }
        return $node;
    }
    

    function find($code)
    {
        $node = false;
        if (array_key_exists($code, $this->cache)) {
            $node = $this->cache[$code];
        }
        else {
            $query = $this->con->prepare("SELECT * FROM classes WHERE code = ?");
            $query->bind_param("s", $code);
            if ($query->execute()) {
                if ($row = $query->get_result()->fetch_assoc()) {
                    $node = $this->row2node($row);
                }
            }
            $query->close();
        }
        return $node;
    }

    function getKind($id, &$name)
    {
        $kind = '';
        $name = '';
        $query = $this->con->prepare("SELECT classes.kind, ckinds.name FROM classes INNER JOIN ckinds ON ckinds.id = classes.kind WHERE classes.id = ?");
        $query->bind_param("i", $id);
        if ($query->execute()) {
            if ($row = $query->get_result()->fetch_assoc()) {
                $kind = $row['kind'];
                $name = $row['name'];
            }
        }
        $query->close();
        return $kind;
    }

    function setHasChildren($id, $value=true)
    {
        $this->con->query("UPDATE classes SET hasChildren=" . $value . " WHERE id=" . $id);
    }


    function setHasParent($id, $value=true)
    {
        $this->con->query("UPDATE classes SET hasParent=" . $value . " WHERE id=" . $id);
    }

    function countClassesOfKind($kind)
    {
        $count = 0;
        if ($result = $this->con->query("SELECT * FROM classes WHERE kind=" . $kind)) {
            $count = $result->num_rows;
            $result->free_result();
        }
        return $count;
    }
    
    private function row2node($row)
    {
        $node = new Node();
        $node->id = $row['id'];
        $node->kind = $row['kind'];
        $node->hasParent = $row['hasParent'];
        $node->hasChildren = $row['hasChildren'];;
        $node->isModified = $row['isModified'];
        $node->code = $row['code'];
        $this->cache[$node->code] = $node;
        return $node;
    }
}


class Node {
    public $id;
    public $kind;
    public $hasParent;
    public $hasChildren;
    public $isModified;
    public $code;
}

?>
