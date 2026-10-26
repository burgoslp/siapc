import ApexCharts from 'apexcharts';

document.addEventListener('livewire:init', () => {
    
    let chartLine, chartPie;

    function initCharts() {
        // Destruir gráficos anteriores si existen para evitar duplicados
        if (chartLine) { chartLine.destroy(); chartLine = null; }
        if (chartPie) { chartPie.destroy(); chartPie = null; }

        const elLine = document.querySelector("#chart-line");
        const elPie = document.querySelector("#chart-pie");

        if (!elLine || !elPie) return;

        // Recuperamos los datos que Livewire inyectó en el HTML
        const dataLine = JSON.parse(elLine.dataset.chart || '{}');
        const dataPie = JSON.parse(elPie.dataset.chart || '{}');

        // Gráfico de Línea (Ingresos vs Egresos)
        const lineOptions = {
            series: [
                { name: 'Ingresos', data: dataLine.ingresos || [] },
                { name: 'Egresos', data: dataLine.egresos || [] }
            ],
            chart: { type: 'area', height: 320, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            colors: ['#0d9488', '#ef4444'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            xaxis: { categories: dataLine.labels || [] },
            tooltip: { y: { formatter: (val) => (dataLine.simbolo || '') + val.toLocaleString() } },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } }
        };
        chartLine = new ApexCharts(elLine, lineOptions);
        chartLine.render();

        // Gráfico de Torta (Métodos de Pago)
        const pieOptions = {
            series: dataPie.series || [],
            labels: dataPie.labels || [],
            chart: { type: 'donut', height: 320, fontFamily: 'Inter, sans-serif' },
            colors: ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#6366f1', '#ec4899'],
            legend: { position: 'bottom' },
            tooltip: { y: { formatter: (val) => (dataPie.simbolo || '') + val.toLocaleString() } }
        };
        chartPie = new ApexCharts(elPie, pieOptions);
        chartPie.render();
    }

    // Escuchar cuando Livewire pide redibujar (al cambiar fechas o moneda)
    Livewire.on('redibujar-graficos', () => {
        setTimeout(initCharts, 100);
    });

    // Inicializar al cargar la página
    initCharts();
});