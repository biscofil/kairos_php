<template>
    <div v-if="election">
        <h3 class="title">New Trustee
            <a href="javascript:void(0)" @click="closeModal" class="brackets_around">cancel</a>
        </h3>

        <p>
            Adding your own trustee requires a good bit more work to tally the election.
            <br><br>
            You will need to have trustees generate keypairs and safeguard their secret key.
            <br><br>
            If you are not sure what that means, we strongly recommend clicking Cancel and letting Helios tally the
            election for you.
        </p>

        <div class="form">
            <label>
                Email:
                <input type="email" name="email" size="60" v-model="email"/>
            </label>
            <br/>
            <label>
                Url:
                <input type="url" name="url" size="60" v-model="url"/>
            </label>
            <br/>
            <button @click="submit">Add Trustee</button>
        </div>
    </div>
</template>

<script>

import {EventBus} from "../event-bus";

export default {
    name: "NewTrusteeModal",

    props: {
        election: {
            required: true,
            type: Object
        },
    },

    data() {
        return {
            url: "",
            email: "",
        }
    },

    methods: {
        submit() {
            let self = this;
            this.$http.post(BASE_URL + '/api/elections/' + this.election.slug + '/trustees', {
                url: this.url,
                email: this.email,
            })
                .then(response => {
                    EventBus.$emit('addedTrustee', response.data.election);
                    self.$toastr.success("Done");
                    self.closeModal();
                })
                .catch(e => {
                    self.$toastr.error("Error");
                });
        },

        closeModal() {
            this.$emit('close');
        }
    }
}
</script>

<style scoped>

</style>
