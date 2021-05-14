<template>
    <div>
        <!--        <button @click="flyPlane">FLY</button>-->
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

// Define marker path
let targetSVG = "M9,0C4.029,0,0,4.029,0,9s4.029,9,9,9s9-4.029,9-9S13.971,0,9,0z M9,15.93 c-3.83,0-6.93-3.1-6.93-6.93S5.17,2.07,9,2.07s6.93,3.1,6.93,6.93S12.83,15.93,9,15.93 M12.5,9c0,1.933-1.567,3.5-3.5,3.5S5.5,10.933,5.5,9S7.067,5.5,9,5.5 S12.5,7.067,12.5,9z";

const planeSVG = "m2,106h28l24,30h72l-44,-133h35l80,132h98c21,0 21,34 0,34l-98,0 -80,134h-35l43,-133h-71l-24,30h-28l15,-47";
const planeShadowSVG = "m2,106h28l24,30h72l-44,-133h35l80,132h98c21,0 21,34 0,34l-98,0 -80,134h-35l43,-133h-71l-24,30h-28l15,-47";

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
            planeImage: null,
            shadowPlaneImage: null,
            shadowPlane: null,
            cities: null,
            chart: null,
            plane: null,
            lineSeries: null,
            shadowLineSeries: null,
            // Plane animation
            currentLine: 0,
            direction: 1,
        }
    },

    mounted() {
        console.log("WorldMap2 mounted");

        let self = this;
        let chart = am4core.create(this.$refs.chartdiv, am4maps.MapChart);

        chart.geodata = am4geodata_worldLow;
        chart.projection = new am4maps.projections.Miller();
        chart.homeZoomLevel = 2.5;
        chart.homeGeoPoint = {latitude: 45, longitude: 12};

        // Create map polygon series
        let polygonSeries = chart.series.push(new am4maps.MapPolygonSeries());
        polygonSeries.useGeodata = true;
        polygonSeries.mapPolygons.template.fill = chart.colors.getIndex(0).lighten(0.5);
        polygonSeries.mapPolygons.template.nonScalingStroke = true;
        // polygonSeries.exclude = ["AQ"];

        // Add line bullets
        this.cities = chart.series.push(new am4maps.MapImageSeries());
        this.cities.mapImages.template.nonScaling = true;

        let city = this.cities.mapImages.template.createChild(am4core.Circle);
        city.radius = 6;
        city.fill = am4core.color("#f00"); // chart.colors.getIndex(0).brighten(-0.2);
        city.strokeWidth = 2;
        city.stroke = am4core.color("#fff");

        let _created_nodes = {};
        this.nodes
            .filter(node => {
                return node.gps;
            })
            .forEach(node => {
                _created_nodes[node.id] = self.addNode({
                    "latitude": node.gps.coordinates[1],
                    "longitude": node.gps.coordinates[0]
                }, node.name);
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
                let node1 = this.nodes[key1];
                let node2 = this.nodes[key2];
                let _node1 = _created_nodes[node1.id];
                let _node2 = _created_nodes[node2.id];
                if (key1 < key2 && node1.gps && node2.gps) {
                    self.addLine(_node1, _node2);
                }
            });
        });


        // Add plane
        this.plane = this.lineSeries.mapLines.getIndex(0).lineObjects.create();
        this.plane.position = 0;
        this.plane.width = 48;
        this.plane.height = 48;

        this.plane.adapter.add("scale", function (scale, target) {
            return 0.5 * (1 - (Math.abs(0.5 - target.position)));
        });

        this.planeImage = this.plane.createChild(am4core.Sprite);
        this.planeImage.scale = 0.08;
        this.planeImage.horizontalCenter = "middle";
        this.planeImage.verticalCenter = "middle";
        this.planeImage.path = planeSVG;
        this.planeImage.fill = chart.colors.getIndex(2).brighten(-0.2);
        this.planeImage.strokeOpacity = 0;

        this.shadowPlane = this.shadowLineSeries.mapLines.getIndex(0).lineObjects.create();
        this.shadowPlane.position = 0;
        this.shadowPlane.width = 48;
        this.shadowPlane.height = 48;
        this.shadowPlane.adapter.add("scale", function (scale, target) {
            target.opacity = (0.6 - (Math.abs(0.5 - target.position)));
            return 0.5 - 0.3 * (1 - (Math.abs(0.5 - target.position)));
        });

        this.shadowPlaneImage = this.shadowPlane.createChild(am4core.Sprite);
        this.shadowPlaneImage.scale = 0.05;
        this.shadowPlaneImage.horizontalCenter = "middle";
        this.shadowPlaneImage.verticalCenter = "middle";
        this.shadowPlaneImage.path = planeShadowSVG;
        this.shadowPlaneImage.fill = am4core.color("#000");
        this.shadowPlaneImage.strokeOpacity = 0;

        this.chart = chart;

        // Go!
        this.flyPlane();
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
            // line.line.controlPointDistance = 0;
            // line.line.shortestDistance = true;
            let shadowLine = this.shadowLineSeries.mapLines.create();
            shadowLine.imagesToConnect = [from, to];
            return line;
        },

        flyPlane() {

            console.log("flyPlane");

            // Get current line to attach plane to
            this.plane.mapLine = this.lineSeries.mapLines.getIndex(this.currentLine);
            this.plane.parent = this.lineSeries;
            this.shadowPlane.mapLine = this.shadowLineSeries.mapLines.getIndex(this.currentLine);
            this.shadowPlane.parent = this.shadowLineSeries;
            this.shadowPlaneImage.rotation = this.planeImage.rotation;

            // Set up animation
            let from = 0, to = 1;
            let numLines = this.lineSeries.mapLines.length;
            // if (this.direction === 1) {
            //     from = 0;
            //     to = 1;
            //     if (this.planeImage.rotation !== 0) {
            // this.planeImage.animate({
            //     to: 0,
            //     property: "rotation"
            // }, 1000).events.on("animationended", this.flyPlane);
            // return;
            //     }
            // } else {
            //     from = 1;
            //     to = 0;
            //     if (this.planeImage.rotation !== 180) {
            // this.planeImage.animate({
            //     to: 180,
            //     property: "rotation"
            // }, 1000).events.on("animationended", this.flyPlane);
            // return;
            //     }
            // }

            // Start the animation
            let animation = this.plane.animate({
                from: from,
                to: to,
                property: "position"
            }, 5000, am4core.ease.sinInOut);
            // animation.events.on("animationended", this.flyPlane);
            /*animation.events.on("animationprogress", function(ev) {
              var progress = Math.abs(ev.progress - 0.5);
              //console.log(progress);
              //planeImage.scale += 0.2;
            });*/

            this.shadowPlane.animate({
                from: from,
                to: to,
                property: "position"
            }, 5000, am4core.ease.sinInOut);

            // Increment line, or reverse the direction
            this.currentLine += this.direction;
            if (this.currentLine < 0) {
                this.currentLine = 0;
                this.direction = 1;
            } else if ((this.currentLine + 1) > numLines) {
                this.currentLine = numLines - 1;
                this.direction = -1;
            }

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
