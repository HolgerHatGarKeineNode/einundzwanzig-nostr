import {Chart} from "chart.js/auto";

export default (livewireComponent) => ({
    plebs: livewireComponent.entangle('plebs', true),
    electionConfig: livewireComponent.entangle('electionConfig', true),
    votes: livewireComponent.entangle('votes', true),
    boardVotes: livewireComponent.entangle('boardVotes', true),
    charts: {}, // Store chart instances

    hexToRGB(h) {
        let r = 0;
        let g = 0;
        let b = 0;
        if (h.length === 4) {
            r = `0x${h[1]}${h[1]}`;
            g = `0x${h[2]}${h[2]}`;
            b = `0x${h[3]}${h[3]}`;
        } else if (h.length === 7) {
            r = `0x${h[1]}${h[2]}`;
            g = `0x${h[3]}${h[4]}`;
            b = `0x${h[5]}${h[6]}`;
        }
        return `${+r},${+g},${+b}`;
    },

    init() {
        this.createChart('chart_presidency', 'presidency');
        this.createChart('chart_board', 'board');

        this.$watch('votes', () => {
            this.createChart('chart_presidency', 'presidency');
            this.createChart('chart_board', 'board');
        });
    },

    createChart(refName, type) {
        const ctx = this.$refs[refName];
        if (!ctx) return;

        // Destroy old chart instance if it exists
        if (this.charts[refName]) {
            this.charts[refName].destroy();
        }

        const darkMode = localStorage.getItem('dark-mode') === 'true';

        const textColor = {
            light: '#9CA3AF',
            dark: '#6B7280'
        };

        const gridColor = {
            light: '#F3F4F6',
            dark: `rgba(${this.hexToRGB('#374151')}, 0.6)`
        };

        const tooltipBodyColor = {
            light: '#6B7280',
            dark: '#9CA3AF'
        };

        const tooltipBgColor = {
            light: '#ffffff',
            dark: '#374151'
        };

        const tooltipBorderColor = {
            light: '#E5E7EB',
            dark: '#4B5563'
        };

        const config = this.electionConfig.find(config => config.type === type);
        const labels = config ? config.candidates.map(candidate => candidate.name) : [];
        const labelsPubkeys = config ? config.candidates.map(candidate => candidate.pubkey) : [];
        let data;
        if (type === 'board') {
            data = this.boardVotes.find(vote => vote.type === type);
        } else {
            data = this.votes.find(vote => vote.type === type);
        }
        const findVoteCountInDataByLabelsPubkey = data ? labelsPubkeys.map(pubkey => data.votes[pubkey]?.count ?? 0) : labelsPubkeys.map(() => 0);
        console.log('findVoteCountInDataByLabelsPubkey', findVoteCountInDataByLabelsPubkey);

        // Create new chart instance and store it
        this.charts[refName] = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Stimmen',
                        data: findVoteCountInDataByLabelsPubkey,
                        backgroundColor: '#67BFFF',
                        hoverBackgroundColor: '#56B1F3',
                        barPercentage: 0.7,
                        categoryPercentage: 0.7,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                layout: {
                    padding: {
                        top: 12,
                        bottom: 16,
                        left: 20,
                        right: 20,
                    },
                },
                scales: {
                    y: {
                        border: {display: false},
                        ticks: {
                            maxTicksLimit: 5,
                            color: darkMode ? textColor.dark : textColor.light,
                        },
                        grid: {
                            color: darkMode ? gridColor.dark : gridColor.light,
                        },
                    },
                    x: {
                        border: {display: false},
                        grid: {display: false},
                        ticks: {
                            color: darkMode ? textColor.dark : textColor.light,
                        },
                    },
                },
                plugins: {
                    legend: {display: false},
                    htmlLegend: {containerID: 'dashboard-card-01-legend'},
                    tooltip: {
                        bodyColor: darkMode ? tooltipBodyColor.dark : tooltipBodyColor.light,
                        backgroundColor: darkMode ? tooltipBgColor.dark : tooltipBgColor.light,
                        borderColor: darkMode ? tooltipBorderColor.dark : tooltipBorderColor.light,
                    },
                },
                interaction: {
                    intersect: false,
                    mode: 'nearest',
                },
                animation: {duration: 200},
                maintainAspectRatio: false,
            },
        });

        document.addEventListener('darkMode', (e) => {
            const {mode} = e.detail;
            if (mode === 'on') {
                this.charts[refName].options.scales.x.ticks.color = textColor.dark;
                this.charts[refName].options.scales.y.ticks.color = textColor.dark;
                this.charts[refName].options.scales.y.grid.color = gridColor.dark;
                this.charts[refName].options.plugins.tooltip.bodyColor = tooltipBodyColor.dark;
                this.charts[refName].options.plugins.tooltip.backgroundColor = tooltipBgColor.dark;
                this.charts[refName].options.plugins.tooltip.borderColor = tooltipBorderColor.dark;
            } else {
                this.charts[refName].options.scales.x.ticks.color = textColor.light;
                this.charts[refName].options.scales.y.ticks.color = textColor.light;
                this.charts[refName].options.scales.y.grid.color = gridColor.light;
                this.charts[refName].options.plugins.tooltip.bodyColor = tooltipBodyColor.light;
                this.charts[refName].options.plugins.tooltip.backgroundColor = tooltipBgColor.light;
                this.charts[refName].options.plugins.tooltip.borderColor = tooltipBorderColor.light;
            }
            this.charts[refName].update('none');
        });
    },
});
