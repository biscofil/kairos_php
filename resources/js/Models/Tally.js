import EGCiphertext from "../Voting/CryptoSystems/Elgamal/EGCiphertext";

export default class Tally {

    /**
     *
     * @param raw_tally
     * @param num_tallied
     */
    constructor(raw_tally, num_tallied) {
        this.tally = raw_tally;
        this.num_tallied = num_tallied;
    }

    /**
     *
     * @return {{num_tallied, tally}}
     */
    toJSONObject() {
        let tally_json_obj = this.tally.map(one_q => {
            return one_q.map(one_a => {
                return one_a.toJSONObject();
            });
        });

        return {
            num_tallied: this.num_tallied,
            tally: tally_json_obj
        };
    }

    /**
     * @param d
     * @param public_key : EGPublicKey
     * @return {Tally}
     */
    static fromJSONObject(d, public_key) {
        let num_tallied = d['num_tallied'];
        let raw_tally = d['tally'].map(one_q => {
            return one_q.map(one_a => {
                return EGCiphertext.fromJSONObject(one_a, public_key);
            });
        });
        return new Tally(raw_tally, num_tallied);
    };

}
