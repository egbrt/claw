export default class Scheme {
    constructor(reference) {
        this.isExternal = false;
        this.isOtherClaW = false;
        this.isKnownScheme = false;
        this.msecs2Wait1Time = 0;
        this.code = reference.target.childNodes[0].nodeValue;
        if ((reference.target.attributes.length > 0) && (reference.target.attributes[0].name == "scheme")) {
            this.uid = "";
            this.isExternal = true;
            this.name = reference.target.attributes[0].value;
            if ((reference.target.attributes.length > 1) && (reference.target.attributes[1].name == "uid")) {
                this.uid = reference.target.attributes[1].value;
            }
            if (this.name == "icd-10") {
                this.url = "https://icd.who.int/browse10/2019/en#/" + this.code;
                this.isKnownScheme = true;
            }
            else if (this.name == "icd-11") {
                if (this.uid != "") {
                    this.uid = reference.target.attributes[1].value;
                    this.url = "https://icd.who.int/browse11/l-m/en#/http://id.who.int/icd/entity/" + this.uid;
                    this.isKnownScheme = true;
                }
            }
            else if (this.name == "snomed-ct-en") {
                this.url = "https://browser.ihtsdotools.org/?perspective=full&edition=en-edition&release=v20190131&conceptId1=" + this.code;
                //this.url = "https://snomed.icpc-3.info";
                this.isKnownScheme = true;
                this.isOtherClaW = false;
                this.msecs2Wait1Time = 1000;
            }
            else if (this.name == "ichi") {
                if (this.uid != "") {
                    this.uid = reference.target.attributes[1].value;
                    this.url = "https://mitel.dimi.uniud.it/ichi/#http://id.who.int/ichi/entity/" + this.uid;
                    this.isKnownScheme = true;
                }
            }
            else if (this.name == "icpc-1-en") {
                this.url = "https://icpc1.icpc-3.info";
                this.isKnownScheme = true;
                this.isOtherClaW = true;
                this.msecs2Wait1Time = 2000;
            }
            else if (this.name == "icpc-2-en") {
                // for 'old' ROIS browser at who-fic
                // this.url = "https://class.whofic.nl/browser.aspx?scheme=ICPC-2e-7.0.cla&code=" + this.code;
                this.url = "https://icpc2.icpc-3.info";
                this.isKnownScheme = true;
                this.isOtherClaW = true;
                this.msecs2Wait1Time = 2000;
            }
            else if (this.name == "orphanet") {
                this.url = "https://www.orpha.net/consor/cgi-bin/OC_Exp.php?lng=EN&Expert=" + this.code;
                this.isKnownScheme = true;
            }
        }
    }
}
