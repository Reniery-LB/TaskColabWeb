// assets/javascript/projects.js
document.addEventListener('DOMContentLoaded', () => {
  const apiBase = window.API_BASE_PROJECTS || '/assets/app/endpointsProjects';
  const form = document.getElementById('form-proyecto');
  const editForm = document.getElementById('project-edit-form');
  const archiveBtn = document.getElementById('project-archive-btn');
  const openArchivedBtn = document.getElementById('open-archived-projects');
  const list = document.getElementById('projects-list');
  const count = document.getElementById('projects-count');
  const archivedList = document.getElementById('archived-projects-list');
  const archivedCount = document.getElementById('archived-projects-count');
  const activeName = document.getElementById('active-project-name');
  const quickProjectSwitchers = document.querySelectorAll('.quick-project-switcher');
  const detailName = document.getElementById('project-detail-name');
  const detailDescription = document.getElementById('project-detail-description');
  const detailStatus = document.getElementById('project-detail-status');
  const detailTotal = document.getElementById('project-detail-total');
  const detailProgress = document.getElementById('project-detail-progress');
  const detailDone = document.getElementById('project-detail-done');
  const detailPercent = document.getElementById('project-detail-percent');
  const editName = document.getElementById('edit-project-name');
  const editDescription = document.getElementById('edit-project-description');
  const editStatus = document.getElementById('edit-project-status');
  const editDueDate = document.getElementById('edit-project-due-date');
  const editColor = document.getElementById('edit-project-color');

  let projects = [];
  let archivedProjects = [];
  let activeProject = null;

  window.TaskColabProjects = {
    getActiveProject: () => activeProject,
    getActiveBoardId: () => activeProject?.board_id || 1,
    getProjects: () => projects,
    setActiveProjectById,
    reload: loadProjects
  };

  loadProjects();

  openArchivedBtn?.addEventListener('click', () => {
    loadArchivedProjects();
  });

  quickProjectSwitchers.forEach((switcher) => {
    const toggle = switcher.querySelector('.quick-project-toggle');
    const menu = switcher.querySelector('.quick-project-menu');
    toggle?.addEventListener('click', (event) => {
      event.stopPropagation();
      const willOpen = menu?.hidden !== false;
      closeQuickProjectMenus(menu);
      if (menu) menu.hidden = !willOpen;
      toggle.setAttribute('aria-expanded', String(willOpen));
    });
  });

  document.addEventListener('click', (event) => {
    const clickedInside = event.target.closest?.('.quick-project-switcher');
    if (!clickedInside) closeQuickProjectMenus();
  });

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(form);
    const payload = {
      name: (formData.get('name') || '').trim(),
      description: (formData.get('description') || '').trim(),
      due_date: formData.get('due_date') || null,
      color: formData.get('color') || '#1B5CFF'
    };

    if (!payload.name) {
      showProjectError('El nombre del proyecto es obligatorio.');
      return;
    }

    if (!isFutureOrToday(payload.due_date)) {
      showProjectError('La fecha objetivo no puede ser anterior al día de hoy.');
      return;
    }

    try {
      const response = await fetch(`${apiBase}/create_project.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const json = await response.json();

      if (!response.ok || !json.ok) {
        throw new Error(json.message || 'No se pudo crear el proyecto');
      }

      form.reset();
      document.getElementById('project-color').value = '#1B5CFF';
      await loadProjects(json.project.id);
      showProjectSuccess('Proyecto creado correctamente.');
    } catch (error) {
      console.error('Error creando proyecto:', error);
      showProjectError(error.message || 'Error al crear el proyecto.');
    }
  });

  editForm?.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!activeProject) {
      showProjectError('Selecciona un proyecto antes de editar.');
      return;
    }

    const formData = new FormData(editForm);
    const payload = {
      project_id: Number(activeProject.id),
      name: (formData.get('name') || '').trim(),
      description: (formData.get('description') || '').trim(),
      status: formData.get('status') || 'active',
      due_date: formData.get('due_date') || null,
      color: formData.get('color') || '#1B5CFF'
    };

    if (!payload.name) {
      showProjectError('El nombre del proyecto es obligatorio.');
      return;
    }

    if (!isFutureOrToday(payload.due_date)) {
      showProjectError('La fecha objetivo no puede ser anterior al día de hoy.');
      return;
    }

    try {
      setProjectSaving(true);
      const response = await fetch(`${apiBase}/update_project.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const json = await response.json();

      if (!response.ok || !json.ok) {
        throw new Error(json.message || 'No se pudo actualizar el proyecto');
      }

      await loadProjects(activeProject.id);
      showProjectSuccess('Proyecto actualizado correctamente.');
    } catch (error) {
      console.error('Error actualizando proyecto:', error);
      showProjectError(error.message || 'Error al actualizar el proyecto.');
    } finally {
      setProjectSaving(false);
    }
  });

  archiveBtn?.addEventListener('click', () => {
    if (!activeProject) {
      showProjectError('Selecciona un proyecto antes de archivar.');
      return;
    }

    if (projects.length <= 1) {
      showProjectError('Mantén al menos un proyecto activo para no dejar el tablero sin espacio de trabajo.');
      return;
    }

    const archive = async () => {
      try {
        const response = await fetch(`${apiBase}/archive_project.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ project_id: Number(activeProject.id) })
        });
        const json = await response.json();

        if (!response.ok || !json.ok) {
          throw new Error(json.message || 'No se pudo archivar el proyecto');
        }

        localStorage.removeItem('taskcolab_active_project_id');
        await loadProjects();
        await loadArchivedProjects(false);
        showProjectSuccess('Proyecto archivado correctamente.');
      } catch (error) {
        console.error('Error archivando proyecto:', error);
        showProjectError(error.message || 'Error al archivar el proyecto.');
      }
    };

    if (typeof window.configurarAlerta === 'function') {
      window.configurarAlerta(
        'Archivar proyecto',
        `¿Archivar "${escapeHtml(activeProject.name)}"?<br>Sus tareas se conservarán, pero el proyecto saldrá de la lista activa.`,
        'alerta',
        {
          textoConfirmar: 'Archivar',
          onConfirmar: archive
        }
      );
    } else if (confirm(`¿Archivar "${activeProject.name}"?`)) {
      archive();
    }
  });

  async function loadProjects(preferredProjectId = null) {
    if (!list) return;

    try {
      list.innerHTML = '<div class="project-empty">Cargando proyectos...</div>';
      const response = await fetch(`${apiBase}/list_projects.php?t=${Date.now()}`, {
        cache: 'no-cache'
      });
      const json = await response.json();

      if (!response.ok || !json.ok) {
        throw new Error(json.message || 'No se pudieron cargar proyectos');
      }

      projects = json.projects || [];
      const savedProjectId = preferredProjectId || Number(localStorage.getItem('taskcolab_active_project_id'));
      activeProject = projects.find(project => Number(project.id) === Number(savedProjectId)) || projects[0] || null;

      if (activeProject) {
        localStorage.setItem('taskcolab_active_project_id', activeProject.id);
      }

      renderProjects();
      renderProjectDetail();
      renderQuickProjectMenu();
      notifyProjectChanged();
    } catch (error) {
      console.error('Error cargando proyectos:', error);
      projects = [];
      activeProject = null;
      list.innerHTML = `<div class="project-empty project-empty-error">${escapeHtml(error.message || 'Error al cargar proyectos.')}</div>`;
      if (count) count.textContent = '0 proyectos';
      renderProjectDetail();
      renderQuickProjectMenu();
      notifyProjectChanged();
    }
  }

  function renderProjects() {
    if (!list) return;

    if (count) {
      count.textContent = projects.length === 1 ? '1 proyecto' : `${projects.length} proyectos`;
    }

    if (!projects.length) {
      list.innerHTML = '<div class="project-empty">Crea tu primer proyecto para organizar el trabajo.</div>';
      return;
    }

    list.innerHTML = projects.map(project => {
      const total = Number(project.total_tasks || 0);
      const done = Number(project.done_tasks || 0);
      const inProgress = Number(project.in_progress_tasks || 0);
      const progress = clampPercent(total > 0 ? Math.round((done / total) * 100) : 0);
      const isActive = activeProject && Number(activeProject.id) === Number(project.id);
      const statusLabel = getStatusLabel(project.status);

      return `
        <article class="project-card ${isActive ? 'active' : ''}" data-project-id="${project.id}">
          <div class="project-card-top">
            <span class="project-color" style="background:${escapeHtml(project.color || '#1B5CFF')}"></span>
            <span class="project-status status-${escapeHtml(project.status || 'active')}">${statusLabel}</span>
          </div>
          <h3>${escapeHtml(project.name)}</h3>
          <p>${escapeHtml(project.description || 'Sin descripción')}</p>
          <div class="project-progress">
            <div>
              <span>Avance</span>
              <strong>${progress}%</strong>
            </div>
            <div class="project-progress-track">
              <span style="width:${progress}%"></span>
            </div>
          </div>
          <div class="project-card-meta">
            <span>${total} tareas</span>
            <span>${inProgress} en proceso</span>
            <span>${Number(project.members_count || 1)} miembros</span>
            <span>${project.due_date ? formatDate(project.due_date) : 'Sin fecha'}</span>
          </div>
          <button type="button" class="project-select-btn">${isActive ? 'Gestionando' : 'Usar proyecto'}</button>
        </article>
      `;
    }).join('');

    list.querySelectorAll('.project-card').forEach((card) => {
      card.addEventListener('click', () => {
        setActiveProjectById(Number(card.dataset.projectId));
      });
    });
  }

  function renderQuickProjectMenu() {
    const menus = document.querySelectorAll('.quick-project-menu');
    if (!menus.length) return;

    menus.forEach((menu) => {
      if (!projects.length) {
        menu.innerHTML = '<div class="quick-project-empty">No hay proyectos activos.</div>';
        return;
      }

      menu.innerHTML = projects.map(project => {
        const isActive = activeProject && Number(activeProject.id) === Number(project.id);
        return `
          <button type="button" class="quick-project-option ${isActive ? 'active' : ''}" data-project-id="${project.id}">
            <span class="project-color" style="background:${escapeHtml(project.color || '#1B5CFF')}"></span>
            <span class="quick-project-name">${escapeHtml(project.name || 'Proyecto')}</span>
            ${isActive ? '<strong>Activo</strong>' : ''}
          </button>
        `;
      }).join('');

      menu.querySelectorAll('.quick-project-option').forEach((button) => {
        button.addEventListener('click', (event) => {
          event.stopPropagation();
          setActiveProjectById(Number(button.dataset.projectId), true);
        });
      });
    });
  }

  function setActiveProjectById(projectId, closeMenu = false) {
    const nextProject = projects.find(project => Number(project.id) === Number(projectId));
    if (!nextProject) return;

    activeProject = nextProject;
    localStorage.setItem('taskcolab_active_project_id', activeProject.id);
    renderProjects();
    renderProjectDetail();
    renderQuickProjectMenu();
    notifyProjectChanged();
    if (closeMenu) closeQuickProjectMenus();
  }

  function closeQuickProjectMenus(exceptMenu = null) {
    document.querySelectorAll('.quick-project-switcher').forEach((switcher) => {
      const menu = switcher.querySelector('.quick-project-menu');
      const toggle = switcher.querySelector('.quick-project-toggle');
      if (menu && menu !== exceptMenu) {
        menu.hidden = true;
        toggle?.setAttribute('aria-expanded', 'false');
      }
    });
  }

  async function loadArchivedProjects(showLoading = true) {
    if (!archivedList) return;

    try {
      if (showLoading) {
        archivedList.innerHTML = '<div class="project-empty">Cargando proyectos archivados...</div>';
      }

      const response = await fetch(`${apiBase}/list_archived_projects.php?t=${Date.now()}`, {
        cache: 'no-cache'
      });
      const json = await response.json();

      if (!response.ok || !json.ok) {
        throw new Error(json.message || 'No se pudieron cargar proyectos archivados');
      }

      archivedProjects = json.projects || [];
      renderArchivedProjects();
    } catch (error) {
      console.error('Error cargando proyectos archivados:', error);
      archivedProjects = [];
      archivedList.innerHTML = `<div class="project-empty project-empty-error">${escapeHtml(error.message || 'Error al cargar proyectos archivados.')}</div>`;
      if (archivedCount) archivedCount.textContent = '0 proyectos';
    }
  }

  function renderArchivedProjects() {
    if (!archivedList) return;

    if (archivedCount) {
      archivedCount.textContent = archivedProjects.length === 1 ? '1 proyecto' : `${archivedProjects.length} proyectos`;
    }

    if (!archivedProjects.length) {
      archivedList.innerHTML = '<div class="project-empty">No hay proyectos archivados.</div>';
      return;
    }

    archivedList.innerHTML = archivedProjects.map(project => {
      const total = Number(project.total_tasks || 0);
      const done = Number(project.done_tasks || 0);
      const inProgress = Number(project.in_progress_tasks || 0);
      const progress = clampPercent(total > 0 ? Math.round((done / total) * 100) : 0);

      return `
        <article class="project-card archived-project-card" data-project-id="${project.id}">
          <div class="project-card-top">
            <span class="project-color" style="background:${escapeHtml(project.color || '#1B5CFF')}"></span>
            <span class="project-status status-${escapeHtml(project.status || 'archived')}">${getStatusLabel(project.status)}</span>
          </div>
          <h3>${escapeHtml(project.name)}</h3>
          <p>${escapeHtml(project.description || 'Sin descripción')}</p>
          <div class="project-progress">
            <div>
              <span>Avance</span>
              <strong>${progress}%</strong>
            </div>
            <div class="project-progress-track">
              <span style="width:${progress}%"></span>
            </div>
          </div>
          <div class="project-card-meta">
            <span>${total} tareas</span>
            <span>${inProgress} en proceso</span>
            <span>${Number(project.members_count || 1)} miembros</span>
            <span>${project.due_date ? formatDate(project.due_date) : 'Sin fecha'}</span>
          </div>
          <div class="archived-project-actions">
            <button type="button" class="project-restore-btn" data-project-id="${project.id}">Desarchivar</button>
            <button type="button" class="project-delete-permanent-btn" data-project-id="${project.id}">Eliminar</button>
          </div>
        </article>
      `;
    }).join('');

    archivedList.querySelectorAll('.project-restore-btn').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.stopPropagation();
        const projectId = Number(button.dataset.projectId);
        const project = archivedProjects.find(item => Number(item.id) === projectId);
        if (project) restoreArchivedProject(project);
      });
    });

    archivedList.querySelectorAll('.project-delete-permanent-btn').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.stopPropagation();
        const projectId = Number(button.dataset.projectId);
        const project = archivedProjects.find(item => Number(item.id) === projectId);
        if (project) deleteArchivedProject(project);
      });
    });
  }

  function restoreArchivedProject(project) {
    const restore = async () => {
      try {
        const response = await fetch(`${apiBase}/restore_project.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ project_id: Number(project.id) })
        });
        const json = await response.json();

        if (!response.ok || !json.ok) {
          throw new Error(json.message || 'No se pudo desarchivar el proyecto');
        }

        localStorage.setItem('taskcolab_active_project_id', project.id);
        await loadProjects(project.id);
        await loadArchivedProjects(false);
        showProjectSuccess('Proyecto desarchivado correctamente.');
      } catch (error) {
        console.error('Error desarchivando proyecto:', error);
        showProjectError(error.message || 'Error al desarchivar el proyecto.');
      }
    };

    if (typeof window.configurarAlerta === 'function') {
      window.configurarAlerta(
        'Desarchivar proyecto',
        `¿Desarchivar "${escapeHtml(project.name)}"?<br>El proyecto volverá a la lista de espacios activos.`,
        'alerta',
        {
          textoConfirmar: 'Desarchivar',
          onConfirmar: restore
        }
      );
    } else if (confirm(`¿Desarchivar "${project.name}"?`)) {
      restore();
    }
  }

  function deleteArchivedProject(project) {
    const remove = async () => {
      try {
        const response = await fetch(`${apiBase}/delete_archived_project.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ project_id: Number(project.id) })
        });
        const json = await response.json();

        if (!response.ok || !json.ok) {
          throw new Error(json.message || 'No se pudo eliminar el proyecto');
        }

        if (Number(localStorage.getItem('taskcolab_active_project_id')) === Number(project.id)) {
          localStorage.removeItem('taskcolab_active_project_id');
        }

        await loadArchivedProjects(false);
        await loadProjects();
        showProjectSuccess('Proyecto eliminado permanentemente.');
      } catch (error) {
        console.error('Error eliminando proyecto archivado:', error);
        showProjectError(error.message || 'Error al eliminar el proyecto.');
      }
    };

    if (typeof window.configurarAlerta === 'function') {
      window.configurarAlerta(
        'Eliminar proyecto',
        `¿Eliminar permanentemente "${escapeHtml(project.name)}"?<br>Se borrarán sus tareas, tarjetas, chats y relaciones. Esta acción no se puede deshacer.`,
        'alerta',
        {
          textoConfirmar: 'Eliminar',
          onConfirmar: remove
        }
      );
    } else if (confirm(`¿Eliminar permanentemente "${project.name}"? Esta acción no se puede deshacer.`)) {
      remove();
    }
  }

  function renderProjectDetail() {
    if (!activeProject) {
      if (detailName) detailName.textContent = 'Sin proyecto activo';
      if (detailDescription) detailDescription.textContent = 'Crea un proyecto para empezar a organizar el trabajo.';
      if (detailStatus) {
        detailStatus.textContent = 'Sin estado';
        detailStatus.className = 'project-detail-status status-paused';
      }
      if (detailTotal) detailTotal.textContent = '0';
      if (detailProgress) detailProgress.textContent = '0';
      if (detailDone) detailDone.textContent = '0';
      if (detailPercent) detailPercent.textContent = '0%';
      if (editForm) editForm.reset();
      setEditDisabled(true);
      return;
    }

    const total = Number(activeProject.total_tasks || 0);
    const inProgress = Number(activeProject.in_progress_tasks || 0);
    const done = Number(activeProject.done_tasks || 0);
    const progress = clampPercent(total > 0 ? Math.round((done / total) * 100) : 0);

    if (detailName) detailName.textContent = activeProject.name || 'Proyecto';
    if (detailDescription) {
      detailDescription.textContent = activeProject.description || 'Sin descripción. Agrega objetivo, alcance o entregables para que el equipo tenga contexto.';
    }
    if (detailStatus) {
      detailStatus.textContent = getStatusLabel(activeProject.status);
      detailStatus.className = `project-detail-status status-${activeProject.status || 'active'}`;
    }
    if (detailTotal) detailTotal.textContent = total;
    if (detailProgress) detailProgress.textContent = inProgress;
    if (detailDone) detailDone.textContent = done;
    if (detailPercent) detailPercent.textContent = `${progress}%`;

    if (editName) editName.value = activeProject.name || '';
    if (editDescription) editDescription.value = activeProject.description || '';
    if (editStatus) editStatus.value = activeProject.status === 'paused' ? 'paused' : 'active';
    if (editDueDate) editDueDate.value = activeProject.due_date || '';
    if (editColor) editColor.value = activeProject.color || '#1B5CFF';
    setEditDisabled(false);
  }

  function notifyProjectChanged() {
    document.querySelectorAll('[data-active-project-name]').forEach((node) => {
      node.textContent = activeProject?.name || 'Proyecto general';
    });

    window.dispatchEvent(new CustomEvent('taskcolab:projectChanged', {
      detail: {
        project: activeProject,
        boardId: activeProject?.board_id || 1
      }
    }));
  }

  function showProjectError(message) {
    if (typeof window.configurarAlerta === 'function') {
      window.configurarAlerta('Proyecto', message, 'alerta', { soloAceptar: true });
    } else {
      alert(message);
    }
  }

  function showProjectSuccess(message) {
    if (typeof window.configurarAlerta === 'function') {
      window.configurarAlerta('Proyecto', message, 'exito', { soloAceptar: true });
    }
  }

  function setProjectSaving(isSaving) {
    const submit = editForm?.querySelector('button[type="submit"]');
    if (submit) {
      submit.disabled = isSaving;
      submit.textContent = isSaving ? 'Guardando...' : 'Guardar cambios';
    }
    if (archiveBtn) archiveBtn.disabled = isSaving;
  }

  function setEditDisabled(isDisabled) {
    editForm?.querySelectorAll('input, textarea, select, button').forEach((field) => {
      field.disabled = isDisabled;
    });
  }

  function getStatusLabel(status) {
    const labels = {
      active: 'Activo',
      paused: 'Pausado',
      archived: 'Archivado'
    };
    return labels[status] || 'Activo';
  }

  function formatDate(value) {
    const date = new Date(`${value}T12:00:00`);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }

  function clampPercent(value) {
    return Math.max(0, Math.min(100, Number(value) || 0));
  }

  function todayISO() {
    const today = new Date();
    today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
    return today.toISOString().slice(0, 10);
  }

  function isFutureOrToday(value) {
    return !value || value >= todayISO();
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }
});
