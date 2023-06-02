<?php
/*
    Classification Workbench
    Copyright (c) 2020-2023, WONCA ICPC-3 Foundation

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

class Comments extends Table {
    function __construct($con)
    {
        $this->con = $con;
        $this->name = "comments";
        if (!parent::exists()) {
            $this->create();
        }
    }

    function create()
    {
        $valid = true;
        $query = "CREATE TABLE `comments` (
                    `class` int(11) NOT NULL PRIMARY KEY,
                    `text` mediumtext NOT NULL
                )";
        if (!mysqli_query($this->con, $query)) {
            echo "TABLE comments: " . mysqli_error($this->con);
            $valid = false;
        }
        return $valid;
    }
    
    function get($class)
    {
        $comment = false;
        if ($class == 0) { // i.e. root
            $comment = $this->getAll();
        }
        else {
            $query = $this->con->prepare("SELECT * FROM comments WHERE class = ?");
            $query->bind_param("i", $class);
            if ($query->execute()) {
                if ($row = $query->get_result()->fetch_assoc()) {
                    $comment = $row['text'];
                }
            }
            $query->close();
        }
        return $comment;
    }
    
    function put($class, $text)
    {
        if ($text == "") {
            $this->con->query("DELETE FROM comments WHERE class = " . $class);
        }
        else {
            if ($this->get($class)) {
                $query = $this->con->prepare("UPDATE comments SET text = ? WHERE class = ?");
                $query->bind_param("si", $text, $class);
            }
            else {
                $query = $this->con->prepare("INSERT INTO comments (class, text) VALUES(?, ?)");
                $query->bind_param("is", $class, $text);
            }
            $query->execute();
            $query->close();
        }
    }
    
    function getAll()
    {
        $comments = array();
        $query = $this->con->prepare("SELECT classes.id, classes.code, comments.text FROM comments INNER JOIN classes ON classes.id=comments.class");
        if ($query->execute()) {
            if ($result = $query->get_result()) {
                $comments = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
        $query->close();
        return $comments;
    }
}

?>
