export default class EGDLogProof {

    /**
     *
     * @param commitment : BigInt
     * @param challenge : BigInt
     * @param response : BigInt
     */
    constructor(commitment, challenge, response) {
        this.commitment = commitment;
        this.challenge = challenge;
        this.response = response;
    }

    /**
     *
     * @return {{response: string, challenge: string, commitment: string}}
     */
    toJSONObject() {
        return {
            'challenge': this.challenge.toString(),
            'commitment': this.commitment.toString(),
            'response': this.response.toString()
        };
    }

    /**
     *
     * @param d
     * @return {EGDLogProof}
     */
    static fromJSONObject(d) {
        return new EGDLogProof(
            BigInt(d.commitment || d.s),
            BigInt(d.challenge),
            BigInt(d.response)
        );
    }

}

