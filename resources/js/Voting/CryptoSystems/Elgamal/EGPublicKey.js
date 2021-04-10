import {modPow} from "bigint-crypto-utils";
import PublicKey from "../PublicKey";

export default class EGPublicKey extends PublicKey {

    /**
     * @callback challengeGeneratorCallback
     * @param x : BigInt
     * @return BigInt
     */

    /**
     *
     * @param p : BigInt
     * @param q : BigInt
     * @param g : BigInt
     * @param y : BigInt
     */
    constructor(p, q, g, y) {
        super();
        this.p = p;
        this.q = q;
        this.g = g;
        this.y = y;
    }

    /**
     *
     * @return {{p: string, q: string, g: string, y: string}}
     */
    toJSONObject() {
        return {
            g: this.g.toString(),
            p: this.p.toString(),
            q: this.q.toString(),
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
        return modPow(this.g, proof.response, this.p) === ((modPow(this.y, proof.challenge, this.p) * proof.commitment) % this.p);
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
            this.g,
            ciphertext.alpha,
            this.y,
            decryptionFactor,
            this.p,
            this.q,
            challengeGenerator);
    }

    /**
     *
     * @param other : EGPublicKey
     * @return {EGPublicKey}
     */
    multiply(other) {
        // base condition
        if (other === 0 || other === 1) {
            return this;
        }

        // check params
        if (this.p !== other.p) {
            throw "mismatched params";
        }
        if (this.g !== other.g) {
            throw "mismatched params";
        }

        return new EGPublicKey(this.p, this.q, this.g, (this.y * other.y) % this.p);
    }

    /**
     *
     * @param other : EGPublicKey
     * @return {boolean}
     */
    equals(other) {
        return this.p === other.p && this.q === other.q && this.g === other.g && this.y === other.y;
    }

    /**
     *
     * @param d
     * @return {EGPublicKey}
     */
    static fromJSONObject(d) {
        return new EGPublicKey(
            BigInt("0x" + d.p),
            BigInt("0x" + d.q),
            BigInt("0x" + d.g),
            BigInt("0x" + d.y)
        );
    }

    /**
     * a bogus default public key to allow for ballot previewing, nothing more
     * this public key should not be used ever, that's why the secret key is not given.
     * @return {EGPublicKey}
     */
    static get_bogus() {
        let g = 14887492224963187634282421537186040801304008017743492304481737382571933937568724473847106029915040150784031882206090286938661464458896494215273989547889201144857352611058572236578734319505128042602372864570426550855201448111746579871811249114781674309062693442442368697449970648232621880001709535143047913661432883287150003429802392229361583608686643243349727791976247247948618930423866180410558458272606627111270040091203073580238905303994472202930783207472394578498507764703191288249547659899997131166130259700604433891232298182348403175947450284433411265966789131024573629546048637848902243503970966798589660808533n;
        let p = 16328632084933010002384055033805457329601614771185955389739167309086214800406465799038583634953752941675645562182498120750264980492381375579367675648771293800310370964745767014243638518442553823973482995267304044326777047662957480269391322789378384619428596446446984694306187644767462460965622580087564339212631775817895958409016676398975671266179637898557687317076177218843233150695157881061257053019133078545928983562221396313169622475509818442661047018436264806901023966236718367204710755935899013750306107738002364137917426595737403871114187750804346564731250609196846638183903982387884578266136503697493474682071n;
        let q = 61329566248342901292543872769978950870633559608669337131139375508370458778917n;
        let y = 8049609819434159960341080485505898805169812475728892670296439571117039276506298996734003515763387841154083296559889658342770776712289026341097211553854451556820509582109412351633111518323196286638684857563764318086496248973278960517204721786711381246407429787246857335714789053255852788270719245108665072516217144567856965465184127683058484847896371648547639041764249621310049114411288049569523544645318180042074181845024934696975226908854019646138985505600641910417380245960080668869656287919893859172484656506039729440079008919716011166605004711585860172862472422362509002423715947870815838511146670204726187094944n;
        return new EGPublicKey(p, q, g, y);
    };

}
