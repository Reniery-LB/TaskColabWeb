class ReportsManager {
  constructor() {
    this.baseUrl = window.API_BASE
      ? window.API_BASE.replace('/endpoints', '/endpointsReportes')
      : '/PROYECTO_GESTOR_TAREAS/assets/app/endpointsReportes';
    this.charts = {};
    this.latestData = null;
    this.isLoading = false;
    this.init();
  }

  init() {
    this.setupEventListeners();
    this.loadDashboardStats();
  }

  setupEventListeners() {
    const exportBtn = document.getElementById('btn-exportar-pdf');
    exportBtn?.addEventListener('click', (event) => {
      event.preventDefault();

      const exportNow = () => this.exportToPDF();
      if (window.configurarAlerta) {
        window.configurarAlerta(
          'Exportar PDF',
          '¿Generar un reporte ejecutivo con el estado actual del tablero?',
          'alerta',
          { textoConfirmar: 'Exportar', onConfirmar: exportNow }
        );
      } else {
        exportNow();
      }
    });

    document.querySelectorAll('.report-kpi-card').forEach((card) => {
      card.addEventListener('click', () => this.navigateFromStats(card.dataset.action));
    });
  }

  async loadDashboardStats() {
    if (this.isLoading) return;

    try {
      this.isLoading = true;
      this.setLoadingState(true);
      const response = await fetch(`${this.baseUrl}/get_dashboard_stats.php?t=${Date.now()}`, {
        credentials: 'include',
        cache: 'no-cache'
      });
      const result = await response.json();

      if (!response.ok || !result.success) {
        throw new Error(result.error || 'No se pudieron cargar los reportes');
      }

      this.latestData = result.data;
      this.renderDashboard(result.data);
    } catch (error) {
      console.error('Error cargando reportes:', error);
      this.showToast('Error al cargar reportes', 'error');
      this.renderDashboard(this.fallbackData());
    } finally {
      this.setLoadingState(false);
      this.isLoading = false;
    }
  }

  renderDashboard(data) {
    const stats = data.general_stats || {};
    this.setText('report-total-tasks', stats.total_tareas || 0);
    this.setText('report-completed-tasks', stats.completado || 0);
    this.setText('report-overdue-tasks', stats.atrasadas || 0);
    this.setText('report-soon-tasks', stats.proximas || 0);
    this.setText('report-active-users', stats.usuarios_activos || 0);
    this.setText('report-productivity-label', `${stats.productividad || 0}% productividad`);

    this.renderStatusChart(data.state_distribution || data.board_progress || []);
    this.renderUsersChart(data.active_users || []);
    this.renderWeeklyChart(data.completed_by_week || []);
    this.renderHeatmap(data.priority_heatmap || { buckets: [], rows: [] });
    this.renderAlerts(data.alert_tasks || data.overdue_tasks || []);
    this.queueChartsResize();
  }

  renderStatusChart(rows) {
    const labels = rows.map(item => item.status_display);
    const values = rows.map(item => Number(item.total_tasks || 0));
    const colors = ['#f59e0b', '#1b5cff', '#16a34a'];
    const canvas = document.getElementById('report-status-chart');
    const fallback = document.getElementById('report-status-fallback');
    const legend = document.getElementById('report-status-legend');

    if (legend) {
      legend.innerHTML = rows.map((item, index) => `
        <span><i style="background:${colors[index] || '#64748b'}"></i>${escapeHtml(item.status_display)} · ${item.total_tasks}</span>
      `).join('');
    }

    if (!window.Chart || !canvas) {
      this.renderDonutFallback(fallback, values, colors);
      if (canvas) canvas.style.display = 'none';
      return;
    }

    if (fallback) fallback.innerHTML = '';
    canvas.style.display = 'block';
    this.destroyChart('status');
    this.charts.status = new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{
          data: values,
          backgroundColor: colors,
          borderColor: '#ffffff',
          borderWidth: 4,
          hoverOffset: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        cutout: '68%',
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: ctx => `${ctx.label}: ${ctx.raw} tareas` } }
        }
      }
    });
    this.queueChartsResize();
  }

  renderUsersChart(users) {
    const labels = users.map(user => user.usuario);
    const assigned = users.map(user => Number(user.tareas_asignadas || 0));
    const completed = users.map(user => Number(user.tareas_completadas || 0));
    const canvas = document.getElementById('report-users-chart');
    const list = document.getElementById('report-users-list');
    this.setChartBoxHeight(canvas, Math.min(Math.max(labels.length * 46 + 92, 300), 520));

    if (list) {
      const max = Math.max(...assigned, 1);
      list.innerHTML = users.length ? users.map(user => {
        const value = Number(user.tareas_asignadas || 0);
        return `
          <div class="report-user-row">
            <div><strong>${escapeHtml(user.usuario)}</strong><span>${value} asignadas · ${user.tareas_completadas || 0} completadas</span></div>
            <div class="report-mini-track"><i style="width:${Math.round((value / max) * 100)}%"></i></div>
          </div>
        `;
      }).join('') : '<div class="report-empty">Sin tareas asignadas todavía.</div>';
    }

    if (!window.Chart || !canvas) {
      if (canvas) canvas.style.display = 'none';
      return;
    }

    canvas.style.display = 'block';
    this.destroyChart('users');
    this.charts.users = new Chart(canvas, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            label: 'Asignadas',
            data: assigned,
            backgroundColor: '#1b5cff',
            borderRadius: 8,
            barThickness: 12,
            maxBarThickness: 16
          },
          {
            label: 'Completadas',
            data: completed,
            backgroundColor: '#16a34a',
            borderRadius: 8,
            barThickness: 12,
            maxBarThickness: 16
          }
        ]
      },
      options: this.chartScaffold({ indexAxis: 'y' })
    });
    this.queueChartsResize();
  }

  renderWeeklyChart(weeks) {
    const canvas = document.getElementById('report-weekly-chart');
    const list = document.getElementById('report-weekly-list');
    this.setChartBoxHeight(canvas, 340);
    if (list) {
      const max = Math.max(...weeks.map(week => Number(week.total || 0)), 1);
      list.innerHTML = weeks.length ? weeks.map(week => {
        const value = Number(week.total || 0);
        return `
          <div class="report-user-row">
            <div><strong>${escapeHtml(week.label)}</strong><span>${value} completadas</span></div>
            <div class="report-mini-track"><i style="width:${Math.round((value / max) * 100)}%"></i></div>
          </div>
        `;
      }).join('') : '<div class="report-empty">Aún no hay tareas completadas por semana.</div>';
    }

    if (!window.Chart || !canvas) {
      if (canvas) canvas.style.display = 'none';
      return;
    }

    canvas.style.display = 'block';

    this.destroyChart('weekly');
    this.charts.weekly = new Chart(canvas, {
      type: 'line',
      data: {
        labels: weeks.map(week => week.label),
        datasets: [{
          label: 'Completadas',
          data: weeks.map(week => Number(week.total || 0)),
          borderColor: '#1b5cff',
          backgroundColor: 'rgba(27, 92, 255, 0.16)',
          fill: true,
          tension: 0.4,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#1b5cff',
          pointBorderWidth: 3,
          pointRadius: 4
        }]
      },
      options: this.chartScaffold()
    });
    this.queueChartsResize();
  }

  renderHeatmap(heatmap) {
    const container = document.getElementById('report-priority-heatmap');
    if (!container) return;

    const buckets = heatmap.buckets || [];
    const rows = heatmap.rows || [];
    const max = Math.max(
      1,
      ...rows.flatMap(row => (row.cells || []).map(cell => Number(cell.value || 0)))
    );

    container.innerHTML = `
      <div class="report-heatmap-head">
        <span></span>
        ${buckets.map(bucket => `<strong>${escapeHtml(bucket)}</strong>`).join('')}
      </div>
      ${rows.map(row => `
        <div class="report-heatmap-row">
          <strong>${escapeHtml(row.label)}</strong>
          ${(row.cells || []).map(cell => {
            const intensity = Math.max(0.08, Number(cell.value || 0) / max);
            return `<span style="--heat:${intensity}" title="${escapeHtml(row.label)} · ${escapeHtml(cell.label)}: ${cell.value}">${cell.value}</span>`;
          }).join('')}
        </div>
      `).join('')}
    `;
  }

  renderAlerts(tasks) {
    const container = document.getElementById('report-alerts-table');
    if (!container) return;

    if (!tasks.length) {
      container.innerHTML = '<div class="report-empty">Sin alertas críticas por ahora.</div>';
      return;
    }

    container.innerHTML = `
      <table>
        <thead>
          <tr>
            <th>Alerta</th>
            <th>Tarea</th>
            <th>Responsable</th>
            <th>Fecha</th>
            <th>Prioridad</th>
          </tr>
        </thead>
        <tbody>
          ${tasks.map(task => `
            <tr>
              <td><span class="alert-pill alert-${escapeHtml(task.alert_type || 'priority')}">${escapeHtml(task.alert_label || 'Alerta')}</span></td>
              <td><strong>${escapeHtml(task.title)}</strong><small>${escapeHtml(task.board_title || 'Sin tablero')}</small></td>
              <td>${escapeHtml(task.assigned_users || 'Sin asignar')}</td>
              <td>${escapeHtml(task.due_date_display || 'Sin fecha')}</td>
              <td>${escapeHtml(task.priority_display || task.priority)}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  renderDonutFallback(container, values, colors) {
    if (!container) return;
    const total = values.reduce((sum, value) => sum + value, 0);
    let cursor = 0;
    const stops = values.map((value, index) => {
      const start = total > 0 ? (cursor / total) * 100 : 0;
      cursor += value;
      const end = total > 0 ? (cursor / total) * 100 : 0;
      return `${colors[index]} ${start}% ${end}%`;
    }).join(', ');

    container.innerHTML = `<div class="css-donut" style="background: conic-gradient(${stops || '#dbe6f7 0 100%'});"><span>${total}</span></div>`;
  }

  chartScaffold(extra = {}) {
    const isHorizontal = extra.indexAxis === 'y';

    return {
      responsive: true,
      maintainAspectRatio: false,
      animation: false,
      resizeDelay: 80,
      ...extra,
      plugins: {
        legend: {
          labels: { boxWidth: 10, color: '#64748b', font: { family: 'Poppins' } }
        },
        tooltip: { backgroundColor: '#172033', padding: 10 }
      },
      scales: {
        x: {
          beginAtZero: true,
          grace: '12%',
          grid: { color: 'rgba(100, 116, 139, 0.12)' },
          ticks: { color: '#64748b', precision: 0 }
        },
        y: {
          grid: { display: false },
          ticks: { color: '#64748b', autoSkip: !isHorizontal }
        }
      }
    };
  }

  setChartBoxHeight(canvas, height) {
    const box = canvas?.closest('.chart-box');
    if (box) box.style.height = `${height}px`;
  }

  queueChartsResize() {
    const raf = window.requestAnimationFrame || ((callback) => window.setTimeout(callback, 16));
    raf(() => {
      raf(() => {
        Object.values(this.charts).forEach((chart) => chart.resize());
      });
    });
  }

  navigateFromStats(action) {
    if (action === 'usuarios') {
      window.mostrarSeccion?.('usuarios');
    } else if (action) {
      window.mostrarSeccion?.('tableros');
    }
  }

  exportToPDF() {
    this.showToast('Generando PDF moderno...', 'info');
    window.open(`${this.baseUrl}/export_pdf.php`, '_blank');
  }

  fallbackData() {
    return {
      general_stats: {
        total_tareas: 0,
        pendiente: 0,
        en_proceso: 0,
        completado: 0,
        atrasadas: 0,
        proximas: 0,
        productividad: 0,
        usuarios_activos: 0
      },
      state_distribution: [
        { status: 'pending', status_display: 'Pendiente', total_tasks: 0, percentage: 0 },
        { status: 'in_progress', status_display: 'En proceso', total_tasks: 0, percentage: 0 },
        { status: 'done', status_display: 'Completado', total_tasks: 0, percentage: 0 }
      ],
      active_users: [],
      completed_by_week: [],
      priority_heatmap: { buckets: ['Atrasadas', 'Hoy', '7 días', 'Después', 'Sin fecha'], rows: [] },
      alert_tasks: []
    };
  }

  setText(id, value) {
    const node = document.getElementById(id);
    if (node) node.textContent = value;
  }

  setLoadingState(isLoading) {
    document.getElementById('reportes')?.classList.toggle('reports-loading', isLoading);
  }

  destroyChart(key) {
    if (this.charts[key]) {
      this.charts[key].destroy();
      delete this.charts[key];
    }
  }

  showToast(message, type = 'info') {
    if (window.showToast) {
      window.showToast(message, type);
      return;
    }
    console.log(`Toast (${type}): ${message}`);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const reportesSection = document.getElementById('reportes');
  if (!reportesSection) return;

  let wasActive = reportesSection.classList.contains('activa');

  const ensureReports = () => {
    if (!reportesSection.classList.contains('activa')) return;
    if (!window.reportsManager) {
      window.reportsManager = new ReportsManager();
    } else {
      window.reportsManager.loadDashboardStats();
    }
  };

  ensureReports();

  const observer = new MutationObserver(() => {
    const isActive = reportesSection.classList.contains('activa');
    if (isActive && !wasActive) {
      ensureReports();
    }
    wasActive = isActive;
  });
  observer.observe(reportesSection, { attributes: true, attributeFilter: ['class'] });
});

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}
