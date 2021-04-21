<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Kairos Voting System">
    <meta name="author" content="Filippo Bisconcin">
    <!--    <link rel="icon" href="/docs/4.0/assets/img/favicons/favicon.ico">-->
    <title>Kairos</title>
    <link href="/css/app.css" rel="stylesheet">
</head>

<body>

<div id="app">

    <header v-if="settings">
        <!-- Fixed navbar -->
        <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
            <router-link class="navbar-brand" :to="{ name: 'home' }">Kairos</router-link>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse"
                    aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarCollapse">

                <ul class="navbar-nav mr-auto">

                    <li v-if="$store.getters.isLogged && $store.getters.user.can_create_election" class="nav-item">
                        <router-link class="nav-link" :to="{name: 'elections@new'}">New election</router-link>
                    </li>

                    <li v-if="$store.getters.isLogged && $store.getters.user.is_admin" class="nav-item">
                        <router-link class="nav-link" :to="{ name: 'admin@home' }">Admin</router-link>
                    </li>
                </ul>

                <span class="navbar-text">
                    <span v-if="$store.getters.isLogged">
                        Hi <b>{{ $store.state.user.name }}</b>!
                        <a class="btn btn-sm btn-danger" href="javascript:void(0)" @click="$store.dispatch('logout');">Logout</a>
                    </span>
                    <span v-else>
                         <LoginBox :default_auth_system="$root.login_box.default_auth_system"
                                   :enabled_auth_systems="$root.login_box.enabled_auth_systems"
                                   color="white"/>
                    </span>
                </span>

            </div>
        </nav>
    </header>

    <!-- Begin page content -->
    <main role="main" class="container-fluid" id="contentbody" v-if="settings">
        <router-view></router-view>
    </main>

    <footer class="footer" v-if="settings">
        <div class="container">
            <span class="text-muted">Kairos</span>
        </div>
    </footer>


</div>
<script src="/js/app.js"></script>
</body>
</html>
