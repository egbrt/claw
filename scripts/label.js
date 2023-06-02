export default class Label {
    constructor(label) {
        this.language = "en";
        if (label[2] == ':') {
            this.language = label.substr(0, 2);
            label = label.substr(3);
        }
        this.content = label;
        
        // rewrite {{scheme:uid:code}} to <Reference scheme uid>code</Reference>
        var i = label.indexOf("{{");
        if (i >= 0) {
            var m = label.indexOf("}}", i+2);
            if (m > i) {
                this.content = label.substring(0, i) + "<Reference";
                var j = label.indexOf(":", i+2);
                if (j > i) {
                    if (j > i+2) {
                        this.content += " scheme=\"" + label.substring(i+2, j) + "\"";
                    }
                    var k = label.indexOf(":", j+1);
                    if (k > j+1) {
                        this.content += " uid=\"" + label.substring(j+1, k) + "\"";
                    }
                    this.content += ">";
                    if (m > k+1) {
                        this.content += label.substring(k+1, m);
                    }
                    this.content += "</Reference>" + label.substring(m+2);
                }
            }
        }
        this.content = this.content.trim();
    }
}
