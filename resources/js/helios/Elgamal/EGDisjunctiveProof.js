import EGProof from "./EGProof";

export default class EGDisjunctiveProof {

    /**
     *
     * @param list_of_proofs : EGProof[]
     */
    constructor(list_of_proofs) {
        this.proofs = list_of_proofs;
    }

    /**
     *
     * @return {*}
     */
    toJSONObject() {
        return this.proofs.map(function (proof) {
            return proof.toJSONObject();
        });
    }

    /**
     *
     * @param d
     * @return {EGDisjunctiveProof|null}
     */
    static fromJSONObject(d) {
        if (d == null) {
            return null;
        }

        return new EGDisjunctiveProof(
            d.map(function (p) {
                return EGProof.fromJSONObject(p);
            })
        );
    }
}
