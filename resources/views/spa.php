<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Kairos Voting System">
    <meta name="author" content="Filippo Bisconcin">
    <title>Kairos</title>
    <link href="/css/app.css" rel="stylesheet">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
    <link rel="manifest" href="/favicon/site.webmanifest">
    <link rel="mask-icon" href="/favicon/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">
</head>

<body>

<div id="app">

    <header v-if="settings">
        <!-- Fixed navbar -->
        <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
            <router-link class="navbar-brand" :to="{ name: 'home' }">
                <img src="/favicon/favicon-32x32.png" width="30" height="30" class="d-inline-block align-top" alt="">
                Kairos
            </router-link>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse"
                    aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="navbar-collapse collapse" id="navbarCollapse">

                <ul class="navbar-nav mr-auto">

                    <li v-if="$store.getters.isLogged && $store.getters.user.can_create_election" class="nav-item">
                        <router-link class="nav-link" :to="{name: 'elections@new'}">New election</router-link>
                    </li>

                    <li v-if="$store.getters.isLogged && $store.getters.user.is_admin" class="nav-item">
                        <router-link class="nav-link" :to="{ name: 'admin@home' }">Admin</router-link>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="javascript:void(0)" id="dropdown09"
                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <country-flag :country="$root.current_language"></country-flag>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="dropdown09">
                            <a class="dropdown-item" href="javascript:void(0)" v-for="lang in $root.languages"
                               v-if="lang !== $root.current_language" @click="current_language=lang">
                                <country-flag :country="lang"></country-flag>
                            </a>
                        </div>
                    </li>

                </ul>

                <span class="navbar-text">
                    <span v-if="$store.getters.isLogged">
                        Hi <b>{{ $store.state.user.name }}</b>!&nbsp;
                        <span v-if="$store.state.user.is_admin" class="text-success" title="Admin">[A]</span>
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
    <main role="main" :class="main_class" id="contentbody" v-if="settings">
        <router-view></router-view>
    </main>

    <footer class="footer bg-dark" v-if="settings">
        <div class="container">
            <span class="text-muted">Kairos</span>
        </div>
    </footer>

</div>
<script src="/js/jquery.min.js"></script>
<script src="/js/bootstrap.bundle.min.js"></script>
<script src="/js/app.js"></script>
</body>
</html>
