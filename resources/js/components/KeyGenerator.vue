<template>

    <div v-if="election">

        <h3 class="title">Key Setup</h3>

        <p>
            As a trustee, it's time to set up your key for this election.
            <label>
                <input type="radio" name="mode" value="new" v-model="mode" checked>
                Generate Election Keys
            </label>
            <label>
                <input type="radio" name="mode" value="reuse" v-model="mode">
                Reuse key
            </label>
        </p>

        <textarea :readonly="mode == 'new'" v-model="public_key"></textarea>
        <textarea :readonly="mode == 'new'" v-model="private_key"></textarea>
        <button style="font-size:16pt;" @click="upload_pk">Upload PK</button>

        <div v-show="show_generator">
            <span v-show="show_buttons">
                <button @click="generate_keypair">Generate Election Keys</button>
            <br/>
            </span>
            If you've already generated a keypair, you can
            <a href="javascript:void(0)" @click="show_key_reuse">reuse it</a>.
        </div>

        <div v-show="show_reuse">
            <h3>Reusing a Key</h3>
            <p>
                Enter your complete secret key below:
            </p>
            <textarea cols="80" rows="5" wrap="soft" v-model="secret_key"></textarea>
            <br/>
            <button @click="reuse_key">Reuse</button>
        </div>

        <div v-show="show_sk_download">
            <h3>Your Secret Key</h3>
            <span v-show="show_clear_button">
                Your key has been generated, but you may choose to<br/><a href="javascript:void(0)" @click="clear_keys">
                clear it from memory and start from scratch</a> if you prefer.<br/>
            </span>
            <p>
                <button style="font-size:16pt;" @click="show_my_secret_key">Show my secret key</button>
            </p>
        </div>

        <div style="display:none;" v-if="show_pk_content">
            <p>
                Bellow is your trustee secret key content. Please copy its content and save it securely. <br>
                You can also click to dowload it to a file.
                And please don't lose it! Otherwise it will not be possible to decrypt the election tally.<br>
            </p>
            <textarea v-if="show_sk_content" v-model="sk_content" rows="5" wrap="soft" cols="50"
                      style="height: 25em;"></textarea>
        </div>

        <div style="display:none;" v-if="show_pk_link">
            <p>
                <a id="download_to_file" href="javascript:void(0)" @click="download_sk_to_file">
                    download private key to a file
                </a>
            </p>
            <p>
                <a href="javascript:void(0)" @click="show_pk">ok, I've saved the key, let's move on</a>
            </p>
        </div>

        <div v-show="show_pk_form">
            <h3>Your Public Key</h3>
            <p>
                It's time to upload the public key to the server.
            </p>
            <p>
                The fingerprint of your public key is:
                <tt style="font-size: 1.5em; font-weight: bold;">{{ pk_hash }}</tt>.<br/>
                You may want to save this to confirm that your public key was properly stored by the server.<br/>
                (Your public key is not currently being displayed because you do not need to save it, the fingerprint is
                sufficient.)
            </p>
            <!-- style="display:none;" -->
            <textarea id="pk_textarea" v-model="public_key_json" cols="80" rows="10"></textarea>
            <button @click="upload_pk">Upload your public key</button>
        </div>

    </div>

</template>

<script>

import Election from "../Models/Election";
import ElgamalParams from "../Voting/CryptoSystems/Elgamal/ElgamalParams";
import EGProof from "../Voting/CryptoSystems/Elgamal/EGProof";

export default {
    name: "KeyGenerator",

    props: {
        election: {
            required: true,
            type: Election
        },
        trustee: {},
    },

    data() {
        return {
            mode: "new",
            public_key: '',
            private_key: '',
            //
            show_generator: true,
            show_reuse: false,
            //
            sk_content: '',
            show_sk_content: true,
            show_sk_download: false,
            secret_key: null,
            _SECRET_KEY: null,
            //
            show_pk_content: true,
            show_pk_form: false,
            show_pk_link: true,
            pk_hash: null,
            //
            show_buttons: true,
            show_clear_button: false,
            //
            public_key_json: null,
            //
            ELGAMAL_PARAMS: null, // todo remove
            eg_params_json: null,
            // TODO moduleWasmInstance: null
        }
    },

    __mounted() {

        this.clear_keys();

        let self = this;

        // get some more server-side randomness for keygen
        this.$http.get(BASE_URL + "/api/elections/" + this.election.slug + "/trustee/keygenerator")
            .then(response => {
                self.eg_params_json = response.data.eg_params_json;

                // get some more server-side randomness for keygen TODO remove randomness
                this.$http.get(BASE_URL + "/api/elections/" + this.election.slug + "/get-randomness")
                    .then(response => {
                        console.log(self.eg_params_json);

                        // console.log(ElGamal.Params.fromJSONObject(self.eg_params_json));
                        // sjcl.random.addEntropy(response.data.randomness);
                        // BigInt.setup(function () {
                        //   self.ELGAMAL_PARAMS = ElGamal.Params.fromJSONObject(self.eg_params_json);
                        //   self.show_generator = true;
                        // });
                    })
                    .catch(e => {
                        console.log(e);
                        self.$toastr.error("Error");
                    });
            })
            .catch(e => {
                console.log(e);
                self.$toastr.error("Error");
            });
    },

    methods: {

        generate_keypair() {
            this.show_buttons = false;

            let keypair = ElgamalParams.fromPublicKey(this.election.public_key).generate();
            console.log(keypair);
            this.public_key = JSON.stringify(keypair.pk.toJSONObject());
            this.private_key = JSON.stringify(keypair.toJSONObject());

            // let proof = EGProof.generate(
            //     keypair.pk.g,
            //     // TODO
            //     keypair.x,
            //     keypair.pk.p,
            //     keypair.pk.q);
            // console.log(proof);

        },

        reuse_key() {
            // TODO this._SECRET_KEY = EGSecretKey.fromJSONObject(JSON.parse(this.secret_key)); // TODO
            this.show_reuse = false;
            this.setup_public_key_and_proof();
            this.show_pk();
        },

        upload_pk() {
            let self = this;
            this.$http.post(BASE_URL + '/api/elections/' + this.election.slug + '/trustee/upload-pk', {
                public_key_pok: JSON.stringify({
                    "public_key": self.public_key,
                    "pok": "12124124" // TODO
                })
            })
                .then(response => {
                    self.$toastr.success("Ok");
                })
                .catch(e => {
                    self.$toastr.error("Error");
                });
        },

        setup_public_key_and_proof() {
            // generate PoK of secret key
            // TODO t let _PROOF = this.moduleWasmInstance.generate_proof(this._SECRET_KEY);
            let _PUBLIC_KEY = this._SECRET_KEY.pk;

            this.public_key_json = JSON.stringify({
                'pok': _PROOF,
                'public_key': _PUBLIC_KEY
            });

            // TODO t this.pk_hash = this.moduleWasmInstance.b64_sha256(JSON.stringify(_PUBLIC_KEY));

            this.show_clear_button = true;
            this.show_sk();
        },

        show_my_secret_key() {
            this.show_pk_content = true;
            this.sk_content = JSON.stringify(this._SECRET_KEY);
            this.show_pk_link = true;
        },

        download_sk_to_file() {
            let filename = 'trustee_key_for_' + this.election.name + '.txt';
            let element = document.createElement('a');
            element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(JSON.stringify(this._SECRET_KEY)));
            element.setAttribute('download', filename);
            element.style.display = 'none';
            // TODO document.body.appendChild(element);
            element.click();
            // TODO document.body.removeChild(element);
        },

        show_sk() {
            this.show_sk_download = true;
        },

        show_pk() {
            this.show_sk_download = false;
            this.show_pk_content = false;
            this.show_pk_form = true;
        },

        clear_keys() {
            this.show_sk_download = false;
            this.show_pk_form = false;
            this.show_buttons = true;
            this.show_clear_button = false;
            this.show_reuse = false;
        },

        show_key_reuse() {
            this.show_generator = false;
            this.show_reuse = true;
        },
    }

}
</script>
