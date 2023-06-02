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

include "templates/header.php";
require_once "auth/user.php";
require_once "templates/menu.php";


if (isAuthenticated($con, $user, $name, $role) and (($role == "writer") or ($role == "editor") or ($role == "admin"))) {
    showMenu($user, $role);
    $db = new Database($con);
    showStatistics($db);
    if (isset($_REQUEST['submit'])) {
        $action = $_REQUEST['submit'];
        if ($action == "Export") {
            $file = "./downloads/statistics.txt";
            if (file_exists($file)) unlink($file);
            createStatistics($db, $file);

            echo "<div id='exportStatResults'>";
            if (file_exists($file)) {
                echo "<p>Click the file name to download:<br/>";
                echo "<a href=\"" . $file ."\" download>" . basename($file) . "</a></p>";
            }
            else {
                echo "<p>There is no file created.</p>";
            }
            echo "</div>";
        }
    }
    else {
        showExportOption();
    }
}
else {
    header('Location: ./index.php');
}

include "templates/footer.php";

function showStatistics($db)
{
    $db->updateStats();
    if ($rows = $db->stats->getStats(STATS_CLASS)) {
        echo "<div id=\"ckinds\" class=\"kinds statistics\">";
        echo "<table>";
        foreach ($rows as $row) {
            echo "<tr><td>";
            echo $row['total'];
            echo "</td><td>x</td>";
            echo "<td>" . $db->ckinds->getName($row['kind']);
            echo "</td></tr>";
        }
        echo "</table></div>";
    }    
    if ($rows = $db->stats->getStats(STATS_RUBRIC)) {
        echo "<div id=\"rkinds\" class=\"kinds statistics\">";
        echo "<table>";
        foreach ($rows as $row) {
            $rkind = $db->rkinds->getName($row['kind']);
            echo "<tr><td>";
            echo $row['total'];
            echo "</td><td>x</td>";
            echo "<td>" . $rkind;
            echo "</td></tr>";
        }
        echo "</table></div>";
    }    
}


function showExportOption()
{
    echo "<div id='statsExport'>";
    echo "<p>Export more statistics information.</p>";
    echo "<form method='post'>";
    echo "<input type='submit' value='Export' name='submit'>";
    echo "</form>"; 
    echo "</div>";
}


function createStatistics($db, $filename)
{
    $file = fopen($filename, 'w');
    if ($file) {
        if ($rows = $db->stats->getStats(STATS_CLASS)) {
            fwrite($file, "Number of class kinds in classification \"" . $db->getClassificationName() . "\".\n");
            foreach ($rows as $row) {
                fwrite($file, "\t". $row['total'] . " x " . $db->ckinds->getName($row['kind']) . "\n");
            }
            fwrite($file, "\n");
        }
        if ($rows = $db->stats->getStats(STATS_RUBRIC)) {
            fwrite($file, "Number of rubric kinds in classification.\n");
            foreach ($rows as $row) {
                fwrite($file, "\t". $row['total'] . " x " .$db->rkinds->getName($row['kind']) . "\n");
            }
            fwrite($file, "\n");
        }
        fwrite($file, "More specific statistics about class kinds.\n\n");
        writeStatsOfClass($db, $file, 1, $db->getTopCategory());
        fclose($file);
    }
}


function writeStatsOfClass($db, $file, $depth, $class)
{
    if ($rows = $db->nodes->selectChildren($class)) {
        $ckinds = [];
        $hasChildren = false;
        foreach ($rows as $row) {
            $ckinds[] = $row['kind'];
            $hasChildren = true;
        }

        if ($hasChildren) {
            $counted = array_count_values($ckinds);
            fwrite($file, $db->nodes->get($class)->code);
            fwrite($file, " \"" . $db->rubrics->getPreferred($class) . "\" contains:\n");
            foreach ($counted as $key => $number) {
                fwrite($file, "\t" . $number . " x " . $db->ckinds->getName($key) . "\n");
            }

            foreach ($rows as $row) {
                writeStatsOfClass($db, $file, $depth+1, $row['id']);
            }
        }
    }
}


?>



