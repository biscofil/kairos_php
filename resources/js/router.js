import VueRouter from 'vue-router'

import About from "./views/About.vue";
import Docs from "./views/Docs.vue";
import Faq from "./views/Faq.vue";
import Privacy from "./views/Privacy.vue";
import Home from "./views/Home.vue";
import ElectionsVoted from "./views/ElectionsVoted.vue";
// TODO import Verifier from "./views/Verifier/Verifier.vue";
import PermsWhy from "./views/PermsWhy.vue";
import Election from "./views/Election/Election.vue";
import NewElection from "./views/Election/NewElection.vue";
import ElectionQuestions from "./views/Election/ElectionQuestions.vue";
import EditElection from "./views/Election/EditElection.vue";
// TODO import Booth from "./views/Election/Booth/Booth.vue";
import ElectionTrustees from "./views/Election/ElectionTrustees.vue";
import ElectionVoters from "./views/Election/ElectionVoters.vue";
import ElectionsAdministered from "./views/ElectionsAdministered.vue";
import TrusteeHome from "./views/Election/Trustee/TrusteeHome.vue";
import ElectionAuditedBallots from "./views/Election/ElectionAuditedBallots.vue";
import Stats from "./views/Admin/Stats.vue";
import StatsElections from "./views/Admin/StatsElections.vue";
import StatsRecentVotes from "./views/Admin/StatsRecentVotes.vue";
import StatsProblemElections from "./views/Admin/StatsProblemElections.vue";
import ExtendElection from "./views/Election/ExtendElection.vue";
import VotersEmail from "./views/Election/VotersEmail.vue";
import PageNotFound from "./views/PageNotFound";
import {store} from "./store";
import NewBoothWizard from "./views/Election/Booth/NewBoothWizard";
import Network from "./views/Admin/Network";

const router = new VueRouter({
    mode: 'history',
    // TODO use old names
    // TODO validate
    routes: [
        {path: '/', component: Home, name: 'home'},
        {path: '/about', component: About, name: 'about'},
        {path: '/docs', component: Docs, name: 'docs'},
        {path: '/faq', component: Faq, name: 'faq'},
        {path: '/privacy', component: Privacy, name: 'privacy'},
        {path: '/auth/why', component: PermsWhy},
        //
        {path: '/voted-elections', component: ElectionsVoted, name: 'elections@voted', meta: {requires_auth: true}},
        {
            path: '/administered-elections',
            component: ElectionsAdministered,
            name: 'elections@administered',
            meta: {requires_auth: true}
        },
        // stats
        {path: '/admin/network', component: Network, name: "admin@network"},
        {path: '/admin/elections', component: StatsElections, name: "admin@elections"},
        {path: '/admin/recent-votes', component: StatsRecentVotes, name: "admin@recent-votes"},
        {path: '/admin/problem-elections', component: StatsProblemElections, name: "admin@elections-problems"},
        {path: '/admin', component: Stats, name: "admin@home"},
        // election
        {path: '/new-election', component: NewElection, name: "elections@new", meta: {requires_auth: true}},
        // TODO {path: '/elections/:slug/vote', component: Booth, name: "election@vote", meta: {requires_auth: true}},
        {path: '/elections/:slug/vote', component: NewBoothWizard, name: "election@vote", meta: {requires_auth: true}},
        // TODO {path: '/elections/:slug/verifier', name: "election@verify", component: Verifier},
        {path: '/elections/:slug/edit', component: EditElection, name: "election@edit", meta: {requires_auth: true}},
        {
            path: '/elections/:slug/extend',
            component: ExtendElection,
            name: "election@extend",
            meta: {requires_auth: true}
        },
        {path: '/elections/:slug/questions', component: ElectionQuestions, name: "election@questions"},
        {path: '/elections/:slug/voters/email', component: VotersEmail, name: "election@voters@email"},
        {path: '/elections/:slug/voters', component: ElectionVoters, name: "election@voters@list-pretty"},
        {path: '/elections/:slug/trustee', component: TrusteeHome, name: 'election@trustee'},
        {path: '/elections/:slug/trustees', component: ElectionTrustees, name: "election@trustees@view"},
        {path: '/elections/:slug/audited-ballots', component: ElectionAuditedBallots, name: "election@audited-ballots"},
        {path: '/elections/:slug', component: Election, name: "election@view"},
        {path: "*", component: PageNotFound}
    ]
});

router.beforeEach((to, from, next) => {
    if (to.meta.requires_auth) {
        if (!store.getters.isLogged) {
            next({
                path: '/',
                query: {
                    show_login_modal: true,
                    redirect: to.fullPath
                }
            })
        } else {
            next()
        }
    } else {
        next()
    }
});

export default router;

