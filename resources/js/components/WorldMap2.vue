<template>
    <div>
        <div class="hello" ref="chartdiv">
        </div>
    </div>
</template>

<script>

import * as am4core from "@amcharts/amcharts4/core";
import * as am4maps from "@amcharts/amcharts4/maps";
import am4geodata_worldLow from "@amcharts/amcharts4-geodata/worldLow";
import am4themes_animated from "@amcharts/amcharts4/themes/animated";

am4core.useTheme(am4themes_animated);

const envelopeSVG = "m 1664,32 v 768 q -32,-36 -69,-66 -268,-206 -426,-338 -51,-43 -83,-67 -32,-24 -86.5,-48.5 Q 945,256 897,256 h -1 -1 Q 847,256 792.5,280.5 738,305 706,329 674,353 623,396 465,528 197,734 160,764 128,800 V 32 Q 128,19 137.5,9.5 147,0 160,0 h 1472 q 13,0 22.5,9.5 9.5,9.5 9.5,22.5 z m 0,1051 v 11 13.5 q 0,0 -0.5,13 -0.5,13 -3,12.5 -2.5,-0.5 -5.5,9 -3,9.5 -9,7.5 -6,-2 -14,2.5 H 160 q -13,0 -22.5,-9.5 Q 128,1133 128,1120 128,952 275,836 468,684 676,519 682,514 711,489.5 740,465 757,452 774,439 801.5,420.5 829,402 852,393 q 23,-9 43,-9 h 1 1 q 20,0 43,9 23,9 50.5,27.5 27.5,18.5 44.5,31.5 17,13 46,37.5 29,24.5 35,29.5 208,165 401,317 54,43 100.5,115.5 46.5,72.5 46.5,131.5 z m 128,37 V 32 q 0,-66 -47,-113 -47,-47 -113,-47 H 160 Q 94,-128 47,-81 0,-34 0,32 v 1088 q 0,66 47,113 47,47 113,47 h 1472 q 66,0 113,-47 47,-47 47,-113 z";

export default {

    name: "WorldMap2",

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
            cities: null,
            chart: null,
            lineSeries: null,
            shadowLineSeries: null,
            created_nodes: {},
        }
    },

    mounted() {
        console.log("WorldMap2 mounted");

        let self = this;
        let chart = am4core.create(this.$refs.chartdiv, am4maps.MapChart);

        chart.geodata = am4geodata_worldLow;
        chart.projection = new am4maps.projections.Miller();
        chart.homeZoomLevel = 1;
        chart.homeGeoPoint = {latitude: 45, longitude: 12};

        // Create map polygon series
        let polygonSeries = chart.series.push(new am4maps.MapPolygonSeries());
        polygonSeries.useGeodata = true;
        polygonSeries.mapPolygons.template.fill = chart.colors.getIndex(0).lighten(0.5);
        polygonSeries.mapPolygons.template.nonScalingStroke = true;

        // Add line bullets
        this.cities = chart.series.push(new am4maps.MapImageSeries());
        this.cities.mapImages.template.nonScaling = true;

        let city = this.cities.mapImages.template.createChild(am4core.Circle);
        city.radius = 6;
        city.fill = am4core.color("#f00");
        city.strokeWidth = 2;
        city.stroke = am4core.color("#fff");

        this.nodes
            .filter(node => {
                return node.gps;
            })
            .forEach(node => {
                this.created_nodes[node.id] = {
                    node: self.addNode({
                        "latitude": node.gps.coordinates[1],
                        "longitude": node.gps.coordinates[0]
                    }, node.name),
                    outMapLines: {}
                };
            });

        // Add lines
        this.lineSeries = chart.series.push(new am4maps.MapLineSeries()); // MapArcSeries
        this.lineSeries.mapLines.template.shortestDistance = true;
        this.lineSeries.mapLines.template.line.strokeWidth = 2;
        this.lineSeries.mapLines.template.line.strokeOpacity = 0.5;
        this.lineSeries.mapLines.template.line.stroke = city.fill;
        this.lineSeries.mapLines.template.line.nonScalingStroke = true;
        this.lineSeries.mapLines.template.line.strokeDasharray = "1,1";
        this.lineSeries.zIndex = 10;

        this.shadowLineSeries = chart.series.push(new am4maps.MapLineSeries());
        this.shadowLineSeries.mapLines.template.shortestDistance = true;
        this.shadowLineSeries.mapLines.template.line.strokeOpacity = 0;
        this.shadowLineSeries.mapLines.template.line.nonScalingStroke = true;
        this.shadowLineSeries.zIndex = 5;

        // links
        Object.keys(this.nodes).forEach(key1 => {
            Object.keys(this.nodes).forEach(key2 => {

                if (key1 < key2) { // k1 < k2

                    let node1 = this.nodes[key1];
                    let node2 = this.nodes[key2];

                    if (node1.gps && node2.gps) {
                        let _node1 = this.created_nodes[node1.id].node;
                        let _node2 = this.created_nodes[node2.id].node;

                        this.created_nodes[node1.id].outMapLines[node2.id] = self.addLine(_node1, _node2);
                    }

                }

            });
        });

        console.log(this.created_nodes);

        this.chart = chart;
    },

    methods: {

        addNode(coords, title) {
            let city = this.cities.mapImages.create();
            city.latitude = coords.latitude;
            city.longitude = coords.longitude;
            city.tooltipText = title;
            return city;
        },

        addLine(from, to) {
            let line = this.lineSeries.mapLines.create();
            line.imagesToConnect = [from, to];
            let shadowLine = this.shadowLineSeries.mapLines.create();
            shadowLine.imagesToConnect = [from, to];
            return line;
        },

        flyPlane(from_domain, to_domain) {

            // get id of both by their domains
            let from = this.servers.find(s => {
                return s.domain === from_domain;
            }).id;

            let to = this.servers.find(s => {
                return s.domain === to_domain;
            }).id;

            // get path and direction
            let currentLine = null;
            if (from < to) {
                currentLine = this.created_nodes[from].outMapLines[to];
                from = 0;
                to = 1;
            } else {
                currentLine = this.created_nodes[to].outMapLines[from];
                from = 1;
                to = 0;
            }

            let plane = currentLine.lineObjects.create();

            plane.position = 0;
            plane.width = 48;
            plane.height = 48;
            plane.adapter.add("scale", function (scale, target) {
                return 0.5 * (1 - (Math.abs(0.5 - target.position)));
            });

            let planeImage = plane.createChild(am4core.Sprite);
            planeImage.scale = 0.02;
            planeImage.horizontalCenter = "middle";
            planeImage.verticalCenter = "middle";
            planeImage.path = envelopeSVG;
            planeImage.fill = this.chart.colors.getIndex(2).brighten(-0.2);
            planeImage.strokeOpacity = 0;


            // Get current line to attach plane to
            plane.mapLine = currentLine;
            plane.parent = this.lineSeries;

            plane.animate({
                from: from,
                to: to,
                property: "position"
            }, 2500, am4core.ease.sinInOut).events.on("animationended", a => {
                currentLine.lineObjects.removeValue(plane);
            });

        },

        flyTo(server) {
            this.chart.homeZoomLevel = 2.5;
            this.chart.homeGeoPoint = {latitude: server.gps.coordinates[1], longitude: server.gps.coordinates[0]};
            this.chart.goHome();
        }
    },

    beforeDestroy() {
        if (this.chart) {
            this.chart.dispose();
        }
    }

}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.hello {
    width: 100%;
    height: 500px;
}
</style>
