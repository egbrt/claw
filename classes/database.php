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

require_once "classes/basicModel.php";
require_once "classes/rubrics.php";
require_once "classes/nodes.php";
require_once "classes/ckinds.php";
require_once "classes/rkinds.php";
require_once "classes/links.php";
require_once "classes/modders.php";
require_once "classes/changes.php";
require_once "classes/stats.php";
require_once "classes/comments.php";

class Database {
    public $con;
    public $nodes;
    public $links;
    public $rubrics;
    public $ckinds;
    public $rkinds;
    public $modders;
    public $changes;
    public $stats;
    public $comments;
    
    function __construct($con)
    {
        $this->con = $con;
        $this->stats = new Stats($con);
        $this->changes = new Changes($con);
        $this->nodes = new Nodes($con);
        $this->rubrics = new Rubrics($con);
        $this->ckinds = new CKinds($con);
        $this->rkinds = new RKinds($con);
        $this->links = new Links($con);
        $this->modders = new Modders($con);
        $this->comments = new Comments($con);
    }
    
    function __destruct()
    {
        $this->con->close();
    }

    private function isEmpty()
    {
        $empty = true;
        $query = "SHOW TABLES LIKE 'classes'";
        if ($result = mysqli_query($this->con, $query)) {
            if ($row = mysqli_fetch_array($result)) {
                $empty = false;
            }
            mysqli_free_result($result);
        }
        return $empty;
    }


    function clearTables()
    {
        $valid = false;
        if (!$this->isEmpty()) {
            $this->dropTables();
        }
        if ($this->createTables()) {
            $valid = true;
        }
        else {
            echo "Old classification could not be cleared from database.";
        }
        return $valid;
    }


    function create()
    {
        echo "drop tables";
        $this->dropTables();
        echo "create tables";
        $this->createTables();
        echo "addBasicMetaModel";
        $this->addBasicMetaModel();
    }


    function setClassificationName($name, $date, $version)
    {
        if ($name) $this->rubrics->insert(0, 0, DEFAULT_LANGUAGE, $name);
        if ($date) $this->rubrics->insert(0, 0, DEFAULT_LANGUAGE, $date);
        if ($version) $this->rubrics->insert(0, 0, DEFAULT_LANGUAGE, $version);
    }


    function getClassificationName()
    {
        if ($this->isEmpty()) {
            $name = "un-initialised";
        }
        else {
            $this->getScheme($title, $date, $version, $subversion, $authors);
            if ($title == "") {
                $name = "none";
            }
            else {
                $name = $title . ", date: " . $date . ", version: " . $version;
            }
        }
        return $name;
    }
    
    
    function getScheme(&$title, &$date, &$version, &$subversion, &$authors)
    {
        $title = '';
        $date = '';
        $version = '';
        $authors = array();
        if ($rows = $this->rubrics->getAllOfClass(0)) {
            $i = 1;
            $author = 0;
            foreach ($rows as $row) {
                if ($i == 1) {
                    $title = $row['label'];
                }
                elseif ($i == 2) {
                    $date = $row['label'];
                }
                elseif ($i == 3) {
                    $version = $row['label'];
                }
                else {
                    $authors[$author] = $row['label'];
                    $author++;
                }
                $i++;
            }
        }
        $subversion = $this->changes->getNumber();
        while (strlen($subversion) < 4) {
            $subversion = '0' . $subversion;
        }
    }
    
    
    function getTopModifier()
    {
        return $this->nodes->find(TOPMODIFIER);
    }


    function getTopCategory()
    {
        return $this->nodes->find(TOPCATEGORY);
    }


    function insertCKind($name, $display)
    {
        $this->ckinds->insert($name, $display);
        $this->changes->write('ClassKind', 'New', '', $name);
    }
    
    
    function changeCKind($id, $name, $display)
    {
        $this->ckinds->getNameAndDisplay($id, $oldName, $oldDisplay);
        $this->ckinds->change($id, $name, $display);
        if ($name != $oldName) if ($this->changes) $this->changes->write('ClassKind', 'name changed', $oldName, $name);
        if ($display != $oldDisplay) if ($this->changes) $this->changes->write('ClassKind', 'display changed', $oldDisplay, $display);
    }


    function deleteCKind($id)
    {
        $name = $this->rkinds->getName($id);
        $this->rkinds->delete($id);
        $this->changes->write('RubricKind', 'deleted', $name, '');
    }


    function insertRKind($name, $display)
    {
        $this->rkinds->insert($name, $display);
        $this->changes->write('RubricKind', 'New', '', $name);
    }
    
    
    function changeRKind($id, $name, $display)
    {
        $this->rkinds->getNameAndDisplay($id, $oldName, $oldDisplay);
        $this->rkinds->change($id, $name, $display);
        if ($name != $oldName) if ($this->changes) $this->changes->write('RubricKind', 'name changed', $oldName, $name);
        if ($display != $oldDisplay) if ($this->changes) $this->changes->write('RubricKind', 'display changed', $oldDisplay, $display);
    }


    function deleteRKind($id)
    {
        $this->con->query("DELETE FROM rubrics WHERE kind = " . $id);
        $name = $this->rkinds->getName($id);
        $this->rkinds->delete($id);
        $this->changes->write('RubricKind', 'deleted', $name, '');
    }

    
    function insertNode($kind, $code)
    {
        if ($id = $this->nodes->insert($kind, $code)) {
            $this->changes->write($code, 'New class', '' , '');
        }
        return $id;
    }
    
    
    function deleteNode($id)
    {
        $node = $this->nodes->get($id);
        $this->nodes->delete($id);
        $this->changes->write($node->code, 'Class deleted', '', '');
    }
    
    
    function changeNodeCode($id, $code)
    {
        $node = $this->nodes->get($id);
        $this->nodes->changeCode($id, $code);
        $this->changes->write($code, 'Code changed', $node->code, $code);
    }

    function changeNodeKind($id, $ckind)
    {
        $node = $this->nodes->get($id);
        $this->nodes->changeKind($id, $ckind);
        $this->changes->write($node->code, 'ClassKind changed', $this->ckinds->getName($node->kind), $this->ckinds->getName($ckind));
    }

    
    function insertRubric($class, $kind, $language, $text)
    {
        $this->rubrics->insert($class, $kind, $language, $text);
        if ($class == 0) { // i.e. root
            $this->changes->write("Root", "New " . $this->rkinds->getName($kind), '', $language . ':' . $text);
        }
        else {
            $node = $this->nodes->get($class);
            $this->changes->write($node->code, "New " . $this->rkinds->getName($kind), '', $language . ':' . $text);
        }
    }


    function changeRubric($id, $language, $label)
    {
        if ($this->rubrics->getCodeAndLabel($id, $code, $kind, $oldLanguage, $oldLabel)) {
            $this->changes->write($code, $kind . " changed", $oldLanguage . ':' . $oldLabel, $language . ':' . $label);
        }
        elseif ($rows = $this->rubrics->get($id)) {
            $this->changes->write('', "changed", $rows['language'] . ':' . $rows['label'], $language . ':' . $label);
        }
        $this->rubrics->change($id, $language, $label);
    }

    
    function deleteRubric($id)
    {
        if ($this->rubrics->getCodeAndLabel($id, $code, $kind, $language, $label)) {
            $this->changes->write($code, $kind . " deleted", $language . ':' . $label, '');
        }
        elseif ($rows = $this->rubrics->get($id)) {
            $this->changes->write("", "deleted", $rows['language'] . ':' . $rows['label'], '');
        }
        $this->rubrics->delete($id);
    }

    
    function globalChangeLanguage($source, $target)
    {
        $source = strtolower($source);
        $target = strtolower($target);
        $query = $this->con->prepare("UPDATE rubrics SET language = ? WHERE language = ?");
        $query->bind_param("ss", $target, $source);
        if ($query->execute()) {
            $this->changes->write('global', "Language changed", $source, $target);
        }
        $query->close();
    }


    function globalChangeWord($rkind, $source, $target)
    {
        $source = strtolower($source);
        if ($rows = $this->rubrics->selectRubricsContaining($rkind, $source)) {
            $query = $this->con->prepare("UPDATE rubrics SET label = ?  WHERE id = ?");
            foreach ($rows as $row) {
                $label = '';
                $changed = false;
                $words = explode(" ", $row['label']);
                foreach ($words as $word) {
                    //echo "&nbsp;" . $word . "<br/>";
                    $lastChar = substr($word, -1);
                    if (($lastChar == ",") || ($lastChar == ".") || ($lastChar == "-") || ($lastChar == ":")) {
                        $word = substr($word, 0, -1);
                    }
                    else {
                        $lastChar = '';
                    }
                    if (strtolower($word) == $source) {
                        //echo "&nbsp;&nbsp;" . ' == ' . $source . "<br/>";
                        if ($word == ucfirst($source)) {
                            $label .= ucfirst($target);
                        }
                        else {
                            $label .= $target;
                        }
                        $changed = true;
                    }
                    else {
                        $label .= $word;
                    }
                    if ($lastChar != '') $label .= $lastChar;
                    $label .= ' ';
                }
                if ($changed) {
                    //echo "&nbsp;&nbsp;&nbsp;&nbsp;'" . $label . "'<br/>";
                    $label = trim($label);
                    $query->bind_param("si", $label, $row['id']);
                    $query->execute();
                }
            }
            $this->changes->write('global', "Word translated", $source, $target);
            $query->close();
        }
    }

    
    private function dropTables()
    {
        $this->nodes->drop();
        $this->rubrics->drop();
        $this->ckinds->drop();
        $this->rkinds->drop();
        $this->links->drop();
        $this->modders->drop();
        $this->changes->drop();
        $this->stats->drop();
        $this->comments->drop();
    }


    private function createTables()
    {
        $valid = (  $this->ckinds->create()
                and $this->rkinds->create()
                and $this->nodes->create()
                and $this->links->create()
                and $this->modders->create()
                and $this->rubrics->create()
                and $this->changes->create()
                and $this->stats->create()
                and $this->comments->create());
        if ($valid) {
            $this->nodes->insert(0, TOPMODIFIER);
            $this->nodes->insert(0, TOPCATEGORY);
            $this->rkinds->insertNoCheck(PREFERRED_NAME, "");
            $this->rkinds->insertNoCheck(AUTHOR_NAME, AUTHOR_DISPLAY);
        }
        return $valid;
    }


    private function addBasicMetaModel()
    {
        $this->ckinds->insert(CATEGORY_NAME, "");
        $this->ckinds->insert(MODIFIER_NAME, "");
        $this->setClassificationName("New classification", date('Y-m-d'), "0000");
    }


    function updateStats()
    {
        $update = true;
        if ($result = $this->con->query("SELECT * FROM stats WHERE about = " . STATS_STATS)) {
            if ($row = $result->fetch_assoc()) {
                $stored = $row['total'];
                if ($stored == $this->changes->getNumber()) $update = false;
            }
        }

        if ($update) {
            $this->con->query("TRUNCATE TABLE stats");
            $this->con->query("INSERT INTO stats (about, kind, total) VALUES (" . STATS_STATS . ",0," . $this->changes->getNumber() . ")");
            if ($rows = $this->ckinds->getAll()) {
                $about = STATS_CLASS;
                foreach ($rows as $row) {
                    $kind = $row['id'];
                    $total = $this->nodes->countClassesOfKind($kind);
                    $this->con->query("INSERT INTO stats (about, kind, total) VALUES (" . $about . "," . $kind . "," . $total . ")");
                }
            }
    
            if ($rows = $this->rkinds->getAll()) {
                $about = STATS_RUBRIC;
                foreach ($rows as $row) {
                    $kind = $row['id'];
                    $total = $this->rubrics->countRubricsOfKind($kind);
                    $this->con->query("INSERT INTO stats (about, kind, total) VALUES (" . $about . "," . $kind . "," . $total . ")");
                }
            }
        }
    }

}

?>
