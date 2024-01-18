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

require_once "auth/login.php";
startSecureSession();
require_once "auth/user.php";
require_once "classes/database.php";

class Category {
    public $id;
    public $code;
    public $text;
    public $ckind;
    public $parents;
    public $children;
    
    function __construct($id) {
        $this->id = $id;
        $this->code = "";
        $this->ckind = "";
        $this->text = "";
        $this->isLeaf = false;
        $this->parents = array();
        $this->children = array();
    }
}

class Rubric {
    public $id;
    public $code;
    public $kind;
    public $display;
    public $language;
    public $label;
    
    function __construct($id) {
        $this->id = $id;
        $this->code = "";
        $this->kind = "";
        $this->display = "";
        $this->language = DEFAULT_LANGUAGE;
        $this->label = "";
    }
}

class searchResult {
    public $found;
    public $parents;
}

class Comment {
    public $text;
}


if (isset($_REQUEST['operation']) && isAuthenticated($con, $user, $name, $role)) {
    if (isset($_REQUEST['id']) and is_numeric($_REQUEST['id'])) {
        $id = $_REQUEST['id'];
    }
    else {
        $id = 0;
    };
    header('Content-Type: application/json; charset=utf-8');
    $db = new Database($con);
    if ($_REQUEST['operation'] == "getClasses") {
        $classes = array();
        if ($id <= 2) { // 0=Root, 1=topModifier, 2=topCategory
            getRoot($db, $user, $id, $classes);
        }
        else {
            getClasses($db, $id, $classes);
        }
        echo json_encode($classes);
    }
    elseif ($_REQUEST["operation"] == "getRubrics") {
        $rubrics = array();
        getRubrics($db, $role, $id, $rubrics);
        echo json_encode($rubrics);
    }
    elseif ($_REQUEST["operation"] == "changeRubrics") {
        $rubrics = array();
        changeRubrics($db, $role, $id, $_REQUEST['code'], $_REQUEST['rubrics']);
        getRubrics($db, $role, $id, $rubrics);
        echo json_encode($rubrics);
    }
    elseif ($_REQUEST["operation"] == "search") {
        $searchResult = new Category(0);
        if (isset($_REQUEST["str"])) {
            $search = $_REQUEST["str"];
            $searchResult->text = "Search results for '" . $search . "'";
            searchClasses1($db, $search, $searchResult->children);
        }
        echo json_encode($searchResult);
    }
    elseif ($_REQUEST["operation"] == "searchCode") {
        $searchResult = new searchResult();
        $searchResult->found = array();
        $searchResult->parents = array();
        if (isset($_REQUEST["str"])) {
            $search = $_REQUEST["str"];
            searchCode($db, $search, $searchResult);
        }
        echo json_encode($searchResult);
    }
    elseif ($_REQUEST["operation"] == "getComments") {
        $comment = new Comment();
        $comment->text = $db->comments->get($id);
        echo json_encode($comment);
    }
    elseif ($_REQUEST["operation"] == "putComments") {
        $db->comments->put($id, $_REQUEST["text"]);
        $comment = new Comment();
        $comment->text = $db->comments->get($id);
        echo json_encode($comment);
    }
}


function getRoot($db, $user, $id, &$classes)
{
    $classes[0] = new Category($id);
    if ($user == "anonymous") {
        $classes[0]->id = 2;            // = topCategory
        $classes[0]->text = TOPCATEGORY;
        getClasses2($db, 2, $db->nodes->selectChildren(2), $classes[0]->children);
    }
    else {
        switch ($id) {
            case 0:
                $classes[0]->text = ROOT;
                getClasses2($db, $id, $db->nodes->selectRootClasses(), $classes[0]->children);
                break;
            case 1:
                $classes[0]->text = TOPMODIFIER;
                $classes[0]->parents[0] = new Category(0);
                $classes[0]->parents[0]->text = ROOT;
                getClasses2($db, $id, $db->nodes->selectChildren($id), $classes[0]->children);
                break;
            case 2:
                $classes[0]->text = TOPCATEGORY;
                $classes[0]->parents[0] = new Category(0);
                $classes[0]->parents[0]->text = ROOT;
                getClasses2($db, $id, $db->nodes->selectChildren($id), $classes[0]->children);
                break;
        }
        if ($classes[0]->children[1]->id == 2) { // = topCategory
            getClasses2($db, $id, $db->nodes->selectChildren(2), $classes[0]->children[1]->children);
        }
    }
}


function getClasses($db, $parent, &$classes)
{
    if ($parent) {
        $classes[0] = new Category($parent);
        $node = $db->nodes->get($parent);
        $classes[0]->code = $node->code;
        $classes[0]->ckind = $db->ckinds->getName($node->kind);
        $classes[0]->text = $db->rubrics->getPreferred($parent);
        getClasses2($db, $parent, $db->nodes->selectParents($parent), $classes[0]->parents);
        getClasses2($db, $parent, $db->nodes->selectChildren($parent), $classes[0]->children);
    }
    else {
        getClasses2($db, 0, $db->nodes->selectRootClasses(), $classes);
    }
}


function getClasses2($db, $parent, $rows, &$classes)
{
    $i = 0;
    if ($classes) $i = count($classes);
    foreach ($rows as $row) {
        $ckind = $db->ckinds->getName($row['kind']);
        $classes[$i] = new Category($row['id']);
        $classes[$i]->ckind = $ckind;
        $classes[$i]->code = $row['code'];
        $classes[$i]->text = $db->rubrics->getPreferred($row['id']);
        $classes[$i]->isLeaf = ($row['hasChildren'] == false);
        $i++;
    }
}


/* original search routine */
function searchClasses1($db, $search, &$foundClasses)
{
    $found = 0;
    $first = true;
    $searching = true;
    $local = array();
    $words = explode(' ', $search);
    foreach ($words as $word) {
        if ($searching) {
            searchRubrics($db, $word, $local);
            if ($first) {
                $classes = $local;
                $first = false;
            }
            else {
                $classes = array_intersect($classes, $local);
            }
            $local = array();
            if (count($classes) == 0) {
                $searching = false;
            }
        }
    }

    $seen = array();
    if ($searching) {
        collectClasses($db, $classes, $seen, $foundClasses);
    }
    else {
        collectCode($db, $words[0], $foundClasses);
    }
    return (count($foundClasses) > 0);
}


function collectCode($db, $search, &$foundClasses)
{
    if ($row = $db->nodes->selectClassWithCode($search)) {
        $foundClasses[0] = new Category($row['id']);
        $foundClasses[0]->ckind = $db->ckinds->getName($row['kind']);
        $foundClasses[0]->code = $row['code'];
        $foundClasses[0]->text = $db->rubrics->getPreferred($row['id']);
        $foundClasses[0]->isLeaf = ($row['hasChildren'] == false);
    }
}


function collectClasses($db, $classes, &$seen, &$foundClasses)
{
    $i = count($foundClasses);
    foreach ($classes as $class) {
        if (!in_array($class, $seen)) {
            $seen[count($seen)] = $class;
            $node = $db->nodes->get($class);
            $foundClasses[$i] = new Category($class);
            $foundClasses[$i]->ckind = $db->ckinds->getName($node->kind);
            $foundClasses[$i]->code = $node->code;
            $foundClasses[$i]->text = $db->rubrics->getPreferred($node->id);
            $foundClasses[$i]->isLeaf = ($node->hasChildren == false);
            $i++;
        }
    }
}


function searchCode($db, $search, &$searchResult)
{
    if ($class = $db->nodes->find($search)) {
        if ($rows = $db->links->selectParents($class->id)) {
            $found = 0;
            foreach ($rows as $row) {
                if (!in_array($class->id, $searchResult->found)) {
                    $searchResult->found[$found] = $class->id;
                    addClassParents($db, $row['head'], $searchResult->parents);
                    $found++;
                }
            }
        }
    }
}


function searchRubrics($db, $word, &$classes)
{
    $foundNewClasses = false;
    $found = count($classes);
    if ($rows = $db->rubrics->selectRubricsContaining(-1, $word)) {
        foreach ($rows as $row) {
            if (!in_array($row['class'], $classes)) {
                $classes[$found] = $row['class'];
                $foundNewClasses = true;
                $found++;
            }
        }
    }
    return $foundNewClasses;
}


function addClassParents($db, $parent, &$parents)
{
    if ($rows = $db->links->selectParents($parent)) {
        $top = true;
        foreach ($rows as $row) {
            $top = false;
            if (!in_array($parent, $parents)) {
                $parents[count($parents)] = $parent;
                addClassParents($db, $row['head'], $parents);
            }
        }
        if ($top) $parents[count($parents)] = $parent;
    }
}


function getRubrics($db, $role, $class, &$rubrics)
{
    if ($class == 0) {
        getScheme($db, $role, $rubrics);
    }
    else {
        if ($node = $db->nodes->get($class)) {
            $code = $node->code;
            $_SESSION['currentCode'] = $code;
            $rubrics[0] = new Rubric($class);
            $rubrics[0]->code = $code;
            $rubrics[0]->kind = $db->ckinds->getName($node->kind);
        
            $i = 1;
            $_SESSION['code'] = $code;
            
            // get rubrics
            if ($rows = $db->rubrics->getAllOfClass($class)) {
                foreach ($rows as $row) {
                    $rubrics[$i] = new Rubric($row['id']);
                    $db->rkinds->getNameAndDisplay($row['kind'], $rubrics[$i]->kind, $rubrics[$i]->display);
                    if ($rubrics[$i]->kind == PREFERRED_NAME) {
                        $rubrics[0]->label = $row['label'];
                    }
                    $rubrics[$i]->language = $row['language'];
                    $rubrics[$i]->label = $row['label'];
                    $i++;
                }
            }

            // get modifiers
            if ($rows = $db->modders->select($class)) {
                foreach ($rows as $row) {
                    $rubrics[$i] = new Rubric($row['id']);
                    $rubrics[$i]->kind = '_modifiedBy';
                    $rubrics[$i]->label = $db->nodes->get($row['modifier'])->code . ' ' . $db->rubrics->getPreferred($row['modifier']);
                    $i++;
                }
            }
            
            // get modified classes, in case this is a modifier
            if ($rows = $db->modders->getModded($class)) {
                foreach ($rows as $row) {
                    $rubrics[$i] = new Rubric($row['id']);
                    $rubrics[$i]->kind = '_modifies';
                    $rubrics[$i]->label = $db->nodes->get($row['class'])->code . ' ' . $db->rubrics->getPreferred($row['class']);
                    $i++;
                }
            }
        }
    }
}


function changeRubrics($db, $role, $class, $code, $rubrics)
{
    if (validClass($role, $class) and changeMetaInfo($db, $class, $rubrics)) {    
        if ($rows = $db->rubrics->getAllOfClass($class)) {
            $i = 0;
            $checkRubrics = array();
            foreach ($rows as $row) {
                if (isRubricDeleted($db, $row['kind'], $row['language'], $row['label'], $rubrics)) {
                    $checkRubrics[] = $row['id'];
                }
            }
            
            $i = 0;
            $deletedRubrics = array();
            foreach ($rows as $row) {
                if  (in_array($row['id'], $checkRubrics)) {
                    if (isRubricChanged($db, $row['kind'], $row['language'], $row['label'], $rubrics, $language, $label)) {
                        $db->changeRubric($row['id'], $language, $label);
                    }
                    else {
                        $deletedRubrics[] = $row['id'];
                    }
                }
            }
            
            $i = 0;
            while ($i < count($deletedRubrics)) {
                $db->deleteRubric($deletedRubrics[$i]);
                $i++;
            }
        }
        addNewRubrics($db, $class, $rubrics);
    }
}


function validClass($role, $class)
{
    $valid = true;
    if ($class == 0) {
        $valid = ($role == "admin");
    }
    return $valid;
}


function changeMetaInfo($db, $class, &$rubrics)
{
    $i = 0;
    $valid = true;
    while ($i < count($rubrics)) {
        if ($rubrics[$i]['kind'] == '_code') {
            $rubrics[$i]['seen'] = true;
            if (!$db->nodes->find($rubrics[$i]['label'])) {
                $db->changeNodeCode($class, $rubrics[$i]['label']);
            }            
        }
        elseif ($rubrics[$i]['kind'] == '_classkind') {
            $rubrics[$i]['seen'] = true;
            $db->changeNodeKind($class, $db->ckinds->getId($rubrics[$i]['label']));
        }
        elseif ($rubrics[$i]['kind'] == '_addSubclass') {
            $rubrics[$i]['seen'] = true;
            if ($node = $db->nodes->find($rubrics[$i]['label'])) { // class already exists
                $db->links->remove($node->id, $class); // removes link if it already exists
                $db->links->insert($node->id, $class);
                $db->nodes->setHasChildren($class);
            }
            else {
                addSubClass($db, $class, $rubrics[$i]['label']);
            }
        }
        elseif ($rubrics[$i]['kind'] == '_deleteSubclass') {
            $rubrics[$i]['seen'] = true;
            // first find subclass
            if ($subclass = $db->nodes->find($rubrics[$i]['label'])) {
                removeClass($db, $class, $subclass->id);
            }
        }
        elseif ($rubrics[$i]['kind'] == '_sortSubclasses') {
            $db->nodes->sortChildren($class);
            $i = count($rubrics);
            $valid = false;
        }
        elseif ($rubrics[$i]['kind'] == '_modifier') {
            $rubrics[$i]['seen'] = true;
            $modifier = $db->nodes->find($rubrics[$i]['label']);
            if ($modifier) {
                $db->modders->insertWhenNew($class, $modifier->id);
            }
        }
        elseif ($rubrics[$i]['kind'] == '_removeModifier') {
            $rubrics[$i]['seen'] = true;
            $modifier = $db->nodes->find($rubrics[$i]['label']);
            if ($modifier) {
                $db->modders->delete2($class, $modifier->id);
            }
        }
        $i++;
    }
    return $valid;
}


function isRubricDeleted($db, $kind, $language, $oldLabel, &$rubrics)
{
    $i = 0;
    $deleted = true;
    while ($i < count($rubrics)) {
        if  ((!array_key_exists('seen', $rubrics[$i]))
        and ($rubrics[$i]['language'] == $language)
        and ($db->rkinds->getId($rubrics[$i]['kind']) == $kind)
        and ($rubrics[$i]['label'] == $oldLabel)) {
            $rubrics[$i]['seen'] = true;
            $deleted = false;
            $i = count($rubrics);
        }
        $i++;
    }
    return $deleted;
}


function isRubricChanged($db, $kind, $language, $oldLabel, &$rubrics, &$newLanguage, &$newLabel)
{
    $i = 0;
    $changed = false;
    $newLabel = "";
    while ($i < count($rubrics)) {
        if ((!array_key_exists('seen', $rubrics[$i])) 
        and ($db->rkinds->getId($rubrics[$i]['kind']) == $kind)) {
            $newLanguage = $rubrics[$i]['language'];
            $newLabel = $rubrics[$i]['label'];
            $rubrics[$i]['seen'] = true;
            $changed = true;
            $i = count($rubrics);
        }
        $i++;
    }
    return $changed;
}


function addNewRubrics($db, $class, $rubrics)
{
    $i = 0;
    while ($i < count($rubrics)) {
        if (!array_key_exists('seen', $rubrics[$i])) {
            $rkind = $db->rkinds->getId($rubrics[$i]['kind']);
            if ($rkind <> 0) {
                $db->insertRubric($class, $rkind, $rubrics[$i]['language'], $rubrics[$i]['label']);
            }
        }
        $i++;
    }
}


function addSubClass($db, $class, $sub)
{
    $subclass = $db->insertNode(CATEGORY_ID, $sub);
    $db->rubrics->insert($subclass, PREFERRED_ID, DEFAULT_LANGUAGE, "Specify preferred rubric");
    if ($class <> 0) {
        $db->links->insert($subclass, $class);
        $db->nodes->setHasChildren($class);
        $db->nodes->setHasParent($subclass);
    }
}


function removeClass($db, $parent, $class)
{
    // remove link between class and parent
    $db->links->remove($class, $parent);

    // does class have any parents left?
    if (!$db->nodes->selectParents($class)) {             // class has no more parents
        if ($rows = $db->nodes->selectChildren($class)) { // then remove children of class
            foreach ($rows as $row) {
                removeClass($db, $class, $row['id']);
            }
        }
        $db->rubrics->deleteAll($class); // delete all its rubrics
        $db->deleteNode($class);         // finally delete class itself
    }

    // does parent have any children left?
    if (!$db->nodes->selectChildren($parent)) {
        $db->nodes->setHasChildren($parent, false);
    }
}


function getScheme($db, $role, &$rubrics)
{
    $db->getScheme($title, $date, $version, $subversion, $authors);
    for ($i = 0; $i < 5; $i++) {
        $rubrics[$i] = new Rubric(0);
        $rubrics[$i]->code = '*';
    }
        
    $rubrics[0]->kind = $role;
    
    $rubrics[1]->kind = "title";
    $rubrics[1]->display = "Title";
    $rubrics[1]->label = $title;

    $rubrics[2]->kind = "date";
    $rubrics[2]->display = "Date";
    $rubrics[2]->label = $date;

    $rubrics[3]->kind = "version";
    $rubrics[3]->display = "Version";
    $rubrics[3]->label = $version;

    $rubrics[4]->kind = "subversion";
    $rubrics[4]->display = "Subversion (= Number of changes)";
    $rubrics[4]->label = $subversion;
    
    foreach ($authors as $author) {
        $rubrics[$i] = new Rubric(0);
        $rubrics[$i]->code = '*';
        $rubrics[$i]->kind = AUTHOR_NAME;
        $rubrics[$i]->display = AUTHOR_DISPLAY;
        $rubrics[$i]->label = $author;
        $i++;
    }
}

?>
