export default class Progress {

    constructor() {
        this.n_ticks = 0.0;
        this.current_tick = 0.0;
    }

    addTicks(n_ticks) {
        this.n_ticks += n_ticks;
    }

    tick() {
        this.current_tick += 1.0;
    }

    progress() {
        return Math.round((this.current_tick / this.n_ticks) * 100);
    }
}