export default class RSAPlaintext{


    /**
     * TODO
     * @param r : BigInt
     */
    encrypt(r = null) {
        const text = r.toString(16);
        console.log(this.key.encrypt(text,'hex'));
        console.log(this.key.encrypt(text,'base64'));
    }

}
