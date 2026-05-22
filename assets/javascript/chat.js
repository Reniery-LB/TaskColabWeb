// assets/javascript/chat.js
document.addEventListener('DOMContentLoaded', () => {
  const section = document.getElementById('chat');
  if (!section) return;

  const apiBase = window.API_BASE_CHAT || '/PROYECTO_GESTOR_TAREAS/assets/app/endpointsChat';
  const usersApi = window.API_BASE || '/PROYECTO_GESTOR_TAREAS/assets/app/endpoints';
  const currentUser = window.CURRENT_USER || {};

  const conversationList = document.getElementById('chat-conversations-list');
  const messagesPanel = document.getElementById('chat-messages');
  const chatTitle = document.getElementById('chat-active-title');
  const chatMeta = document.getElementById('chat-active-meta');
  const chatStatus = document.getElementById('chat-status');
  const form = document.getElementById('chat-form');
  const input = document.getElementById('chat-message-input');
  const submit = document.getElementById('chat-send');
  const userSelect = document.getElementById('chat-user-select');
  const directButton = document.getElementById('chat-start-direct');
  const emptyState = document.getElementById('chat-empty-state');
  const deleteActiveButton = document.getElementById('chat-delete-active');

  let conversations = [];
  let activeConversationId = Number(localStorage.getItem('taskcolab_active_conversation_id')) || null;
  let messagesById = new Map();
  let lastMessageId = 0;
  let isLoadingMessages = false;

  loadUsers();
  loadConversations();

  window.addEventListener('taskcolab:projectChanged', () => {
    loadConversations();
  });

  setInterval(() => {
    if (document.hidden) return;
    loadConversations({ quiet: true });
    if (activeConversationId) {
      loadMessages(activeConversationId, { append: true, quiet: true });
    }
  }, 7000);

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const body = (input?.value || '').trim();
    if (!activeConversationId || !body) return;

    try {
      setSending(true);
      const response = await fetch(`${apiBase}/send_message.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          conversation_id: activeConversationId,
          body
        })
      });
      const json = await response.json();
      if (!response.ok || !json.ok) {
        throw new Error(json.message || 'No se pudo enviar el mensaje');
      }

      input.value = '';
      appendMessages(json.data ? [json.data] : []);
      await loadConversations({ quiet: true });
    } catch (error) {
      showChatError(error.message || 'Error al enviar mensaje');
    } finally {
      setSending(false);
      input?.focus();
    }
  });

  directButton?.addEventListener('click', async () => {
    const userId = Number(userSelect?.value || 0);
    if (!userId) {
      showChatError('Selecciona un usuario');
      return;
    }

    try {
      directButton.disabled = true;
      const response = await fetch(`${apiBase}/create_direct.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId })
      });
      const json = await response.json();
      if (!response.ok || !json.ok) {
        throw new Error(json.message || 'No se pudo abrir el chat privado');
      }

      activeConversationId = Number(json.conversation.id);
      localStorage.setItem('taskcolab_active_conversation_id', String(activeConversationId));
      await loadConversations();
      await loadMessages(activeConversationId, { append: false });
    } catch (error) {
      showChatError(error.message || 'Error al abrir chat privado');
    } finally {
      directButton.disabled = false;
    }
  });

  deleteActiveButton?.addEventListener('click', () => {
    const conversation = getActiveConversation();
    if (!conversation) return;
    requestDeleteConversation(conversation);
  });

  async function loadConversations({ quiet = false } = {}) {
    try {
      if (!quiet) setStatus('Cargando...');
      const response = await fetch(`${apiBase}/list_conversations.php?t=${Date.now()}`, {
        cache: 'no-cache'
      });
      const json = await response.json();
      if (!response.ok || !json.ok) {
        throw new Error(json.message || 'No se pudieron cargar conversaciones');
      }

      conversations = json.conversations || [];
      if (!activeConversationId || !conversations.some(item => Number(item.id) === activeConversationId)) {
        activeConversationId = conversations[0]?.id ? Number(conversations[0].id) : null;
      }

      if (activeConversationId) {
        localStorage.setItem('taskcolab_active_conversation_id', String(activeConversationId));
      }

      renderConversations();
      renderActiveHeader();

      if (activeConversationId && messagesById.size === 0) {
        await loadMessages(activeConversationId, { append: false, quiet: true });
      }

      setStatus('Actualizado');
    } catch (error) {
      console.error('Error cargando conversaciones:', error);
      if (!quiet) showChatError(error.message || 'Error al cargar conversaciones');
      setStatus('Sin conexión');
    }
  }

  async function loadMessages(conversationId, { append = false, quiet = false } = {}) {
    if (isLoadingMessages) return;
    isLoadingMessages = true;

    try {
      if (!quiet) setStatus('Cargando mensajes...');
      const afterId = append ? lastMessageId : 0;
      const response = await fetch(`${apiBase}/get_messages.php?conversation_id=${conversationId}&after_id=${afterId}&t=${Date.now()}`, {
        cache: 'no-cache'
      });
      const json = await response.json();
      if (!response.ok || !json.ok) {
        throw new Error(json.message || 'No se pudieron cargar mensajes');
      }

      if (!append) {
        messagesById = new Map();
        messagesPanel.innerHTML = '';
        lastMessageId = 0;
      }

      appendMessages(json.messages || []);
      setStatus('Actualizado');
    } catch (error) {
      console.error('Error cargando mensajes:', error);
      if (!quiet) showChatError(error.message || 'Error al cargar mensajes');
    } finally {
      isLoadingMessages = false;
    }
  }

  async function loadUsers() {
    if (!userSelect) return;

    try {
      const response = await fetch(`${usersApi}/list_users.php?t=${Date.now()}`, { cache: 'no-cache' });
      const json = await response.json();
      if (!response.ok || !json.ok) {
        throw new Error(json.message || 'No se pudieron cargar usuarios');
      }

      const users = (json.users || [])
        .filter(user => Number(user.is_active) === 1 && Number(user.id) !== Number(currentUser.id))
        .sort((a, b) => String(a.name).localeCompare(String(b.name), 'es'));

      userSelect.innerHTML = '<option value="">Chat privado con...</option>' + users.map(user => (
        `<option value="${user.id}">${escapeHtml(user.name)} (${escapeHtml(user.email)})</option>`
      )).join('');
    } catch (error) {
      console.error('Error cargando usuarios para chat:', error);
      userSelect.innerHTML = '<option value="">Usuarios no disponibles</option>';
    }
  }

  function renderConversations() {
    if (!conversationList) return;

    if (!conversations.length) {
      conversationList.innerHTML = '<div class="chat-empty-list">Sin conversaciones</div>';
      return;
    }

    conversationList.innerHTML = conversations.map(conversation => {
      const active = Number(conversation.id) === Number(activeConversationId);
      const lastMessage = conversation.last_message_body || 'Sin mensajes';
      const unread = Number(conversation.unread_count || 0);
      const canDelete = Boolean(conversation.can_delete);
      return `
        <button type="button" class="chat-conversation ${active ? 'active' : ''}" data-conversation-id="${conversation.id}">
          <span class="chat-conversation-icon">${getTypeInitial(conversation.type)}</span>
          <span class="chat-conversation-main">
            <strong>${escapeHtml(conversation.title || 'Conversación')}</strong>
            <small>${escapeHtml(lastMessage)}</small>
          </span>
          <span class="chat-conversation-actions">
            ${unread > 0 ? `<span class="chat-unread">${unread}</span>` : ''}
            ${canDelete ? `<span class="chat-delete-mini" role="button" tabindex="0" aria-label="Eliminar chat" data-delete-conversation="${conversation.id}"></span>` : ''}
          </span>
        </button>
      `;
    }).join('');

    conversationList.querySelectorAll('.chat-conversation').forEach(button => {
      button.addEventListener('click', async () => {
        const conversationId = Number(button.dataset.conversationId);
        if (!conversationId || conversationId === activeConversationId) return;

        activeConversationId = conversationId;
        localStorage.setItem('taskcolab_active_conversation_id', String(activeConversationId));
        messagesById = new Map();
        lastMessageId = 0;
        renderConversations();
        renderActiveHeader();
        await loadMessages(activeConversationId, { append: false });
      });
    });

    conversationList.querySelectorAll('[data-delete-conversation]').forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const conversation = conversations.find(item => Number(item.id) === Number(button.dataset.deleteConversation));
        if (conversation) requestDeleteConversation(conversation);
      });

      button.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        event.stopPropagation();
        const conversation = conversations.find(item => Number(item.id) === Number(button.dataset.deleteConversation));
        if (conversation) requestDeleteConversation(conversation);
      });
    });
  }

  function renderActiveHeader() {
    const conversation = getActiveConversation();

    if (!conversation) {
      if (chatTitle) chatTitle.textContent = 'Chat';
      if (chatMeta) chatMeta.textContent = 'Selecciona una conversación';
      if (emptyState) emptyState.style.display = 'flex';
      if (form) form.style.display = 'none';
      if (deleteActiveButton) deleteActiveButton.hidden = true;
      return;
    }

    if (chatTitle) chatTitle.textContent = conversation.title || 'Conversación';
    if (chatMeta) chatMeta.textContent = getTypeLabel(conversation);
    if (emptyState) emptyState.style.display = 'none';
    if (form) form.style.display = 'flex';
    if (deleteActiveButton) {
      deleteActiveButton.hidden = !conversation.can_delete;
      deleteActiveButton.textContent = conversation.type === 'project' ? 'Borrar chat' : 'Eliminar chat';
    }
  }

  function requestDeleteConversation(conversation) {
    if (!conversation?.can_delete) return;

    const title = conversation.type === 'project' ? 'Borrar chat de proyecto' : 'Eliminar chat privado';
    const message = conversation.type === 'project'
      ? `¿Borrar "${escapeHtml(conversation.title)}"?<br>El chat dejará de estar disponible para el equipo.`
      : `¿Eliminar "${escapeHtml(conversation.title)}" de tus chats privados?`;

    const onConfirmar = () => deleteConversation(Number(conversation.id));

    if (typeof window.configurarAlerta === 'function') {
      window.configurarAlerta(title, message, 'alerta', {
        textoConfirmar: 'Eliminar',
        onConfirmar
      });
    } else if (confirm(stripHtml(message))) {
      onConfirmar();
    }
  }

  async function deleteConversation(conversationId) {
    try {
      const response = await fetch(`${apiBase}/delete_conversation.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ conversation_id: conversationId })
      });
      const json = await response.json();
      if (!response.ok || !json.ok) {
        throw new Error(json.message || 'No se pudo eliminar el chat');
      }

      if (Number(activeConversationId) === Number(conversationId)) {
        activeConversationId = null;
        localStorage.removeItem('taskcolab_active_conversation_id');
        messagesById = new Map();
        lastMessageId = 0;
        if (messagesPanel) messagesPanel.innerHTML = '';
      }

      await loadConversations();
      setStatus('Chat eliminado');
    } catch (error) {
      showChatError(error.message || 'Error al eliminar chat');
    }
  }

  function appendMessages(messages) {
    if (!messagesPanel) return;

    messages.forEach(message => {
      const id = Number(message.id);
      if (!id || messagesById.has(id)) return;

      messagesById.set(id, message);
      lastMessageId = Math.max(lastMessageId, id);
      messagesPanel.insertAdjacentHTML('beforeend', renderMessage(message));
    });

    if (messages.length > 0) {
      messagesPanel.scrollTop = messagesPanel.scrollHeight;
    }

    if (messagesById.size === 0) {
      messagesPanel.innerHTML = '<div class="chat-empty-messages">Aún no hay mensajes</div>';
    } else {
      const empty = messagesPanel.querySelector('.chat-empty-messages');
      if (empty) empty.remove();
    }
  }

  function renderMessage(message) {
    const mine = Number(message.user_id) === Number(currentUser.id) || message.is_mine;
    return `
      <article class="chat-message ${mine ? 'mine' : ''}" data-message-id="${message.id}">
        <div class="chat-message-bubble">
          <div class="chat-message-top">
            <strong>${escapeHtml(mine ? 'Tú' : message.user_name || 'Usuario')}</strong>
            <time>${formatTime(message.created_at)}</time>
          </div>
          <p>${escapeHtml(message.body).replace(/\n/g, '<br>')}</p>
        </div>
      </article>
    `;
  }

  function getActiveConversation() {
    return conversations.find(item => Number(item.id) === Number(activeConversationId)) || null;
  }

  function getTypeInitial(type) {
    const initials = { direct: 'D', group: 'G', project: 'P', task: 'T' };
    return initials[type] || 'C';
  }

  function getTypeLabel(conversation) {
    const labels = {
      direct: 'Privado',
      group: 'Grupo',
      project: conversation.project_name || 'Proyecto',
      task: conversation.task_title || 'Tarea'
    };
    return labels[conversation.type] || 'Conversación';
  }

  function formatTime(value) {
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleString('es-MX', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function setSending(isSending) {
    if (submit) submit.disabled = isSending;
    if (input) input.disabled = isSending;
  }

  function setStatus(text) {
    if (chatStatus) chatStatus.textContent = text;
  }

  function showChatError(message) {
    if (typeof window.configurarAlerta === 'function') {
      window.configurarAlerta('Chat', message, 'alerta', { soloAceptar: true });
    } else {
      alert(message);
    }
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function stripHtml(value) {
    return String(value || '').replace(/<[^>]*>/g, ' ');
  }
});
