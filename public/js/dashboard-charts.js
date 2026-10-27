(() => {
    const dataElement = document.getElementById('dashboard-chart-data');
    if (!dataElement) return;

    let charts;
    try {
        charts = JSON.parse(dataElement.textContent);
    } catch (error) {
        console.error('No se pudieron leer los datos de los gráficos.', error);
        return;
    }

    const namespace = 'http://www.w3.org/2000/svg';
    const makeSvg = (name, attrs = {}) => {
        const element = document.createElementNS(namespace, name);
        Object.entries(attrs).forEach(([key, value]) => element.setAttribute(key, value));
        return element;
    };

    document.querySelectorAll('[data-donut]').forEach((container) => {
        const series = charts[container.dataset.donut] || [];
        const total = series.reduce((sum, item) => sum + Number(item.value || 0), 0);
        const svg = makeSvg('svg', { viewBox: '0 0 200 200', role: 'group', 'aria-label': `${container.getAttribute('aria-label')}: ${total} registros` });
        const ring = makeSvg('circle', { cx: 100, cy: 100, r: 67, fill: 'none', stroke: '#EAF1F6', 'stroke-width': 27 });
        svg.append(ring);

        if (total > 0) {
            const circumference = 2 * Math.PI * 67;
            let offset = 0;
            series.filter((item) => Number(item.value) > 0).forEach((item) => {
                const length = circumference * Number(item.value) / total;
                const anchor = makeSvg('a', {
                    href: item.url,
                    'aria-label': `${item.label}: ${item.value} de ${total} (${(100 * item.value / total).toLocaleString('es-EC', { maximumFractionDigits: 1 })} %). Abrir listado filtrado`,
                    tabindex: '0',
                });
                const title = makeSvg('title');
                title.textContent = `${item.label}: ${item.value} (${(100 * item.value / total).toLocaleString('es-EC', { maximumFractionDigits: 1 })} %). Selecciona para ver el listado.`;
                const segment = makeSvg('circle', {
                    class: 'donut-segment', cx: 100, cy: 100, r: 67, stroke: item.color,
                    'stroke-dasharray': `${length} ${circumference - length}`,
                    'stroke-dashoffset': `${-offset}`,
                    transform: 'rotate(-90 100 100)',
                });
                anchor.append(title, segment);
                svg.append(anchor);
                offset += length;
            });
        }

        const count = makeSvg('text', { x: 100, y: 94, class: 'donut-center-total' });
        count.textContent = String(total);
        const label = makeSvg('text', { x: 100, y: 116, class: 'donut-center-label' });
        label.textContent = 'MANUALES';
        svg.append(count, label);
        container.replaceChildren(svg);
    });
})();
