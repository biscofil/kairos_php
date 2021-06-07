import {modPow} from "bigint-crypto-utils";
import PublicKey from "../PublicKey";
import EGParameterSet from "./EGParameterSet";

export default class EGPublicKey extends PublicKey {

    /**
     * @callback challengeGeneratorCallback
     * @param x : BigInt
     * @return BigInt
     */

    /**
     *
     * @param ps : EGParameterSet
     * @param y : BigInt
     */
    constructor(ps, y) {
        super();
        this.ps = ps;
        this.y = y;
    }

    /**
     *
     * @return {{ps: {p: string, q: string, g: string}, y: string}}
     */
    toJSONObject() {
        return {
            ps: this.ps.toJSONObject(),
            y: this.y.toString()
        };
    }

    /**
     *
     * @param proof
     * @param challengeGenerator : ?challengeGeneratorCallback
     * @return {boolean|BigInt}
     */
    verifyKnowledgeOfSecretKey(proof, challengeGenerator) {
        // if challenge_generator is present, we have to check that the challenge was properly generated.
        if (challengeGenerator !== null) {
            if (proof.challenge !== challengeGenerator(proof.commitment)) {
                return false;
            }
        }

        // verify that g^response = s * y^challenge
        return modPow(this.ps.g, proof.response, this.ps.p) === ((modPow(this.y, proof.challenge, this.ps.p) * proof.commitment) % this.ps.p); // TODO mod
    }

    /**
     * check if the decryption factor is correct for this public key, given the proof
     * @param ciphertext : EGCiphertext
     * @param decryptionFactor
     * @param decryptionProof
     * @param challengeGenerator : challengeGeneratorCallback
     * @return {*}
     */
    verifyDecryptionFactor(ciphertext, decryptionFactor, decryptionProof, challengeGenerator) {
        return decryptionProof.verify(
            this.ps,
            ciphertext.alpha,
            this.y,
            decryptionFactor,
            challengeGenerator);
    }

    /**
     * todo rename to "combine"
     * @param other : EGPublicKey
     * @return {EGPublicKey}
     */
    multiply(other) {
        // base condition
        if (other === 0 || other === 1) {
            return this;
        }

        // check params
        if (this.ps.p !== other.ps.p) {
            throw "mismatched params";
        }
        if (this.ps.g !== other.ps.g) {
            throw "mismatched params";
        }

        return new EGPublicKey(this.ps, (this.y * other.y) % this.ps.p);
    }

    /**
     *
     * @param other : EGPublicKey
     * @return {boolean}
     */
    equals(other) {
        return this.ps.equals(other.ps) && this.y === other.y;
    }

    /**
     *
     * @param d
     * @return {EGPublicKey}
     */
    static fromJSONObject(d) {
        let parameterSet = EGParameterSet.fromJSONObject(d.ps);
        return new EGPublicKey(parameterSet, BigInt("0x" + d.y));
    }

    /**
     * a bogus default public key to allow for ballot previewing, nothing more
     * this public key should not be used ever, that's why the secret key is not given.
     * @return {EGPublicKey}
     */
    static get_bogus() {
        let y = 8049609819434159960341080485505898805169812475728892670296439571117039276506298996734003515763387841154083296559889658342770776712289026341097211553854451556820509582109412351633111518323196286638684857563764318086496248973278960517204721786711381246407429787246857335714789053255852788270719245108665072516217144567856965465184127683058484847896371648547639041764249621310049114411288049569523544645318180042074181845024934696975226908854019646138985505600641910417380245960080668869656287919893859172484656506039729440079008919716011166605004711585860172862472422362509002423715947870815838511146670204726187094944n;
        let parameterSet = EGParameterSet.get_bogus();
        return new EGPublicKey(parameterSet, y);
    };

}
