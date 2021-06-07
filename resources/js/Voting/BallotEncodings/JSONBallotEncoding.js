import {encode, decode} from "iconv-lite";

export default class JSONBallotEncoding {

    /**
     * @param str : String
     * @returns {BigInt}
     */
    static encodeStr(str) {
        let buffer = encode(str, 'ASCII');
        let hexBuffer = JSONBallotEncoding.bytesToHex(buffer);
        return BigInt("0x" + hexBuffer);
    }

    /**
     * @param number : BigInt
     */
    static decodeStr(number) {
        let hexBuffer = number.toString(16);
        let buffer = JSONBallotEncoding.hexToBytes(hexBuffer);
        return decode(buffer, 'ASCII');
    }

    /**
     * Convert a byte array to a hex string
     * @param bytes
     * @return {string}
     */
    static bytesToHex(bytes) {
        let hex = [];
        for (let i = 0; i < bytes.length; i++) {
            let current = bytes[i] < 0 ? bytes[i] + 256 : bytes[i];
            hex.push((current >>> 4).toString(16));
            hex.push((current & 0xF).toString(16));
        }
        return hex.join("");
    }

    /**
     * Convert a hex string to a byte array
     * @param hex
     * @return {*}
     */
    static hexToBytes(hex) {
        let bytes = [];
        for (let c = 0; c < hex.length; c += 2) {
            bytes.push(parseInt(hex.substr(c, 2), 16));
        }
        return bytes;
    }

}
