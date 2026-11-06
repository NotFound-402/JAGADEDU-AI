
const roadmapItemsContainer = document.getElementById("roadmapItems");
const roadmapMessage = document.getElementById("roadmapMessage");

let roadmapData = null;
let isSaving = false;

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
        "Accept": "application/json",
        "X-CSRF-TOKEN": csrfToken,
        ...extraHeaders,
    };
}

// ===================================
// HELPER
// ===================================

function escapeRoadmapText(value) {
    return String(value ?? "").replace(/[&<>"']/g, function (character) {
        const entities = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#39;"
        };

        return entities[character];
    });
}

function showRoadmapMessage(message, isError = true) {
    if (!roadmapMessage) return;

    roadmapMessage.hidden = false;
    roadmapMessage.textContent = message;

    roadmapMessage.classList.toggle("success-message", !isError);
}

function hideRoadmapMessage() {
    if (!roadmapMessage) return;

    roadmapMessage.hidden = true;
    roadmapMessage.textContent = "";
}

// ===================================
// LOAD ROADMAP
// ===================================

async function loadRoadmap() {
    try {
        const response = await fetch("api/get-roadmap.php", {
            credentials: "same-origin",
            headers: {
                "Accept": "application/json"
            }
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Gagal memuat roadmap.");
        }

        roadmapData = result.data;

        renderRoadmapInfo();
        renderRoadmapItems();

    } catch (error) {
        console.error(error);

        showRoadmapMessage(error.message);

        roadmapItemsContainer.innerHTML = `
            <p class="dashboard-empty">
                Roadmap tidak dapat dimuat.
                Silakan refresh halaman.
            </p>
        `;
    }
}

// ===================================
// ROADMAP INFORMATION
// ===================================

function renderRoadmapInfo() {
    const roadmap = roadmapData.roadmap;

    document.getElementById("roadmapTitle").textContent =
        roadmap.title;

    document.getElementById("roadmapDescription").textContent =
        roadmap.description;

    updateRoadmapProgress();
}

// ===================================
// RENDER ACTIVITIES
// ===================================

function renderRoadmapItems() {
    const items = roadmapData.items;

    if (!items || items.length === 0) {
        roadmapItemsContainer.innerHTML = `
            <p class="dashboard-empty">
                Belum ada aktivitas roadmap.
            </p>
        `;
        return;
    }

    roadmapItemsContainer.innerHTML = items.map(function (item) {
        const isCompleted = item.status === "completed";
        const isInProgress = item.status === "in_progress";
        const statusClass = isCompleted ? "completed" : isInProgress ? "in-progress" : "pending";

        let statusLabel = "Belum dimulai";

        if (isCompleted) {
            statusLabel = "Selesai";
        } else if (isInProgress) {
            statusLabel = "Sedang dikerjakan";
        }

        return `
            <article
                class="roadmap-item roadmap-${statusClass}"
                data-item-id="${Number(item.id)}"
            >
                <div class="roadmap-item-day">
                    DAY ${Number(item.day_number)}
                </div>

                <div class="roadmap-item-content">
                    <div class="roadmap-item-heading">
                        <h3>${escapeRoadmapText(item.title)}</h3>

                        <span class="roadmap-item-status roadmap-status-${statusClass}">
                            ${statusLabel}
                        </span>
                    </div>

                    <p>${escapeRoadmapText(item.description)}</p>

                    <div class="roadmap-item-actions">

                        <label>
                            <input
                                type="checkbox"
                                class="roadmap-checkbox"
                                data-item-id="${Number(item.id)}"
                                ${isCompleted ? "checked" : ""}
                            >
                            Tandai selesai
                        </label>

                        <select
                            class="roadmap-status-select"
                            data-item-id="${Number(item.id)}"
                            aria-label="Status aktivitas hari ${Number(item.day_number)}"
                        >
                            <option value="pending"
                                ${item.status === "pending" ? "selected" : ""}>
                                Belum dimulai
                            </option>

                            <option value="in_progress"
                                ${item.status === "in_progress" ? "selected" : ""}>
                                Sedang dikerjakan
                            </option>

                            <option value="completed"
                                ${item.status === "completed" ? "selected" : ""}>
                                Selesai
                            </option>
                        </select>

                    </div>
                </div>
            </article>
        `;
    }).join("");
}

// ===================================
// UPDATE PROGRESS
// ===================================

function updateRoadmapProgress() {
    const items = roadmapData.items;

    const total = items.length;

    const completed = items.filter(function (item) {
        return item.status === "completed";
    }).length;

    const notCompleted = Math.max(total - completed, 0);

    const percentage = total > 0
        ? Math.round((completed / total) * 100)
        : 0;

    document.getElementById("totalActivities").textContent = total;

    document.getElementById("completedActivities").textContent =
        completed;

    document.getElementById("pendingActivities").textContent =
        notCompleted;

    document.getElementById("roadmapProgressText").textContent =
        `${completed} dari ${total} aktivitas selesai`;

    document.getElementById("roadmapPercentage").textContent =
        `${percentage}%`;

    const progressBar = document.getElementById("roadmapProgressBar");

    progressBar.style.width = `${percentage}%`;
    progressBar.parentElement.setAttribute("aria-valuenow", percentage);
}

// ===================================
// SAVE ACTIVITY STATUS
// ===================================

async function updateActivityStatus(itemId, status) {
    if (isSaving) {
        showRoadmapMessage(
            "Tunggu proses penyimpanan sebelumnya selesai."
        );
        return false;
    }

    const item = roadmapData.items.find(function (entry) {
        return Number(entry.id) === Number(itemId);
    });

    if (!item) return false;

    const oldStatus = item.status;

    isSaving = true;
    hideRoadmapMessage();

    const card = roadmapItemsContainer.querySelector(
        `[data-item-id="${Number(itemId)}"]`
    );

    if (card) {
        card.classList.add("roadmap-saving");
    }

    try {
        const response = await fetch("api/update-roadmap-progress.php", {
            method: "POST",
            credentials: "same-origin",
            headers: buildJsonHeaders(),
            body: JSON.stringify({
                item_id: Number(itemId),
                status: status,
                csrf_token: csrfToken
            })
        });

        const result = await response.json();

        if (response.status === 419 && result?.csrf_token) {
            updateCsrfToken(result.csrf_token);
            csrfToken = result.csrf_token;
            throw new Error("Token sesi kedaluwarsa. Segarkan halaman lalu coba lagi.");
        }

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || "Gagal menyimpan progres."
            );
        }

        item.status = status;

        if (result.data && result.data.completed_at !== undefined) {
            item.completed_at = result.data.completed_at;
        }

        renderRoadmapItems();
        updateRoadmapProgress();

        showRoadmapMessage(
            "Progres belajar berhasil disimpan!",
            false
        );

        return true;

    } catch (error) {
        console.error(error);

        item.status = oldStatus;

        renderRoadmapItems();
        updateRoadmapProgress();

        showRoadmapMessage(error.message);

        return false;

    } finally {
        isSaving = false;
    }
}

// ===================================
// CHECKBOX EVENT
// ===================================

roadmapItemsContainer.addEventListener("change", async function (event) {
    const target = event.target;

    if (
        target.classList.contains("roadmap-checkbox") ||
        target.classList.contains("roadmap-status-select")
    ) {
        const itemId = Number(target.dataset.itemId);

        const status = target.classList.contains("roadmap-checkbox")
            ? (target.checked ? "completed" : "pending")
            : target.value;

        await updateActivityStatus(itemId, status);
    }
});

// ===================================
// START
// ===================================

loadRoadmap();
