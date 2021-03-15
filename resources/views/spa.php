<!DOCTYPE html>
<!--[if IE 8]>
<html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js" lang="en"> <!--<![endif]-->

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>Helios</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>

<div id="app">

    <div class="wrapper" v-if="settings">
        <nav class="top-bar">
            <ul class="title-area">
                <!-- Title Area -->
                <li class="name">
                    <h1>
                        <router-link to="/"><img src="/assets/img/tinylogo.png"></router-link>
                    </h1>
                </li>
                <li class="toggle-topbar menu-icon">
                    <a href="javascript:void(0)"><span>menu</span></a>
                </li>
            </ul>
            <section class="top-bar-section">
                <!-- Right Nav Section -->
                <ul class="right">
                    <template v-if="$store.getters.isLogged && $store.getters.user.is_admin">
                        <li><router-link :to="{ name: 'stats@home' } ">Admin</router-link></li>
                        <li class="divider"></li>
                    </template>
                    <li><a href="http://heliosvoting.org">About Helios</a></li>
                </ul>

                <ul>
                    <li>
                        <router-link to="/">{{ settings.SITE_TITLE }}</router-link>
                    </li>
                    <li>
                        <router-link to="/verifier">Verifier</router-link>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <router-link to="/about">About</router-link>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <router-link to="/docs">Docs</router-link>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <router-link to="/faq">FAQ</router-link>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <router-link to="/privacy">Privacy</router-link>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a target="_new" href="https://github.com/benadida/helios-server">Code</a>
                    </li>
                    <li class="divider"></li>
                    <li><a :href="'mailto:' + settings.HELP_EMAIL_ADDRESS">Help!</a></li>
                </ul>
            </section>
        </nav>

        <!-- Main Page Content and Sidebar -->
        <div class="row" id="contentbody">
            <router-view></router-view>
        </div>

        <div class="push"></div>

        <div class="footer" id="footer">
            <span style="float:right;">
                <img v-if="settings.FOOTER_LOGO_URL" :src="settings.FOOTER_LOGO_URL"/>
            </span>

            <div v-if="$store.getters.isLogged">
                logged in as <b>{{ $store.state.user.name }}</b>&nbsp;&nbsp;
                <a class="tiny button" href="javascript:void(0)" @click="$store.dispatch('logout');">logout</a>
                <br/>
            </div>

            <br clear="right"/>
        </div>

    </div>

</div>
<script src="/js/app.js"></script>
</body>
</html>
