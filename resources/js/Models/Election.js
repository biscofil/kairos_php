import EGPublicKey from "../Voting/CryptoSystems/ElGamal/EGPublicKey";
import EGSecretKey from "../Voting/CryptoSystems/ElGamal/EGSecretKey";
import Utils from "./Utils/Utils";
import Question from "./Question";
import Trustee from "./Trustee";
import Cryptosystem from "../Voting/CryptoSystems/Cryptosystem";

var moment = require('moment'); // require

const {SHA256} = require("sha2");

export default class Election {

    /**
     *
     */
    constructor() {
        this.slug = null;

        /** @type ?Number */
        this.admin_id = null;

        this.cryptosystem = null;
        this.anonymization_method = null;

        this.help_email = null;
        this.info_url = null;

        this.min_peer_count_t = null;

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

        /** @type Number */
        this.trustee_count = null;

        /** @type String */
        this.url = null;

        /** @type Boolean */
        this.use_voter_aliases = false;

        /** @type ?Date */
        this.voting_extended_until = null;
        /** @type ?Date */
        this.voting_has_stopped = null;

        /** @type ?moment */
        this.frozen_at = null;
        /** @type ?moment */
        this.voting_starts_at = null;
        /** @type ?moment */
        this.voting_started_at = null;
        /** @type ?moment */
        this.voting_ends_at = null;
        /** @type ?moment */
        this.voting_ended_at = null;
        /** @type ?Date */
        this.result_released_at = null;
        /** @type Boolean */
        this.tallying_started_at = null;
        /** @type ?moment */
        this.archived_at = null;
        /** @type ?moment */
        this.featured_at = null;

        /** @type String */
        this.output_database_filename_url = null;

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
     * @param old_slug : String
     * @return {Promise<Election>}
     */
    update(old_slug) {
        return axios.put(BASE_URL + '/api/elections/' + old_slug, this.toJSONObject())
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
                return Election.fromJSONObject(response.data);
            });
    }

    // ##########################################################

    /**
     *
     * @return {{}}
     */
    toJSONObject() {
        let json_obj = {
            cryptosystem: this.cryptosystem,
            anonymization_method: this.anonymization_method,
            slug: this.slug,
            help_email: this.help_email,
            info_url: this.info_url,
            min_peer_count_t: this.min_peer_count_t,
            description: this.description,
            name: this.name,
            public_key: this.public_key ? this.public_key.toJSONObject() : null,
            questions: this.questions,
            openreg: this.openreg,
            voters_hash: this.voters_hash,
            use_voter_aliases: this.use_voter_aliases,
            voting_starts_at: this.voting_starts_at,//? this.voting_starts_at : null,
            voting_started_at: this.voting_started_at,//? this.voting_starts_at : null,
            voting_ends_at: this.voting_ends_at,//? this.voting_ends_at.format() : null
            voting_ended_at: this.voting_ended_at,//? this.voting_ends_at.format() : null
            output_database_filename_url: this.output_database_filename_url,
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

        el.cryptosystem = d.cryptosystem;
        el.anonymization_method = d.anonymization_method;

        el.name = d.name;
        el.admin_id = d.admin_id;
        el.description = d.description;
        el.slug = d.slug;

        el.help_email = d.help_email;
        el.info_url = d.info_url;

        el.admin = d.admin;
        el.admin_name = d.admin_name;

        // generated
        el.is_auth_user_admin = d.is_auth_user_admin;
        el.is_auth_user_trustee = d.is_auth_user_trustee;
        el.issues = d.issues;
        el.min_peer_count_t = d.min_peer_count_t;
        el.trustee_count = d.trustee_count;

        el.openreg = d.openreg; // TODO
        el.voters_hash = d.voters_hash;
        el.use_voter_aliases = d.use_voter_aliases;
        el.randomize_answer_order = d.randomize_answer_order;

        el.frozen_at = d.frozen_at;//? moment(d.frozen_at) : null;
        el.voting_starts_at = d.voting_starts_at;//? moment(d.voting_starts_at) : null;
        el.voting_started_at = d.voting_started_at;//? moment(d.voting_starts_at) : null;
        el.voting_ends_at = d.voting_ends_at;//? moment(d.voting_ends_at) : null;
        el.voting_ended_at = d.voting_ended_at;//? moment(d.voting_ends_at) : null;

        el.archived_at = d.archived_at;//? moment(d.archived_at) : null;

        el.output_database_filename_url = d.output_database_filename_url;

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
            // let pkClass = this.getCryptoSystemClass().getPublicKeyClass();
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
     * @return {RSA|ElGamal}
     */
    getCryptoSystemClass(){
        return Cryptosystem.getCryptosystemClassFromIdentifier(this.cryptosystem);
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
