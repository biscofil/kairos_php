<template>
    <div>
        <d3-network v-if="nodes"
                    ref='net' id="net"
                    :net-nodes="nodes"
                    :net-links="links"
                    :options="options"
                    @node-click='onNodeClicked'/>
    </div>
</template>

<script>
import D3Network from 'vue-d3-network'

export default {
    name: "Network",

    components: {
        D3Network
    },

    data() {
        return {
            nodes: [],
            links: [],
            nodeSize: 30,
            canvas: false,
        }
    },

    computed: {
        options() {
            return {
                force: 3000,
                size: {w: 600, h: 600},
                nodeSize: this.nodeSize,
                nodeLabels: true,
                canvas: this.canvas,
                linkWidth: 3
            }
        }
    },

    mounted() {
        let self = this;
        fetch("/assets/img/server.svg")
            .then(response => response.text())
            .then(text => {
                axios.get(BASE_URL + "/api/p2p")
                    .then(response => {
                        self.links = response.data.map(server => {
                            return {sid: "_me", tid: server.id};
                        }).filter(server => {
                            return server.tid !== "_me";
                        });
                        self.nodes = response.data.map(server => {
                            return {
                                id: server.id,
                                name: server.name + (server.id === "_me" ? " (Me)" : ""),
                                svgSym: text
                            };
                        });
                    });
            })
            .catch(e => {
                console.log(e);
            })
    },

    methods: {
        onNodeClicked(event, node) {
            // node = Object.assign(node, {svgSym: ServerIcon, svgIcon: null, svgObj: null})
            // this.$set(this.nodes, node.index, node)
        }
    }
}
</script>

<style scoped>

#net >>> .title {
    position: absolute;
    text-align: center;
    left: 2em;
}

#net >>> h1, #net >>> a {
    color: #1aad8d;
    text-decoration: none;
}

#net >>> ul.menu {
    list-style: none;
    position: absolute;
    z-index: 100;
    min-width: 20em;
    text-align: left;
}

#net >>> ul.menu li {
    margin-top: 1em;
    position: relative;
}

#net >>> #m-end path, #net >>> #m-start {
    fill: rgba(18, 120, 98, 0.8);
}

#net >>> .title {
    margin-bottom: 3em;
}

#net >>> .link {
    stroke: red
}

</style>
