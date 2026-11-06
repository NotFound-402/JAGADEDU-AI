const chatToggle = document.getElementById("chatToggle");
const chatWindow = document.getElementById("chatWindow");
const chatClose = document.getElementById("chatClose");

const chatForm = document.getElementById("chatForm");
const chatInput = document.getElementById("chatInput");
const chatMessages = document.getElementById("chatMessages");
const chatSend = document.getElementById("chatSend");
const conversationList = document.getElementById("conversationList");
const newChatBtn = document.getElementById("newChatBtn");
const suggestionButtons = document.querySelectorAll(".chat-suggestion");

let conversationId = null;
let conversationPromise = null;

function updateCsrfToken(token) {
  if (!token) return;

  window.JAGATEDU_CSRF = token;
  const meta = document.querySelector('meta[name="csrf-token"]');

  if (meta) {
    meta.content = token;
  }
}

let csrfToken = window.JAGATEDU_CSRF || document.querySelector('meta[name="csrf-token"]')?.content || "";

function buildJsonHeaders(extraHeaders = {}) {
  return {
    "Content-Type": "application/json",
    "X-CSRF-TOKEN": csrfToken,
    ...extraHeaders,
  };
}

function isChatbotPage() {
  return !!(chatMessages && chatMessages.classList.contains("chat-message-list"));
}

function addMessage(message, sender) {
  if (!chatMessages) return null;

  if (isChatbotPage()) {
    const bubble = document.createElement("article");
    bubble.className = `chat-bubble ${sender === "user" ? "user-bubble" : "coach-bubble"}`;

    const avatar = document.createElement("span");
    avatar.className = "bubble-avatar";
    avatar.textContent = sender === "user" ? "U" : "✦";

    const wrapper = document.createElement("div");
    const author = document.createElement("strong");
    author.innerHTML = sender === "user" ? "Kamu" : "AI Coach <span>· Real-time</span>";

    const text = document.createElement("p");
    text.textContent = message;

    wrapper.appendChild(author);
    wrapper.appendChild(text);
    bubble.appendChild(avatar);
    bubble.appendChild(wrapper);
    chatMessages.appendChild(bubble);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    return bubble;
  }

  const bubble = document.createElement("div");
  bubble.classList.add("chat-message", sender === "user" ? "user-message" : "ai-message");
  bubble.textContent = message;
  chatMessages.appendChild(bubble);
  chatMessages.scrollTop = chatMessages.scrollHeight;
  return bubble;
}

async function loadConversations() {
  if (!conversationList) return;

  try {
    const response = await fetch("api/conversations.php");
    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error("Gagal mengambil riwayat.");
    }

    conversationList.innerHTML = "";

    if (result.data.length === 0) {
      conversationList.textContent = "Belum ada percakapan.";
      return;
    }

    result.data.forEach((conversation) => {
      const item = document.createElement("div");
      item.classList.add("conversation-wrapper");

      const button = document.createElement("button");
      button.type = "button";
      button.classList.add("conversation-item");
      button.dataset.id = conversation.id;
      button.textContent = conversation.title !== "Percakapan baru"
        ? conversation.title
        : conversation.last_message || "Percakapan baru";

      if (Number(conversation.id) === Number(conversationId)) {
        button.classList.add("active");
      }

      button.addEventListener("click", () => {
        openConversation(conversation.id);
      });

      const deleteButton = document.createElement("button");
      deleteButton.type = "button";
      deleteButton.classList.add("delete-conversation");
      deleteButton.textContent = "🗑️";
      deleteButton.title = "Hapus percakapan";
      deleteButton.setAttribute("aria-label", "Hapus percakapan");
      deleteButton.addEventListener("click", (event) => {
        event.stopPropagation();
        deleteConversation(conversation.id);
      });

      item.appendChild(button);
      item.appendChild(deleteButton);
      conversationList.appendChild(item);
    });
  } catch (error) {
    console.error("History Error:", error);
    conversationList.textContent = "Gagal memuat riwayat.";
  }
}

async function deleteConversation(id) {
  const confirmed = confirm("Apakah kamu yakin ingin menghapus percakapan ini?");
  if (!confirmed) return;

  try {
    const response = await fetch("api/delete-conversation.php", {
      method: "POST",
      headers: buildJsonHeaders(),
      body: JSON.stringify({ conversation_id: id, csrf_token: csrfToken }),
    });

    const result = await response.json();

    if (response.status === 419 && result?.csrf_token) {
      updateCsrfToken(result.csrf_token);
      csrfToken = result.csrf_token;
      throw new Error("Token sesi kedaluwarsa. Refresh halaman lalu coba lagi.");
    }

    if (!response.ok || !result.success) {
      throw new Error(result.message || "Gagal menghapus percakapan.");
    }

    if (Number(conversationId) === Number(id)) {
      conversationId = null;
      chatMessages.innerHTML = "";
      addMessage("Halo! Saya JagatEDU AI. Ada yang ingin kamu ceritakan?", "ai");
    }

    await loadConversations();
  } catch (error) {
    console.error("Delete Error:", error);
    alert(error.message);
  }
}

async function createConversation() {
  if (conversationId) return conversationId;
  if (conversationPromise) return conversationPromise;

  conversationPromise = (async () => {
    const response = await fetch("api/conversations.php", {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": csrfToken,
      },
      body: JSON.stringify({ csrf_token: csrfToken }),
    });

    const result = await response.json();

    if (response.status === 419 && result?.csrf_token) {
      updateCsrfToken(result.csrf_token);
      csrfToken = result.csrf_token;
      throw new Error("Token sesi kedaluwarsa. Refresh halaman lalu coba lagi.");
    }

    if (!response.ok || !result.success) {
      throw new Error("Gagal membuat percakapan.");
    }

    conversationId = result.data.conversation_id;
    await loadConversations();
    return conversationId;
  })();

  try {
    return await conversationPromise;
  } finally {
    conversationPromise = null;
  }
}

async function openConversation(id) {
  if (!chatMessages) return;

  try {
    const response = await fetch(`api/messages.php?conversation_id=${encodeURIComponent(id)}`);
    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error("Gagal membuka percakapan.");
    }

    conversationId = Number(id);
    document.querySelectorAll(".conversation-item").forEach((item) => {
      item.classList.remove("active");
    });

    const activeItem = document.querySelector(`.conversation-item[data-id="${id}"]`);
    if (activeItem) {
      activeItem.classList.add("active");
    }

    chatMessages.innerHTML = "";

    if (result.data.length === 0) {
      addMessage("Halo! Ada yang ingin kamu ceritakan?", "ai");
    }

    result.data.forEach((message) => {
      addMessage(message.message, message.sender);
    });
  } catch (error) {
    console.error("Open Conversation Error:", error);
    addMessage(error.message, "ai");
  }
}

if (newChatBtn) {
  newChatBtn.addEventListener("click", () => {
    conversationId = null;
    conversationPromise = null;
    chatMessages.innerHTML = "";
    addMessage("Halo! Saya JagatEDU AI. Ada yang ingin kamu ceritakan?", "ai");
    chatInput.focus();
  });
}

if (chatToggle && chatWindow) {
  chatToggle.addEventListener("click", async () => {
    chatWindow.classList.toggle("active");
    if (chatWindow.classList.contains("active")) {
      await loadConversations();
    }
  });
}

if (chatClose) {
  chatClose.addEventListener("click", () => {
    chatWindow.classList.remove("active");
  });
}

if (suggestionButtons.length) {
  suggestionButtons.forEach((button) => {
    button.addEventListener("click", () => {
      if (!chatInput || !chatForm) return;
      chatInput.value = button.dataset.chatPrompt || "";
      chatForm.requestSubmit();
    });
  });
}

if (chatForm && chatInput) {
  chatForm.addEventListener("submit", async (event) => {
    event.preventDefault();

    const message = chatInput.value.trim();
    if (!message) return;

    addMessage(message, "user");
    chatInput.value = "";

    if (chatSend) {
      chatSend.disabled = true;
    }

    const loading = addMessage("JagatEDU AI sedang berpikir...", "ai");

    try {
      const currentConversationId = await createConversation();

      const response = await fetch("api/chat.php", {
        method: "POST",
        headers: buildJsonHeaders(),
        body: JSON.stringify({
          message,
          conversation_id: currentConversationId,
          csrf_token: csrfToken,
        }),
      });

      const result = await response.json();
      loading.remove();

      if (response.status === 419 && result?.csrf_token) {
        updateCsrfToken(result.csrf_token);
        csrfToken = result.csrf_token;
        throw new Error("Token sesi kedaluwarsa. Refresh halaman lalu coba lagi.");
      }

      if (!response.ok || !result.success) {
        throw new Error(result.message || "Terjadi kesalahan.");
      }

      addMessage(result.message, "ai");
      await loadConversations();
    } catch (error) {
      loading.remove();
      console.error("Chat Error:", error);
      addMessage(error.message, "ai");
    } finally {
      if (chatSend) {
        chatSend.disabled = false;
      }
      chatInput.focus();
    }
  });
}

if (conversationList) {
  loadConversations();
}
