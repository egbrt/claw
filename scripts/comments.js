export default class Comments {
    constructor(url) {
        this.url = url;
        this.tree = null;
        this.holderEditor = $("#commentsEditor");
        this.holderAllComments = $("#allComments");
    }
    
    setTree(tree)
    {
        this.tree = tree;
        //$("#allComments ul li").click(function() {
        this.holderAllComments.on("click", "li", function() {
            tree.show(this.id);
        });
    }
    
    clear()
    {
        this.holderEditor.val("");
        this.holderAllComments.empty();
        if (this.tree.selectedId == 0) {
            this.holderEditor.hide();
            this.holderAllComments.show();
        }
        else {
            this.holderEditor.show();
            this.holderAllComments.hide();
        }
    }

    show(id) 
    {
        let comments = this;
        comments.clear();
        $.ajax ({
            url: comments.url + "?operation=getComments",
            data: {id:id},
            type: "GET",
            dataType: "json",
        })
        .done (function(json) {
            comments.display(json);
        })
    }
    
    display(json)
    {
        let comments = this;
        comments.clear();
        $("#saveComments").hide();
        if (json.text) {
            if (comments.tree.selectedId == 0) { // root
                if (json.text.length> 0) {
                    $("#commentsPresent").show();
                    let comment = "<ul>";
                    json.text.forEach(function(text) {
                        if (text.text.length > 80) text.text = text.text.substr(0,80) + "...";
                        comment += "<li id=\"" + text.id + "\">" + text.code + " " + text.text + "</li>";
                    });
                    comment += "</ul>";
                    comments.holderAllComments.append(comment);
                }
            }
            else {
                comments.holderEditor.val(json.text);
                $("#commentsPresent").show();
            }
        }
        else {
            $("#commentsPresent").hide();
        }
    }
    
    save()
    {
        let comments = this;
        $.ajax ({
            url: comments.url + "?operation=putComments",
            data: {
                id: comments.tree.selectedId,
                text: comments.holderEditor.val()
            },
            type: "POST",
            dataType: "json",
        })
        .done (function(json) {
            comments.display(json);
        })
    }

}
