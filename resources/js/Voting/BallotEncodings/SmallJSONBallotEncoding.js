export default class SmallJSONBallotEncoding {

    static getAlphabet() {
        return [
            "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "[", "]", ",",
            //
            "#", "!", "@"];
    };

    /**
     *
     * @param str : String
     * @return {BigInt}
     */
    static encodeStr(str) {
        let alphabet = SmallJSONBallotEncoding.getAlphabet();
        let out = "";
        for (let i = 0; i < str.length; i++) {
            let letter = str[i];
            let idx = alphabet.indexOf(letter).toString(16);
            out += idx;
        }
        return BigInt("0x" + out);
    };

    /**
     *
     * @param number : BigInt
     * @return {string}
     */
    static decodeStr(number) {
        let alphabet = SmallJSONBallotEncoding.getAlphabet();
        let out = "";
        let str = number.toString(16);
        for (let i = 0; i < str.length; i++) {
            let idx = str[i];
            let letter = alphabet[Number.parseInt(idx, 16)];
            out += letter;
        }
        return out;
    };
}
