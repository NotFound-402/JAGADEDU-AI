
const dashboardMenuToggle = document.getElementById("menuToggle");
const dashboardNav = document.getElementById("dashboardNav");
const dashboardThemeButton = document.getElementById("themeButton");

const API_DASHBOARD = "api/get-dashboard.php";

// ================================
// MENU MOBILE
// ================================

if (dashboardMenuToggle && dashboardNav) {
    dashboardMenuToggle.addEventListener("click", function () {
        const isOpen = !dashboardNav.classList.contains("show");

        dashboardNav.classList.toggle("show", isOpen);
        dashboardMenuToggle.setAttribute("aria-expanded", String(isOpen));
        dashboardMenuToggle.textContent = isOpen ? "✕" : "☰";
    });

    dashboardNav.querySelectorAll("a").forEach(function (link) {
        link.addEventListener("click", function () {
            dashboardNav.classList.remove("show");
            dashboardMenuToggle.setAttribute("aria-expanded", "false");
            dashboardMenuToggle.textContent = "☰";
        });
    });
}

// ================================
// THEME
// ================================

function updateDashboardTheme() {
    if (!dashboardThemeButton) return;

    const isLight = document.body.classList.contains("light-mode");

    dashboardThemeButton.textContent = isLight ? "☀" : "☾";
    dashboardThemeButton.setAttribute("aria-pressed", String(isLight));
    dashboardThemeButton.setAttribute(
        "aria-label",
        isLight ? "Aktifkan tema gelap" : "Aktifkan tema terang"
    );
}

if (dashboardThemeButton) {
    try {
        const theme = localStorage.getItem("jagateduTheme");

        if (theme === "light") {
            document.body.classList.add("light-mode");
        }
    } catch (error) {
        console.error("Gagal membaca tema:", error);
    }

    updateDashboardTheme();

    dashboardThemeButton.addEventListener("click", function () {
        const isLight = document.body.classList.toggle("light-mode");

        try {
            localStorage.setItem(
                "jagateduTheme",
                isLight ? "light" : "dark"
            );
        } catch (error) {
            console.error("Gagal menyimpan tema:", error);
        }

        updateDashboardTheme();
    });
}

// ================================
// HELPER
// ================================

function escapeDashboardText(value) {
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

function parseDashboardArray(value) {
    if (Array.isArray(value)) return value;

    if (typeof value === "string") {
        try {
            const parsed = JSON.parse(value);
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }

    return [];
}

function setJourneyState(id, label, complete) {
    const step = document.getElementById(id);

    if (!step) return;

    step.classList.toggle("journey-done", complete);

    const state = step.querySelector(".journey-state");

    if (state) state.textContent = label;
}

// ================================
// RENDER SKILLS
// ================================

function renderDashboardSkills(scores) {
    const container = document.getElementById("dashboardSkills");
    const empty = document.getElementById("skillEmpty");

    if (!container || !empty) return;

    if (!Array.isArray(scores) || scores.length === 0) {
        container.innerHTML = "";
        empty.hidden = false;
        return;
    }

    empty.hidden = true;

    container.innerHTML = scores.map(function (item) {
        const name = escapeDashboardText(item.name);
        const score = Math.max(
            0,
            Math.min(100, Number(item.final_score) || 0)
        );

        const roundedScore = Math.round(score);

        return `
            <div class="dashboard-skill-row">
                <div class="dashboard-skill-label">
                    <span>${name}</span>
                    <strong>${roundedScore}%</strong>
                </div>

                <div
                    class="dashboard-skill-track"
                    role="progressbar"
                    aria-label="Nilai ${name}"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="${roundedScore}"
                >
                    <span style="width: ${roundedScore}%"></span>
                </div>
            </div>
        `;
    }).join("");
}

// ================================
// RENDER AI ANALYSIS
// ================================

function renderDashboardAnalysis(analysis) {
    const reflection = document.getElementById("dashboardReflection");
    const career = document.getElementById("dashboardCareer");
    const careerEmpty = document.getElementById("careerEmpty");
    const interests = document.getElementById("dashboardInterests");
    const interestEmpty = document.getElementById("interestEmpty");

    if (analysis) {
        const strengths = parseDashboardArray(analysis.strengths);
        const weaknesses = parseDashboardArray(analysis.weaknesses);
        const recommendations = parseDashboardArray(analysis.recommendations);

        if (reflection) {
            reflection.textContent =
                analysis.summary || "Analisis belajar belum tersedia.";
        }

        if (career && careerEmpty) {
            const recommendation = recommendations[0];

            career.innerHTML = `
                <span class="career-direction-label">
                    Rekomendasi AI
                </span>
                <h3>${escapeDashboardText(
                    typeof recommendation === "string"
                        ? recommendation
                        : "Pengembangan keterampilan"
                )}</h3>
                <p>
                    ${escapeDashboardText(
                        strengths.length
                            ? "Kekuatan: " + strengths.slice(0, 2).join(", ")
                            : "Terus tingkatkan kemampuan melalui latihan."
                    )}
                </p>
            `;

            careerEmpty.hidden = true;
        }

        if (interests && interestEmpty) {
            interests.innerHTML = "";
            interestEmpty.hidden = false;
            interestEmpty.textContent =
                "Data eksplorasi minat belum tersedia. Rekomendasi di atas berasal dari analisis keterampilan AI.";
        }

    } else {
        if (reflection) {
            reflection.textContent =
                "Belum ada analisis AI. Selesaikan asesmen untuk mendapatkan analisis perkembanganmu.";
        }

        if (career) career.innerHTML = "";
        if (careerEmpty) careerEmpty.hidden = false;

        if (interests) interests.innerHTML = "";
        if (interestEmpty) interestEmpty.hidden = false;
    }
}

// ================================
// RENDER ROADMAP SUMMARY
// ================================

function renderDashboardRoadmap(roadmap, items) {
    const container = document.getElementById("roadmapSummary");

    if (!container) return;

    if (!roadmap) {
        container.innerHTML = `
            <p class="dashboard-empty">
                Roadmap belum tersedia. Selesaikan asesmen untuk membuat
                rencana belajar personal bersama AI.
            </p>
        `;
        return;
    }

    const safeTitle = escapeDashboardText(roadmap.title);
    const safeDescription = escapeDashboardText(roadmap.description);

    const total = Array.isArray(items) ? items.length : 0;

    const completed = Array.isArray(items)
        ? items.filter(item => item.status === "completed").length
        : 0;

    const progress = total > 0
        ? Math.round((completed / total) * 100)
        : 0;

    container.innerHTML = `
        <div class="roadmap-summary-card">
            <h3>${safeTitle}</h3>

            <p>${safeDescription}</p>

            <div class="roadmap-progress-info">
                <span>${completed} dari ${total} aktivitas selesai</span>
                <strong>${progress}%</strong>
            </div>

            <div
                class="roadmap-progress-track"
                role="progressbar"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-valuenow="${progress}"
            >
                <span style="width: ${progress}%"></span>
            </div>

            <p>Durasi: ${Number(roadmap.duration_days) || 30} hari</p>

            <a href="roadmap.php" class="roadmap-link">
                Buka 30 Hari Roadmap →
            </a>
        </div>
    `;
}

// ================================
// JOURNEY STATUS
// ================================

function renderJourney(data) {
    const hasAssessment = Boolean(data.session);
    const hasAnalysis = Boolean(data.analysis);
    const hasRoadmap = Boolean(data.roadmap);

    setJourneyState(
        "journeySkill",
        hasAssessment ? "Selesai" : "Belum",
        hasAssessment
    );

    setJourneyState(
        "journeyInterest",
        "Belum tersedia",
        false
    );

    setJourneyState(
        "journeyGrowth",
        hasAnalysis ? "Dianalisis AI" : "Opsional",
        hasAnalysis
    );

    setJourneyState(
        "journeyPlan",
        hasRoadmap ? "Tersedia" : "Belum",
        hasRoadmap
    );

    const status = document.getElementById("journeyStatus");

    if (status) {
        status.textContent = hasRoadmap
            ? "Roadmap tersedia"
            : hasAssessment
                ? "Asesmen selesai"
                : "Belum dimulai";
    }
}

// ================================
// LOAD DASHBOARD FROM DATABASE
// ================================

async function loadDashboard() {
    const errorMessage = document.getElementById("dashboardDataMessage");

    try {
        const response = await fetch(API_DASHBOARD, {
            method: "GET",
            headers: {
                "Accept": "application/json"
            },
            credentials: "same-origin"
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || "Gagal mengambil data dashboard."
            );
        }

        const data = result.data;

        renderDashboardSkills(data.scores || []);
        renderDashboardAnalysis(data.analysis);
        renderDashboardRoadmap(
            data.roadmap,
            data.roadmap_items || []
        );
        renderJourney(data);

    } catch (error) {
        console.error("Dashboard error:", error);

        if (errorMessage) {
            errorMessage.hidden = false;
            errorMessage.textContent =
                error.message || "Terjadi kesalahan saat memuat dashboard.";
        }

        renderDashboardSkills([]);
        renderDashboardAnalysis(null);
        renderDashboardRoadmap(null, []);
    }
}

loadDashboard();
