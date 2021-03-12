import EGPlaintext from "../Elgamal/EGPlaintext";

export default class Utils {

    /**
     *
     * @param arr : Array
     * @param val
     * @return {[]}
     */
    array_remove_value(arr, val) {
        let new_arr = [];
        arr.forEach(function (v, i) {
            if (v !== val) {
                new_arr.push(v);
            }
        });
        return new_arr;
    };

    /**
     * produce the same object but with keys sorted
     * @param obj
     * @return {{}}
     */
    object_sort_keys(obj) {
        var new_obj = {};
        // TODO Object.keys(obj)
        _(_.keys(obj)).each(function (k) {
            new_obj[k] = obj[k];
        });
        return new_obj;
    };

    /**
     * generate an array of the first few plaintexts
     * @param pk : EGPublicKey
     * @param min : ?number
     * @param max : number
     * @returns {EGPlaintext[]}
     */
    generatePlaintexts(pk, min, max) {
        let last_plaintext = 1n;

        // an array of plaintexts
        let plaintexts = [];

        if (min == null) {
            min = 0;
        }

        // questions with more than one possible answer, add to the array.
        for (let i = 0; i <= max; i++) {
            if (i >= min) {
                plaintexts.push(new EGPlaintext(last_plaintext, pk, false));
            }
            last_plaintext = (last_plaintext * pk.g) % pk.p;
        }

        return plaintexts;
    }
}
