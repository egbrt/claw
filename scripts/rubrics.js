import RKind from "./rkind.js";
import Label from "./label.js";
import Scheme from "./scheme.js";

export default class Rubrics {
    constructor(url, divEditor, divPreview) {
        this.url = url;
        this.tree = null;
        this.holderEditor = $(divEditor);
        this.holderPreview = $(divPreview);
        this.searchText = "";
        this.windowFollower = null;
        this.windowExternal = Array();
        this.urlFollower = "https://browser.icpc-3.info";

        let rubrics = this;
        rubrics.holderPreview.on("click", "Reference", function(reference) {
            let scheme = new Scheme(reference);
            if (scheme.isExternal) {
                if (scheme.isOtherClaW) {
                    if ((rubrics.windowExternal == null) || (rubrics.windowExternal[scheme.name] == null) || (rubrics.windowExternal[scheme.name].closed)) {
                        rubrics.windowExternal[scheme.name] = window.open(scheme.url, scheme.name);
                        setTimeout(function() {
                            rubrics.windowExternal[scheme.name].postMessage(scheme.code, scheme.url)
                        }, scheme.msecs2Wait1Time);
                    }
                    else if (rubrics.windowExternal[scheme.name]) {
                        rubrics.windowExternal[scheme.name].focus();
                        rubrics.windowExternal[scheme.name].postMessage(scheme.code, scheme.url);
                    }
                }
                else if (scheme.isKnownScheme) {
                    let old = window.open('',scheme.name);   // het liefst zou ik deze twee regels
                    if (old) old.close();                    // weglaten, maar dat werkt alleen in Opera
                    window.open(scheme.url, scheme.name).focus();
                }
                else {
                    alert("The scheme attribute in the Reference tag is unknown.");
                }
            }
            else {
                rubrics.tree.jumpTo(scheme.code);
            }
        });
    }
    
    
    show(id)
    {
        let rubrics = this;
        rubrics.clear();
        $.ajax ({
            url: rubrics.url + "?operation=getRubrics",
            data: {id:id},
            type: "GET",
            dataType: "json",
        })
        .done (function(json) {
            rubrics.display(id, json);
        })
    }

    clear()
    {
        this.holderEditor.val("");
        this.holderPreview.empty();
    }
    
    
    display(id, json)
    {
        let rubrics = this;
        let i = 1;
        let editor = "";
        let preview = "";
        let prevKind = "";
        while (i<json.length) {
            let rkind = new RKind(json[i].kind);
            if (rkind.name != prevKind) {
                if (prevKind != "") editor += "\n";
                editor += rkind.name + "\n";
                prevKind = rkind.name;
                if (!(rkind.isPreferred || rkind.isHidden)) { // not preferred or hidden
                    preview += "<br/><em>";
                    if (json[i].display == '') preview += rkind.name;
                    preview += json[i].display + "</em><br/>";
                }
            }
            if (!rkind.isHidden) { // not hidden
                if (rkind.isPreferred) {
                    preview += "<strong>" + json[0].code + " " + json[i].label + "</strong><br/>";
                }
                else {
                    preview += json[i].label + "<br/>";
                }
            }
            if (json[i].language != '') editor += json[i].language + ':';
            editor += json[i].label + "\n";
            i++;
        }
        
        if (rubrics.searchText) {
            let words = rubrics.searchText.split(' ');
            for (i = 0; i < words.length; i++) {
                let replace = new RegExp(words[i], 'gi');
                preview = preview.replace(replace, function(x) {
                    return "<strong><em>"+x+"</em></strong>";
                })
            }
        }
        
        let editable = true;
        if (id == 0) { // i.e. root
            editable = (json[0].kind == "admin");
        }
        $(rubrics).attr("disabled", !editable);
        
        rubrics.tree.currentCode = json[0].code;
        rubrics.tree.currentClassKind = json[0].kind;
        rubrics.holderEditor.val(editor);
        $("#saveChangedRubrics").hide();
        rubrics.holderPreview.html(preview);
        rubrics.holderPreview.scrollTop(0);
        rubrics.tree.showRelevantCommands();
        
        if (rubrics.windowFollower) {
            rubrics.windowFollower.postMessage(rubrics.tree.currentCode, rubrics.urlFollower);
        }
    }
    
    
    check()
    {
        let valid = true;
        let apos = 0;
        let hooks = 0;
        let startApos = 0;
        let startHooks = 0;
        let cursor = 0;
        let error = "";
        let text = this.holderEditor.val();
        while ((valid) && (cursor < text.length)) {
            if (text[cursor] == "\"") {
                startApos = cursor;
                apos++;
            }
            else if (text[cursor] == "\n") {
                if (apos & 1) { // apos = odd
                    error = "Missing closing apostrophe!";
                    $("#textEditor").prop("selectionStart", startApos);
                    valid = false; 
                }
                else if (hooks > 0) {
                    error = "Missing closing '>'";
                    $("#textEditor").prop("selectionStart", startHooks);
                    valid = false;
                }
                startHooks = cursor+1;
                startApos = cursor+1;
                hooks = 0;
                apos = 0;
            }
            else if (apos & 1) { // apos = odd = 1
                // ignore the rest, because these are now within ""
            }
            else if (text[cursor] == "<") {
                if (hooks > 0) {
                    error = "Missing closing '>'";
                    $("#textEditor").prop("selectionStart", startHooks);
                    valid = false;
                }
                else {
                    startHooks = cursor;
                    hooks++;
                }
            }
            else if (text[cursor] == ">") {
                if (hooks == 0) {
                    error = "Missing opening '<'";
                    $("#textEditor").prop("selectionStart", startHooks);
                    valid = false;
                }
                else {
                    hooks--;
                }
            }
            cursor++;
        }
        if (valid) {
            if (apos & 1) { // apos = odd
                error = "Missing closing apostrophe!";
                $("#textEditor").prop("selectionStart", startApos);
                valid = false; 
            }
            else if (hooks > 0) {
                error = "Missing closing '>'";
                $("#textEditor").prop("selectionStart", startHooks);
                valid = false;
            }
        }
        if (!valid) {
            $("#errorInRubrics").html(error).show();
            $("#textEditor").prop("selectionEnd", cursor);
            $("#textEditor").focus();
        }
        return valid;
    }
    
    
    save()
    {
        let rubrics = this;
        let text = rubrics.holderEditor.val();
        let rubric = {kind:"", label:"", language:"en"};
        let kind = "";
        let numRubrics = 0;
        let edits = Array();
        let refreshTree = false;
        let lines = text.split("\n");
        for (let i=0; i<lines.length; i++) {
            if (lines[i].length == 0) {
                kind = "";
            }
            else if (lines[i].startsWith('_')) {
                let parts = lines[i].split(':=');
                if (parts.length > 0) {
                    rubric.kind = parts[0].trim();
                    if (parts.length > 1) {
                        rubric.label = parts[1].trim();
                    }
                    edits[numRubrics] = rubric;
                    numRubrics++;
                    if ((rubric.kind == '_addSubclass') 
                        || (rubric.kind == '_deleteSubclass')
                        || (rubric.kind == '_sortSubclasses')) {
                        refreshTree = true;
                    }
                    rubric = {kind:"", label:"", language:"en"};
                }
            }
            else if (kind == "") {
                kind = lines[i];
            }
            else {
                let label = new Label(lines[i]);
                rubric.kind = kind;
                rubric.label = label.content;
                rubric.language = label.language;
                edits[numRubrics] = rubric;
                numRubrics++;
                rubric = {kind:"", label:"", language:"en"};
            }
        }
        
        $.ajax ({
            url: rubrics.url + "?operation=changeRubrics",
            data: {
                id: rubrics.tree.selectedId,
                code: rubrics.tree.currentCode,
                rubrics: edits
            },
            type: "POST",
            dataType: "json",
        })
        
        .done (function(json) {
            let label = "";
            if (json[0].kind != "") label += json[0].kind + " ";
            if (json[0].code != "") label += json[0].code + " ";
            label += json[0].label;
            rubrics.tree.checkLabel(label);
            rubrics.display(rubrics.tree.selectedId, json);
            if (refreshTree) {
                rubrics.tree.refreshCurrentNode();
            }
        })
    }
    
    
    
}
