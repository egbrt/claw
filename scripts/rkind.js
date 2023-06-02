export default class RKind {
    constructor(rkind) {
        // adapt the following line to hide the rubrics with external references in browser-mode, e.g. to show core
        const hide_these = []; // ["icpc-1", "icpc-1nl", "icpc-fin", "icpc-2", "icd10", "icd11", "ichi", "icf", "dsm-v", "snomed-CT", "pcfs", "whodas", "atcif", "uhc", "gbd", "sdg"];

        this.name = rkind;
        this.isPreferred = (rkind == "preferred");
        this.isHidden = (rkind[0] == '.');
        if (!this.isHidden) {
            this.isHidden = hide_these.includes(rkind);
        }
    }
}

