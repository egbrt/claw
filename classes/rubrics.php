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


class Rubrics extends Table {
    
    function __construct($con)
    {
        $this->con = $con;
        $this->name = "rubrics";
    }

    function create()
    {
        $valid = true;
        $query = "CREATE TABLE `rubrics` (
                    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    `class` int(11) NOT NULL,
                    `kind` tinyint(4) NOT NULL,
                    `language` varchar(2),
                    `label` mediumtext NOT NULL
                )";
        if (mysqli_query($this->con, $query)) {
            $query = "ALTER TABLE `rubrics`
                        ADD KEY `class` (`class`),
                        ADD KEY `kind` (`kind`)";
            if (!mysqli_query($this->con, $query)) {
                echo "TABLE rubrics: " . mysqli_error($this->con);
                $valid = false;
            }
        }
        else {
            echo "TABLE rubrics: " . mysqli_error($this->con);
            $valid = false;
        }
        return $valid;
    }
    
    
    function find($language, $label)
    {
        $id = false;
        $query = $this->con->prepare("SELECT id FROM rubrics WHERE language = ?  AND label = ?");
        $query->bind_param("ss", $language, $label);
        if ($query->execute()) {
            if ($row = $query->get_result()->fetch_assoc()) {
                $id = $row['id'];
            }
        }
        $query->close();
        return $id;
    }


    function findAt($class, $language, $label)
    {
        $id = false;
        $query = $this->con->prepare("SELECT id FROM rubrics WHERE class = ? AND language = ?  AND label = ?");
        $query->bind_param("iss", $class, $language, $label);
        if ($query->execute()) {
            if ($row = $query->get_result()->fetch_assoc()) {
                $id = $row['id'];
            }
        }
        $query->close();
        return $id;
    }


    function insert($class, $kind, $language, $text)
    {
        $query = $this->con->prepare("INSERT INTO rubrics (class, kind, language, label) VALUES (?, ?, ?, ?)");
        $query->bind_param("iiss", $class, $kind, $language, $text);
        $query->execute();
        $query->close();
    }


    function change($id, $language, $label)
    {
        $query = $this->con->prepare("UPDATE rubrics SET language = ?, label = ? WHERE id = ?");
        $query->bind_param("ssi", $language, $label, $id);
        $query->execute();
        $query->close();
    }


    function getCodeAndLabel($id, &$code, &$kind, &$language, &$label)
    {
        $code = '';
        $kind = '';
        $label = '';
        $language = '';
        $query = $this->con->prepare("SELECT code, rkinds.name, language, label FROM rubrics INNER JOIN classes ON classes.id = rubrics.class INNER JOIN rkinds ON rkinds.id = rubrics.kind WHERE rubrics.id = ?");
        $query->bind_param("i", $id);
        if ($query->execute()) {
            if ($row = $query->get_result()->fetch_assoc()) {
                $language = $row['language'];
                $label = $row['label'];
                $kind = $row['name'];
                $code = $row['code'];
            }
        }
        $query->close();
        return ($code != '');
    }
    
    
    function deleteAllOfKind($class, $kind)
    {
        if ($rows = $this->getKindOfClass($class, $kind)) {
            foreach ($rows as $row) {
                $this->delete($row['id']);
            }
        }
    }


    function deleteAll($class)
    {
        if ($rows = $this->getAllOfClass($class)) {
            foreach ($rows as $row) {
                $this->delete($row['id']);
            }
        }
    }


    function getPreferred($class)
    {
        $rubric = "";
        $query = $this->con->prepare("SELECT label FROM rubrics WHERE kind = " . PREFERRED_ID . " AND class = ?");
        $query->bind_param("i", $class);
        if ($query->execute()) {
            if ($row = $query->get_result()->fetch_assoc()) {
                $rubric = $row['label'];
            }
        }
        $query->close();
        return $rubric;
    }


    function getKindOfClass($class, $kind)
    {
        $rows = array();
        $query = $this->con->prepare("SELECT * FROM rubrics WHERE class = ? AND kind = ?");
        $query->bind_param("ii", $class, $kind);
        if ($query->execute()) {
            $rows = $query->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $query->close();
        return $rows;
    }


    function getAllOfClass($class)
    {
        $rows = array();
        $query = $this->con->prepare("SELECT * FROM rubrics WHERE class = ? ORDER BY kind, id");
        $query->bind_param("i", $class);
        if ($query->execute()) {
            $rows = $query->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $query->close();
        return $rows;
    }


    function selectRubricsContaining($rkind, $word)
    {
        $rows = array();
        if ($rkind >= 0) {
            $query = $this->con->prepare("SELECT * FROM rubrics WHERE kind = ? AND label LIKE ?");
            $query->bind_param("is", $rkind, $fragment);
        }
        else {
            $query = $this->con->prepare("SELECT * FROM rubrics WHERE label LIKE ?");
            $query->bind_param("s", $fragment);
        }
        $fragment = '%' . $word . '%';
        if ($query->execute()) {
            $rows = $query->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $query->close();
        return $rows;
    }


    function countRubricsOfKind($kind)
    {
        $count = 0;
        $query = "SELECT * FROM rubrics WHERE kind=" . $kind;
        if ($result = $this->con->query($query)) {
            $count = $result->num_rows;
            $result->free_result();
        }
        return $count;
    }
}

?>
