import EGPublicKey from "../Voting/CryptoSystems/Elgamal/EGPublicKey";
import EGSecretKey from "../Voting/CryptoSystems/Elgamal/EGSecretKey";
import Utils from "./Utils/Utils";
import Question from "./Question";
import Trustee from "./Trustee";

const {SHA256} = require("sha2");

export default class Election {

    /**
     *
     */
    constructor() {
        this.slug = null;

        /** @type ?EGPublicKey */
        this.public_key = null;
        /** @type ?EGSecretKey */
        this.private_key = null;
        /** @type Boolean */
        this.is_test_public_key = false;

        this.openreg = null;
        this.voters_hash = null;
        /** @type ?Date */
        this.frozen_at = null;
        /** @type ?Boolean */
        this.is_auth_user_admin = null;
        /** @type ?Boolean */
        this.is_auth_user_trustee = null;
        /** @type Boolean */
        this.is_private = null;

        /** @type Object */
        this.admin = null;
        /** @type String */
        this.admin_name = null;
        /** @type Boolean */
        this.is_auth_user_admin = null;

        /** @type ?Date */
        this.archived_at = null;
        /** @type ?Date */
        this.featured_at = null;
        /** @type ?Date */
        this.frozen_at = null;

        /** @type String */
        this.description = null;
        /** @type String */
        this.election_type = null;

        /** @type Boolean */
        this.randomize_answer_order = null;

        this.encrypted_tally = null;
        /** @type String */
        this.hash = null;
        /** @type String */
        this.info_url = null;
        /** @type Boolean */
        this.is_private = null;
        /** @type Boolean */
        this.has_system_trustee = null;

        /** @type Array */
        this.issues = [];

        /** @type String */
        this.name = null;
        /** @type String */
        this.slug = null;
        /** @type Trustee[] */
        this.trustees = null;

        /** @type Question[] */
        this.questions = null;

        /** @type Boolean */
        this.ready_for_decryption_combination = null;
        /** @type Boolean */
        this.result = null;

        /** @type ?Date */
        this.result_released_at = null;
        /** @type Boolean */
        this.tallying_started_at = null;

        /** @type Number */
        this.trustee_count = null;

        /** @type String */
        this.url = null;

        /** @type Boolean */
        this.use_voter_aliases = false;
        /** @type ?Date */
        this.voting_ends_at = null;
        /** @type ?Date */
        this.voting_extended_until = null;
        /** @type ?Date */
        this.voting_has_stopped = null;
        /** @type ?Date */
        this.voting_started_at = null;
        /** @type ?Date */
        this.voting_starts_at = null;

    }

    /**
     *
     * @param slug : String
     * @return {Promise<Election>}
     */
    static fetch(slug) {
        return axios.get(BASE_URL + '/api/elections/' + slug)
            .then(response => {
                return Election.fromJSONObject(response.data);
            });
    }

    /**
     *
     * @return {Promise<Election>}
     */
    fetchTrustees() {
        let self = this;
        return axios.get(BASE_URL + '/api/elections/' + this.slug + '/trustees')
            .then(response => {
                self.trustees = response.data.map(t => {
                    return Trustee.fromJSONObject(t);
                });
                return self;
            });
    }

    /**
     *
     * @return {Promise<Election>}
     */
    update() {
        return axios.put(BASE_URL + '/api/elections/' + this.slug, this.toJSONObject())
            .then(response => {
                return Election.fromJSONObject(response.data);
            });
    }

    /**
     *
     * @return {Promise<Election>}
     */
    store() {
        return axios.post(BASE_URL + '/api/elections', this.toJSONObject())
            .then(response => {
                return Election.fromJSONObject(response.data);
            });
    }

    /**
     *
     * @return {Promise<Election>}
     */
    copy_election() {
        let url = BASE_URL + '/api/elections/' + this.slug + '/copy';
        return axios.post(url)
            .then(response => {
                return Election.fromJSONObject(response.data);
            });
    }

    /**
     *
     * @param featured : Boolean
     * @return {Promise<Election>}
     */
    set_featured(featured) {
        let url = BASE_URL + '/api/elections/' + this.slug + '/feature';
        return axios.post(url, {featured: featured})
            .then(response => {
                return Election.fromJSONObject(response.data);
            });
    }

    /**
     *
     * @param archived : Boolean
     * @return {Promise<Election>}
     */
    set_archived(archived) {
        let url = BASE_URL + '/api/elections/' + this.slug + '/archive';
        return axios.post(url, {archived: archived})
            .then(response => {
                return Election.fromJSONObject(response.data);
            });
    }

    /**
     *
     * @return {Promise<Election>}
     */
    freeze() {
        return axios.post(BASE_URL + '/api/elections/' + this.slug + '/freeze')
            .then(response => {
                return Election.fromJSONObject(response.data.election);
            });
    }

    // ##########################################################

    /**
     *
     * @return {{}}
     */
    toJSONObject() {
        let json_obj = {
            slug: this.slug,
            description: this.description,
            name: this.name,
            public_key: this.public_key.toJSONObject(),
            questions: this.questions,
            openreg: this.openreg,
            voters_hash: this.voters_hash,
            use_voter_aliases: this.use_voter_aliases,
            voting_starts_at: this.voting_starts_at,
            voting_ends_at: this.voting_ends_at
        };

        return Utils.object_sort_keys(json_obj);
    }

    /**
     *
     * @param d
     * @return {Election}
     */
    static fromJSONObject(d) {

        // TODO check, here we don't set election.hash,  election.hash

        let el = new Election();

        el.name = d.name;
        el.description = d.description;
        el.slug = d.slug;

        el.admin = d.admin;
        el.admin_name = d.admin_name;

        // generated
        el.is_auth_user_admin = d.is_auth_user_admin;
        el.is_auth_user_trustee = d.is_auth_user_trustee;
        el.issues = d.issues;
        el.trustee_count = d.trustee_count;

        el.openreg = d.openreg; // TODO
        el.voters_hash = d.voters_hash;
        el.use_voter_aliases = d.use_voter_aliases;
        el.randomize_answer_order = d.randomize_answer_order;

        el.voting_starts_at = d.voting_starts_at;
        el.voting_ends_at = d.voting_ends_at;
        el.frozen_at = d.frozen_at;
        el.archived_at = d.archived_at;
        el.has_system_trustee = d.has_system_trustee;

        el.trustees = null;
        if (d.trustees) {
            el.trustees = d.trustees.map(t => {
                return Trustee.fromJSONObject(t);
            })
        }

        // questions
        el.questions = [];
        if (d.questions) {
            el.questions = d.questions.map(q => {
                return Question.fromJSONObject(q);
            })
        }

        // public key
        if (d.public_key) {
            el.public_key = Utils.getPublicKeyFromJSONObject(d.public_key);
        } else {
            // a placeholder that will allow hashing;
            el.public_key = EGPublicKey.get_bogus(); // TODO rsa??
            el.is_test_public_key = true;
        }

        if (d.private_key) {
            el.private_key = EGSecretKey.fromJSONObject(d.private_key);
        }

        return el;
    }

    // ##########################################################

    /**
     *
     * @return {string|*}
     */
    get_hash() {
        if (this.hash) { // TODO hash
            return this.hash; // TODO hash
        }
        return SHA256(this.toJSON()).toString("base64");
    }

    // ##########################################################

    /**
     *
     * @return {string}
     */
    toJSON() {
        // FIXME: only way around the backslash thing for now.... how ugly
        //return jQuery.toJSON(this.toJSONObject()).replace(/\//g,"\\/");
        return JSON.stringify(this.toJSONObject());
    }

    /**
     *
     * @param raw_json : string
     * @return {Election}
     */
    static fromJSONString(raw_json) {
        let json_object = JSON.parse(raw_json);

        // let's hash the raw_json
        let election = Election.fromJSONObject(json_object);
        election.hash = SHA256(raw_json).toString("base64");

        return election;
    }

    // ##########################################################

    /**
     *
     * @param election
     * @return {Election}
     */
    setup(election) {
        return Election.fromJSONObject(election);
    }

}
