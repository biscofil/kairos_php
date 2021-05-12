<template>

    <div>

        <ul>
            <li v-for="server in servers">
                <country-flag v-if="server.country_code" :country='server.country_code'/>
                <a href="javascript:void(0)" @click="flyTo(server.gps)">{{ server.name }}</a>
            </li>
        </ul>

        <hr>

        <div id="globus" style="width:100%;height:500px"></div>

    </div>
</template>

<script>

import {Entity} from '../github/openglobus/src/og/entity';
import {Globe, LonLat} from '../github/openglobus';
import {Vector, XYZ} from '../github/openglobus/src/og/layer';
import tinygradient from "tinygradient";
import CountryFlag from 'vue-country-flag';

const arc = require('arc');

export default {
    name: "GlobusMap",

    components: {
        CountryFlag
    },

    props: {
        servers: {
            default: [],
            type: Array
        },
        nodes: {type: Array},
        links: {type: Array}
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
                            size: 10,
                            color: "black",
                        },
                    }));
                }
            });

            const steps = 100;
            let colors = tinygradient(['#ff3b3b', '#FD743C', '#2c9700', '#008eb1']).rgb(steps)
                .map(color => {
                    return [color.toRgb().r / 255, color.toRgb().g / 255, color.toRgb().b / 255, 1];
                });

            // links
            Object.keys(this.nodes).forEach(key1 => {
                Object.keys(this.nodes).forEach(key2 => {

                    let node1 = this.nodes[key1];
                    let node2 = this.nodes[key2];

                    if (key1 < key2 && node1.gps && node2.gps) {

                        let path = [];
                        let geodesic = true;
                        if (geodesic) {
                            let start = {x: node1.gps.coordinates[0], y: node1.gps.coordinates[1]};
                            let end = {x: node2.gps.coordinates[0], y: node2.gps.coordinates[1]};
                            let generator = new arc.GreatCircle(start, end, {});
                            let line = generator.Arc(steps, {});
                            line.geometries.forEach(geometry => {
                                geometry.coords.forEach(pts => {
                                    path.push(new LonLat(pts[0], pts[1]));
                                });
                            });

                        } else {
                            let p1 = new LonLat(node1.gps.coordinates[0], node1.gps.coordinates[1]);
                            let p2 = new LonLat(node2.gps.coordinates[0], node2.gps.coordinates[1]);
                            path.push(p1);
                            for (let i = 1; i <= 100; i++) {
                                let c = i / 100;
                                path.push(new LonLat(
                                    p1.lon + ((p2.lon - p1.lon) * c),
                                    p1.lat + ((p2.lat - p1.lat) * c)
                                ));
                            }
                        }

                        entities.push(new Entity({
                            'polyline': {
                                'color': "#ff3b3b",
                                'pathLonLat': [path],
                                'pathColors': [colors],
                                'thickness': 4,
                            }
                        }));
                    }
                });
            });

            // done
            this.pointLayer.setEntities(entities);
        },

        flyTo(gps) {
            const DIST = 1000000; // 2000;
            let viewPoint = new LonLat(gps.coordinates[0], gps.coordinates[1]); // TODO
            let ell = this.globe.planet.ellipsoid;
            this.globe.planet.camera.flyDistance(ell.lonLatToCartesian(viewPoint), DIST);
        }

    }
}
</script>

<style scoped>
@import '../github/openglobus/css/og.css';
</style>
