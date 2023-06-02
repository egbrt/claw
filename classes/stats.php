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

const STATS_CLASS = 0;
const STATS_RUBRIC = 1;
const STATS_STATS = 100;

class Stats extends Table {

    function __construct($con)
    {
        $this->con = $con;
        $this->name = "stats";
        $this->cache = array();
    }

    function create()
    {
        $valid = true;
        $query = "CREATE TABLE `stats` (
                    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    `about` tinyint(4) DEFAULT NULL,
                    `kind` tinyint(4) DEFAULT NULL,
                    `total` int(32) NOT NULL DEFAULT 0
                )";
        if (mysqli_query($this->con, $query)) {
            $valid = true;
        }
        else {
            echo "TABLE stats: " . mysqli_error($this->con);
            $valid = false;
        }
        return $valid;
    }


    function getStats($about)
    {
        $rows = array();
        if ($result = $this->con->query("SELECT * FROM stats WHERE about = " . $about)) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
        }
        return $rows;
    }


}

?>
