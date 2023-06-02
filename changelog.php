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
require_once "classes/debugger.php";

const CODE = 2;
const WHAT = 3;
const OLDTEXT = 4;
const NEWTEXT = 5;

if (isAuthenticated($con, $user, $name, $role) and (($role == "writer") or ($role == "editor") or ($role == "admin"))) {
    showMenu($user, $role);
    showImport();
    $selectedUser = 0;
    if (isset($_REQUEST['selectUser'])) {
        $selectedUser = $_REQUEST['selectUser'];
    }
    $db = new Database($con);
    showChanges($db, $selectedUser);
    showUsers($db, $selectedUser);

    if (isset($_REQUEST['action'])) {
        $action = $_REQUEST['action'];
        if ($action == "Import") {
            if (uploadChangeLog($file, $error)) {
                importChangeLog($db, $file);
                showChanges($db, $selectedUser);
            }
            else {
                echo $error;
            }
        }
        elseif ($action == "Clean") {
            cleanChangeLog($con);
            showChanges($db, $selectedUser);
        }
    }
}
else {
    header('Location: ./index.php');
}

include "templates/footer.php";


function showChanges($db, $selectedUser)
{
    echo "<div id=\"changeLog\">";
    if ($rows = $db->changes->selectReverseOrder()) {
        echo "<ul>";
        foreach ($rows as $row) {
            if (($selectedUser == 0) or ($row['user'] == $selectedUser)) {
                if ($row['what'] == "New class") {
                    $class = 'newclass';
                }
                elseif (substr($row['what'], 0, 3) == "New") {
                    $class = 'newrubric';
                }
                elseif (strpos($row['what'], "changed")) {
                    $class = 'changed';
                }
                elseif (strpos($row['what'], "deleted")) {
                    $class = 'deleted';
                }
                elseif ($row['class'] == "global") {
                    $class = 'processed';
                }
                else {
                    $class = 'default';
                }
                echo "<li class=\"" . $class . "\">";
                echo 'At ' . $row['time'] . ' by ';
                echo getShortUserName($db->con, $row['user']);
                if ($row['class'] != '') {
                    echo ' at ' . $row['class'];
                }
                echo ': ' . $row['what'];
                if ($row['oldText'] != '') echo ': ' . htmlspecialchars($row['oldText']);
                if ($row['newText'] != '') echo ' -> ' . htmlspecialchars($row['newText']);
                echo '</li>';
            }
        }
        echo "</ul>";
    }
    echo "</div>";
}


function showUsers($db, $selectedUser)
{
    $query = "SELECT * FROM users";
    if ($result = mysqli_query($db->con, $query)) {
        echo "<div id=\"changeLogUsers\">";
        echo "<form method=\"post\">";
        echo "<button type='submit' name='selectUser' value='0'";
        if ($selectedUser == 0) echo ' selected';
        echo ">All users</button>";
        while ($row = mysqli_fetch_array($result)) {
            echo "<button type='submit' name='selectUser' value='" . $row['user'] . "'";
            if ($row['user'] == $selectedUser) echo ' selected';
            echo ">" . $row['name'] . '</button>';
        }
        echo "</form>";
        echo "</div>";
        mysqli_free_result($result);
    }
}


function showImport()
{
    echo "<div id='importChangeLog'>";
    echo "<h3>Import existing changelog</h3>";
    echo "<p>This imports an existing changelog and applies all changes in the imported changelog ";
    echo "to the currently loaded database.</p>";
    echo "<p>NOTA BENE: Only use this if you know what you're doing.</p>";
    echo "<form method='post' enctype='multipart/form-data'>";
    echo "<p><input type='file' name='fileToUpload' id='fileToUpload'></p>";
    echo "<input id='buttonImport' type='submit' name='action' value='Import' disabled>";
    echo "<input id='buttonClean' type='submit' name='action' value='Clean'>";
    echo "</form></div>";

    echo "<script>";
    echo "$('#fileToUpload').change(function() {";
    echo "$('#buttonImport').attr('disabled', ($('#fileToUpload').val() == ''))";
    echo "});";
    echo "$('buttonImport').click(function() {";
    echo "$('#buttonImport').attr('disabled', true)";
    echo "});";
    echo "</script>";
    
}


function uploadChangeLog(&$file, &$error)
{
    $file = '';
    $error = '';
    $valid = false;
    if (basename($_FILES["fileToUpload"]["name"])) {
        $dir = "uploads/";
        $file = $dir . basename($_FILES["fileToUpload"]["name"]);
        $type = strtolower(pathinfo($file,PATHINFO_EXTENSION));
        if (file_exists($file)) {
            unlink($file);
        }
        if (!file_exists($file)) {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $file)) {
                $valid = true;
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
        }
        else {
            $error = "file already exists, and could not be deleted.";
        }
    }
    else {
        $error = "Please choose a file first.";
    }
    return $valid;
}


function importChangeLog($db, $file)
{
    $data = fopen($file,"r");
    if ($data) {
        $debugger = new Debugger();
        while ($line = fgets($data)) { // erg moeizaam, maar er zit een bug in fgetcsv mbt xml tags
            $line = str_replace('","', "\t", $line);
            $line = trim($line, "\"\n");
            $word = explode("\t", $line);
            $change = $word[WHAT];
            $code = $word[CODE];
            $oldText = $word[OLDTEXT];
            $newText = $word[NEWTEXT];
            if ($change == "Language changed") {
                $db->globalChangeLanguage($oldText, $newText);
            }
            elseif ($change == "Word translated") {
                $db->globalChangeWord($oldText, $newText);
            }
            elseif (($parts = explode(" ", $change)) and (count($parts) > 1) and ($class = $db->nodes->find($code))) {
                if ($parts[0] == "New") {
                    if ($rkind = $db->rkinds->getId($parts[1])) {
                        $new = explode(':', $newText, 2);
                        $db->insertRubric($class->id, $rkind, $new[0], $new[1]); 
                    }
                }
                elseif ($parts[1] == "deleted") {
                    $old = explode(':', $oldText, 2);
                    if ($id = $db->rubrics->findAt($class->id, $old[0], $old[1])) {
                        $db->deleteRubric($id);
                    }
                    else {
                        $debugger->write("NOT FOUND! deleteRubric");
                        $debugger->write($line);
                    }
                }
                elseif ($parts[1] == "changed") {
                    $old = explode(':', $oldText, 2);
                    $new = explode(':', $newText, 2);
                    if ($id = $db->rubrics->findAt($class->id, $old[0], $old[1])) {
                        $db->changeRubric($id, $new[0], $new[1]);
                    }
                    else {
                        $debugger->write("NOT FOUND: changeRubric");
                        $debugger->write($line);
                        $debugger->write($old[0] . ':' . $old[1]);
                    }
                }
            }
        }
        fclose($data);
    }
}


function cleanChangeLog($con)
{
    if ($result = $con->query("SELECT * FROM changes WHERE what LIKE \"New %\" ORDER BY id DESC")) {
        $debugger = new Debugger();
        while ($row = $result->fetch_assoc()) {
            if ($reversed = isReversed($con, $row['class'], $row['what'], $row['oldText'], $row['newText'])) {
                /*
                $parts = explode(" ", $row['what']);
                if ($parts[0] == "New") {
                    $what = $parts[1] . " " . "deleted";
                }
                elseif ($parts[1] == "deleted") {
                    $what = "New " . $parts[0];
                }
                elseif ($parts[1] == "changed") {
                    $what = $row['what'];
                }
                $debugger->write($row['class'] . "," . $row['what'] . ',' . $row['oldText'] . ',' . $row['newText']);
                $debugger->write($row['class'] . "," . $what . ',' . $row['newText'] . ',' . $row['oldText']);
                */
                $con->query("DELETE FROM changes WHERE id = " . $reversed);
                $con->query("DELETE FROM changes WHERE id = " . $row['id']);
            }
        }
    }
}


function isReversed($con, $class, $what, $old, $new)
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
        $query = $con->prepare("SELECT id FROM changes WHERE what = ? AND class = ? AND oldText = ? AND newText = ? ORDER BY id DESC");
        $query->bind_param("ssss", $what, $class, $new, $old);
        if ($query->execute()) {
            if ($row = $query->get_result()->fetch_assoc()) {
                $reversed = $row['id'];
            }
        }
    }
    return $reversed;
}



?>
