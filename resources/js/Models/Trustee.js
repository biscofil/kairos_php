import EGDLogProof from "../Voting/CryptoSystems/Elgamal/EGDLogProof";
import EGPublicKey from "../Voting/CryptoSystems/Elgamal/EGPublicKey";
import EGProof from "../Voting/CryptoSystems/Elgamal/EGProof";
import Utils from "./Utils/Utils";
import PeerServer from "./PeerServer";
import User from "./User";

export default class Trustee {

    /**
     *
     * @param user : ?User
     * @param peer_server : ?PeerServer
     * @param uuid : String
     * @param public_key : PublicKey
     * @param public_key_hash : String
     * @param pok : EGProof
     * @param decryption_factors : Array
     * @param decryption_proofs : Array
     * @param accepts_ballots : Boolean
     */
    constructor(user, peer_server, uuid, public_key, public_key_hash,
                pok, decryption_factors, decryption_proofs,
                accepts_ballots) {
        this.user = user;
        this.peer_server = peer_server;
        //
        this.uuid = uuid;
        this.public_key = public_key;
        this.public_key_hash = public_key_hash;
        this.pok = pok;
        this.decryption_factors = decryption_factors;
        this.decryption_proofs = decryption_proofs;
        this.accepts_ballots = accepts_ballots;
    }

    /**
     *
     * @return {{public_key: {p: string, q: string, g: string, y: string}, decryption_proofs: *, name: *, decryption_factors: (null|*), pok: {response: BigInt, challenge: BigInt, commitment: {A, B}}, email: *}}
     */
    toJSONObject() {
        return {
            'decryption_factors': Utils.jsonify_list_of_lists(this.decryption_factors),
            'decryption_proofs': Utils.jsonify_list_of_lists(this.decryption_proofs),
            'pok': this.pok.toJSONObject(),
            'public_key': this.public_key.toJSONObject()
        };
    }

    /**
     *
     * @param d : Object
     * @return {Trustee}
     */
    static fromJSONObject(d) {

        let pok = null;
        if (d.pok) {
            pok = EGDLogProof.fromJSONObject(d.pok); // TODO EGProof or EGDLogProof???
        }

        let decryption_factors = null;
        let decryption_proofs = null;

        if (d.decryption_factors) {
            decryption_factors = Utils.dejsonify_list_of_lists(d.decryption_factors, f => {
                return BigInt("0x" + f);
            });
            decryption_proofs = Utils.dejsonify_list_of_lists(d.decryption_proofs, EGProof.fromJSONObject);
        }

        let user = null;
        let peer_server = null;
        if (d.user) {
            user = User.fromJSONObject(d.user);
        } else if (d.peer_server) {
            peer_server = PeerServer.fromJSONObject(d.peer_server);
        }

        let public_key = null;
        if (d.public_key) {
            public_key = Utils.getPublicKeyFromJSONObject(d.public_key);
        }

        return new Trustee(
            user,
            peer_server,
            d.uuid,
            public_key,
            d.public_key_hash,
            pok,
            decryption_factors,
            decryption_proofs,
            d.accepts_ballots && peer_server
        );
    };

}
