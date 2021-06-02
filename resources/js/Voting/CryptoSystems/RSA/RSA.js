import RSAPublicKey from "./RSAPublicKey";
import RSACipherText from "./RSACipherText";
import RSAPlaintext from "./RSAPlaintext";

export default class RSA {

    /**
     *
     * @return {RSAPublicKey}
     */
    static getPublicKeyClass() {
        return RSAPublicKey;
    }

    /**
     *
     * @return {RSACipherText}
     */
    static getCipherTextClass() {
        return RSACipherText;
    }

    /**
     *
     * @return {RSAPlaintext}
     */
    static getPlainTextClass() {
        return RSAPlaintext;
    }

}
