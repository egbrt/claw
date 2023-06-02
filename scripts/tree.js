const PARENTS = 0;
const PARENT_ICON = 1;
const PARENT_LABEL = 2;

const CHILD_ICON = 0;
const CHILD_LABEL = 1;
const CHILDREN = 2;

const ICON_LEAF = "&#x25CB;";
const ICON_EXPLODE = "&#x25B8;";
const ICON_CHILD_IMPLODE = "&#x25BE;";
const ICON_PARENT_IMPLODE = "&#x25B4;";

export default class Tree {
    constructor(url, divTree) {
        this.url = url;
        this.busy = false;
        this.treeDiv = divTree;
        this.treeHolder = $(this.treeDiv);
        this.rubrics = null;
        this.comments = null;
        this.selectedId = 0;
        this.centerId = 0;
        this.currentCode = "";
        this.currentClassKind = "";
        this.limited = null;
        this.mode = "";

        let tree = this;
        tree.treeHolder.on("click", " .li_label", function(event) {
            tree.selectNode(this.parentNode);
        });
        tree.treeHolder.on("click", " .li_icon", function(event) {
            tree.selectNode(this.parentNode);
            if (!event.ctrlKey) {
                tree.expandTree(this.parentNode);
            }
        });
        tree.treeHolder.on("click", "p", function(event) {
            tree.selectedId = this.id;
            $(tree.treeDiv + ' .selected').removeClass("selected");
            $(this).addClass("selected");
            tree.rubrics.show(this.id);
            tree.comments.show(this.id);
        });
        
        $("#backToRoot").click(function() {
            tree.show(0);
        });
    }
    
    selectNode(concept)
    {
        this.selectedId = concept.id;
        if (event.ctrlKey) {
            this.show(concept.id);
        }
        else {
            $(this.treeDiv + ' .selected').removeClass('selected');
            $(concept).children(".li_label").addClass('selected');
            this.rubrics.show(concept.id);
            this.comments.show(concept.id);
        }
    }
    
    showRelevantCommands()
    {
        if ((this.currentCode == '') || (this.mode == 'preview')) {
            $('#allowedForRoot').hide();
            $('#allowedForAll').hide();
        }
        else if (this.selectedId <= 2) { // Root, topModifier, topCategory
            $('#allowedForRoot').show();
            $('#allowedForAll').hide();
        }
        else {
            $('#allowedForRoot').hide();
            $('#allowedForAll').show();
        }
    }
    
    checkLabel(label)
    {
        let current = $(this.treeDiv + " .selected");
        let currentLabel = current.html();
        if (currentLabel != label) current.html(label);        
    }
    
    refreshCurrentNode()
    {
        let current = $(this.treeDiv + " .selected"); // span
        if (current[0].localName == "p") { // centered node
            this.showNode(this.centerId);
        }
        else {
            let li = current[0].parentNode;
            if ($(li).attr("expanded") == "cached") {
                li.childNodes[CHILDREN].remove();
                $(li).attr("expanded", "false");
            }
            else if ($(li).attr("expanded") == "true") {
                this.implodeChildren(li);
                li.childNodes[CHILDREN].remove();
                $(li).attr("expanded", "false");
            }
            this.includeChildren(li);
        }
    }
    
    show(id)
    {
        this.showNode(id);
        this.selectedId = id;
        this.centerId = id;
        if (id == 0) {
            $("#backToRoot").hide(250);
        }
        else
        {
            $("#backToRoot").show(250);
        }
    }
    
    jumpTo(code)
    {
        if (code != this.currentCode) {
            let tree = this;
            $.ajax ({
                url: tree.url + "?operation=searchCode",
                data: {str:code},
                type: "GET",
                dataType: "json",
            })
            
            .done (function(json) {
                if (json.found.length == 0) { // code not found
                    alert("The code \"" + code + "\" is not present in the classification.");
                }
                else {
                    tree.show(json.found[0]);
                }
            })
        }
    }

    search(text)
    {
        let tree = this;
        tree.centerId = 0;
        tree.selectedId = 0;
        tree.treeHolder.empty();
        tree.comments.clear();
        tree.rubrics.clear();
        tree.rubrics.searchText = text;
        $(document.body).css("cursor", "progress");
        $.ajax ({
            url: tree.url + "?operation=search",
            data: {str:text},
            type: "GET",
            dataType: "json",
        })
        .done (function(json) {
            $("#backToRoot").show(250);
            if (json.children.length > 0) {
                let description = "<ul>";
                json.children.forEach(function(node) {
                    description += "<li id=\"" + node.id + "\" tree=\"down\" class=\"" + node.ckind;
                    if (tree.selectedId == 0) {
                        description += " selected";
                        tree.selectedId = node.id;
                        tree.centerId = node.id;
                    }
                    description += "\">" + tree.getLiLabel(node) + "</li>";
                });
                description += "</ul>";
                tree.treeHolder.append(description);
                tree.rubrics.show(tree.selectedId);
                tree.comments.show(tree.selectedId);
            }
            $(document.body).css("cursor", "default");
        })
    }
    
    showNode(id)
    {
        let tree = this;
        tree.treeHolder.empty();
        tree.comments.clear();
        tree.rubrics.clear();
        $.ajax ({
            url: tree.url + "?operation=getClasses",
            data: {id:id},
            type: "GET",
            dataType: "json",
        })
        .done (function(json) {
            if (json.length > 0) {
                tree.centerId = id;
                let description = "";
                if (json[0].parents.length > 0) {
                    description += "<ul>";
                    json[0].parents.forEach(function(parent) {
                        description += tree.showParent(parent);
                    });
                    description += "</ul>";
                    tree.treeHolder.append(description);
                }
                
                description = "<p id=\"" + json[0].id + "\"><span class=\"selected\">";
                if (json[0].ckind != "") description += json[0].ckind + " ";
                if (json[0].code != "") description += json[0].code + " ";
                description += json[0].text + "</span></p>";
                tree.treeHolder.append(description);

                description = "<ul>";
                json[0].children.forEach(function(child) {
                    description += tree.showChild(child);
                });
                description += "</ul>";
                tree.treeHolder.append(description);
                tree.rubrics.show(id);
                tree.comments.show(id);
            }
        })
    }
    
    
    expandTree(concept)
    {
        if (!this.busy) {
            if ($(concept).attr("tree") == "down") {
                if ($(concept).attr("expanded") == "true") {
                    this.implodeChildren(concept);
                }
                else if ($(concept).attr("expanded") == "cached") {
                    this.uncacheChildren(concept);
                }
                else {
                    this.busy= true;
                    $(concept).css("cursor", "progress");
                    this.includeChildren(concept);
                }
            }
            else { // up
                if ($(concept).attr("expanded") == "true") {
                    this.implodeParents(concept);
                }
                else if ($(concept).attr("expanded") == "cached") {
                    this.uncacheParents(concept);
                }
                else {
                    this.busy= true;
                    $(concept).css("cursor", "progress");
                    this.includeParents(concept);
                }
            }
        }
    }


    includeChildren(concept)
    {
        let tree = this;
        $.ajax ({
            url: tree.url + "?operation=getClasses",
            data: {id:concept.id},
            type: "GET",
            dataType: "json",
        })
        .done (function(json) {
            if (json.length == 0) {
                $(concept).addClass("leaf");
            }
            else {
                $(concept.childNodes[CHILD_ICON]).html(ICON_CHILD_IMPLODE);
                let description = "<ul>";
                json[0].children.forEach(function(child) {
                    description += tree.showChild(child);
                });
                description += "</ul>";
                $(concept).attr("expanded", "true");
                $(concept).append(description);
            }
            $(concept).css("cursor", "default");
            tree.busy = false;
        })
    }


    includeParents(concept)
    {
        let tree = this;
        $.ajax ({
            url: tree.url + "?operation=getClasses",
            data: {id:concept.id},
            type: "GET",
            dataType: "json",
        })
        .done (function(json) {
            if (json[0].parents.length > 0) {
                let description = "<ul>";
                json[0].parents.forEach(function(parent) {
                    description += tree.showParent(parent);
                });
                description += "</ul>";
                $(concept).attr("expanded", "true");
                $(concept).prepend(description);
                $(concept.childNodes[PARENT_ICON]).html(ICON_PARENT_IMPLODE);
            }
            $(concept).css("cursor", "default");
            tree.busy = false;
        })
    }

    
    showChild(child)
    {
        let description = "<li id=\"" + child.id + "\" tree=\"down\" class=\"" + child.ckind;
        if (child.isLeaf == true) {
            description += " leaf";
        }
        description += "\"";
        if (child.children.length > 0) description += " expanded=\"true\"";
        description += ">" + this.getLiLabel(child);
        if (child.children.length > 0) { // could be topCategory
            let tree = this;
            description += "<ul>";
            child.children.forEach(function(grandchild) {
                description += tree.showChild(grandchild);
            });
            description += "</ul>";
        }        
        description += "</li>";
        return description;
    }
    
    
    showParent(parent)
    {
        let description = "<li id=\"" + parent.id + "\" tree=\"up\" class=\"" + parent.ckind + "\">";
        description += this.getLiLabel(parent) + "</li>";
        return description;
    }
    

    getLiLabel(node)
    {
        let label = "<span class=\"li_icon\">";
        if (node.isLeaf || (node.id == 0)) {
            label += ICON_LEAF;
        }
        else if (node.children.length > 0) {
            label += ICON_CHILD_IMPLODE;
        }
        else {
            label += ICON_EXPLODE;
        }
        label += "</span>";
        label += "<span class=\"li_label\">";
        if (node.ckind != "") label += node.ckind + " ";
        if (node.code != "") label += node.code + " ";
        label += node.text;
        label += "</span>";
        return label;
    }


    uncacheChildren(concept)
    {
        $(concept.childNodes[CHILD_ICON]).html(ICON_CHILD_IMPLODE);
        $(concept.childNodes[CHILDREN]).css("display", "block");
        $(concept).attr("expanded", "true");
        $(concept).css("cursor", "default");
        this.busy = false;
    }


    implodeChildren(concept)
    {
        $(concept.childNodes[CHILD_ICON]).html(ICON_EXPLODE);
        $(concept.childNodes[CHILDREN]).css("display", "none");
        $(concept).attr("expanded", "cached");
        $(concept).css("cursor", "default");
        this.busy = false;
    }


    uncacheParents(concept)
    {
        $(concept.childNodes[PARENT_ICON]).html(ICON_PARENT_IMPLODE);
        $(concept.childNodes[PARENTS]).css("display", "block");
        $(concept).attr("expanded", "true");
        $(concept).css("cursor", "default");
        this.busy = false;
    }


    implodeParents(concept)
    {
        $(concept.childNodes[PARENT_ICON]).html(ICON_EXPLODE);
        $(concept.childNodes[PARENTS]).css("display", "none");
        $(concept).attr("expanded", "cached");
        $(concept).css("cursor", "default");
        this.busy = false;
    }

}
