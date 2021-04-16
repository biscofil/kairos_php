<template>
    <div id="globus" style="width:100%;height:100%"></div>
</template>

<script>

import {Entity} from '../github/openglobus/src/og/entity';
import {Globe, LonLat} from '../github/openglobus';
import {Vector, XYZ} from '../github/openglobus/src/og/layer';

export default {
    name: "GlobusMap",

    props: {
        nodes: {},
        links: {}
    },

    data() {

        return {
            globe: null,
            pointLayer: null,
            pathLonLat: [],
            pathColors: []
        }
    },

    mounted() {

        const osm = new XYZ("OpenStreetMap", {
            // specular: [0.0003, 0.00012, 0.00001],
            // shininess: 20,
            // diffuse: [0.89, 0.9, 0.83],
            isBaseLayer: true,
            url: "//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
            visibility: true,
            attribution: 'Data @ OpenStreetMap contributors, ODbL'
        });

        this.pointLayer = new Vector("points", {
            'clampToGround': true,
            'visibility': true
        });

        this.globe = new Globe({
            "target": "globus",
            "name": "Earth",
            // "terrain": new GlobusTerrain(),
            "layers": [osm, this.pointLayer]
        });

        this.globe.planet.setHeightFactor(0);
        this.globe.planet.setRatioLod(0.8);

        // this.globe.planet.viewExtentArr([8.08, 46.72, 8.31, 46.75]);
    },

    watch: {
        nodes() {
            this.updateNodesLinks();
        },
        links() {
            this.updateNodesLinks();
        }
    },

    methods: {

        updateNodesLinks() {
            let entities = [];

            // nodes
            this.nodes.forEach(node => {
                if (node.gps) {
                    entities.push(new Entity({
                        lonlat: [node.gps.coordinates[0], node.gps.coordinates[1]],
                        label: {
                            text: node.name,
                            // outline: 0.77,
                            // outlineColor: "rgba(255,255,255,.4)",
                            size: 10,
                            color: "black",
                            // offset: [10, -2]
                        },
                        // billboard: {
                        //     // src: "./marker.png",
                        //     width: 64,
                        //     height: 64,
                        //     offset: [0, 32]
                        // }
                    }));
                }
            });

            // links
            this.nodes.forEach(node1 => {
                this.nodes.forEach(node2 => {
                    if (node1.id !== node2.id && node1.gps && node2.gps) {

                        let p1 = new LonLat(node1.gps.coordinates[0], node1.gps.coordinates[1]);
                        let p2 = new LonLat(node2.gps.coordinates[0], node2.gps.coordinates[1]);

                        let path = [p1];
                        for (let i = 1; i <= 100; i++) {
                            let c = i / 100;
                            path.push(new LonLat(
                                p1.lon + ((p2.lon - p1.lon) * c),
                                p1.lat + ((p2.lat - p1.lat) * c)
                            ));
                        }

                        entities.push(new Entity({
                            'polyline': {
                                'pathLonLat': [path],
                                'color': "#ff3b3b",
                                'thickness': 4,
                            }
                        }));
                    }
                });
            });

            // done
            this.pointLayer.setEntities(entities);
        },
    }
}
</script>

<style scoped>
@import '../github/openglobus/css/og.css';
</style>
