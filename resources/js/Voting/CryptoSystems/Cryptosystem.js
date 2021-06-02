import RSA from "./RSA/RSA";
import ElGamal from "./ElGamal/ElGamal";

/**
 *
 */
export default class Cryptosystem {

    /**
     *
     * @return {{rsa: RSA, eg: ElGamal}}
     */
    static cryptosystems() {
        return {
            'rsa': RSA,
            'eg': ElGamal
        }
    }

    /**
     * @param identifier : string
     * @return {RSA|ElGamal}
     */
    static getCryptosystemClassFromIdentifier(identifier) {
        return this.cryptosystems()[identifier];
    }

}
