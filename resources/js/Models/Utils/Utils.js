import EGPublicKey from "../../Voting/CryptoSystems/ElGamal/EGPublicKey";
import RSAPublicKey from "../../Voting/CryptoSystems/RSA/RSAPublicKey";
import EGPlaintext from "../../Voting/CryptoSystems/ElGamal/EGPlaintext";

export default class Utils {

    /**
     *
     * @param arr : Array
     * @param val
     * @return {[]}
     */
    static array_remove_value(arr, val) {
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
    static object_sort_keys(obj) {
        let new_obj = {};
        // TODO Object.keys(obj)
        _(_.keys(obj)).each(function (k) {
            new_obj[k] = obj[k];
        });
        return new_obj;
    };

    /**
     * TODO remove
     * generate an array of the first few plaintexts
     * @param pk : EGPublicKey
     * @param min : ?number
     * @param max : number
     * @returns {EGPlaintext[]} TODO check Elgamal only
     */
    static generatePlaintexts(pk, min, max) {
        let last_plaintext = 1n;

        // an array of plaintexts
        let plaintexts = [];

        if (min == null) {
            min = 0;
        }

        // questions with more than one possible answer, add to the array.
        for (let i = 0; i <= max; i++) {
            if (i >= min) {
                plaintexts.push(new EGPlaintext(last_plaintext, pk, false)); // TODO
            }
            last_plaintext = (last_plaintext * pk.ps.g) % pk.ps.p;
        }

        return plaintexts;
    }

    /**
     * a utility function for jsonifying a list of lists of items
     * @param lol
     * @return {null|*}
     */
    static jsonify_list_of_lists(lol) {
        if (!lol) {
            return null;
        }
        return lol.map(function (sublist) {
            return sublist.map(function (item) {
                return item.toJSONObject();
            });
        });
    }

    /**
     * a utility function for doing the opposite with an item-level de-jsonifier
     * @param lol
     * @param item_dejsonifier
     * @return {null|*}
     */
    static dejsonify_list_of_lists(lol, item_dejsonifier) {
        if (!lol) {
            return null;
        }
        return lol.map(function (sublist) {
            return sublist.map(function (item) {
                return item_dejsonifier(item);
            });
        });
    }



    /**
     * TODO move
     * @param d : Object
     * @return {EGPublicKey | RSAPublicKey}
     */
    static getPublicKeyFromJSONObject(d) {
        if (d._cs === "eg") {
            return EGPublicKey.fromJSONObject(d);
        } else if (d._cs === "rsa") {
            return RSAPublicKey.fromJSONObject(d);
        }
    }

    static defaultVuetableCss() {
        return {
            table: {
                tableClass: 'table table-hover table-outline mb-0',
                tableHeaderClass: 'thead-light',
                ascendingIcon: "fas fa-arrow-up",
                descendingIcon: "fas fa-arrow-down",
                handleIcon: "fas fa-menu",
                renderIcon: function (classes, options) {
                    return `<span class="${classes.join(' ')}"></span>`
                }
            },
            paginationInfo: {
                infoClass: 'pull-left'
            },
            pagination: {
                wrapperClass: "pagination pull-right",
                activeClass: "btn-primary",
                disabledClass: "disabled",
                pageClass: "btn btn-border",
                linkClass: "btn btn-border",
                icons: {
                    first: "fas fa-backward",
                    prev: "fas fa-caret-left",
                    next: "fas fa-caret-right",
                    last: "fas fa-forward"
                }
            }
        };
    }

}
