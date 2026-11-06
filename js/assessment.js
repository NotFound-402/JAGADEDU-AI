const timerEl = document.getElementById("timer");
const progressText = document.getElementById("progressText");
const progressBar = document.getElementById("progressBar");
const questionList = document.getElementById("questionList");
const questionCard = document.getElementById("questionCard");
const sidebarCount = document.getElementById("sidebarCount");
const prevQuestionBtn = document.getElementById("prevQuestion");
const nextQuestionBtn = document.getElementById("nextQuestion");
const finishAssessmentBtn = document.getElementById("finishAssessment");
const toast = document.getElementById("toast");

const sessionId = window.ASSESSMENT_SESSION_ID;

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

let questions = [];
let currentIndex = 0;
let totalSeconds = 60 * 60;

function escapeHTML(value) {
  return String(value ?? "").replace(
    /[&<>"']/g,
    (char) =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
      })[char],
  );
}

function getAnsweredCount() {
  return questions.filter((question) => {
    if (question.type === "multiple_choice") {
      return question.selectedOptionId !== null;
    }

    return question.codeSubmission.trim().length > 0;
  }).length;
}

function formatTime(seconds) {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;

  return `${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`;
}

function updateTimer() {
  timerEl.textContent = formatTime(totalSeconds);
}

function showToast(message) {
  toast.textContent = message;
  toast.classList.add("show");

  window.clearTimeout(showToast.timeoutId);

  showToast.timeoutId = window.setTimeout(() => {
    toast.classList.remove("show");
  }, 2200);
}

const saveTimers = {};

async function saveAnswer(question) {
  const payload = {
    session_id: sessionId,
    question_id: question.id,
  };

  if (question.type === "multiple_choice") {
    if (question.selectedOptionId === null) return;

    payload.selected_option_id = question.selectedOptionId;
  } else {
    payload.code_submission = question.codeSubmission;
  }

  try {
    const response = await fetch("/api/save-answer.php", {
      method: "POST",
      headers: buildJsonHeaders(),
      body: JSON.stringify({
        ...payload,
        csrf_token: csrfToken,
      }),
    });

    const data = await response.json();

    if (response.status === 419 && data?.csrf_token) {
      updateCsrfToken(data.csrf_token);
      csrfToken = data.csrf_token;
      throw new Error("Token sesi kedaluwarsa. Silakan refresh halaman dan coba lagi.");
    }

    if (!response.ok || !data.success) {
      throw new Error(data.message || "Gagal menyimpan jawaban.");
    }

    console.log(`Jawaban soal ${question.id} berhasil disimpan.`);
  } catch (error) {
    console.error("Autosave Error:", error);
    showToast(`Gagal menyimpan jawaban soal ${question.id}.`);
  }
}

function scheduleCodeSave(question) {
  clearTimeout(saveTimers[question.id]);

  saveTimers[question.id] = setTimeout(() => {
    saveAnswer(question);
  }, 800);
}

function updateProgress() {
  const answeredCount = getAnsweredCount();
  const percent = questions.length
    ? (answeredCount / questions.length) * 100
    : 0;

  progressText.textContent = `${answeredCount}/${questions.length}`;
  progressBar.style.width = `${percent}%`;
  sidebarCount.textContent = `${answeredCount}/${questions.length} selesai`;
}

function getQuestionStatus(index) {
  if (index === currentIndex) return "active";

  const question = questions[index];

  if (question.type === "multiple_choice") {
    return question.selectedOptionId !== null ? "done" : "pending";
  }

  return question.codeSubmission.trim().length > 0 ? "done" : "pending";
}

function renderSidebar() {
  questionList.innerHTML = questions
    .map((question, index) => {
      const status = getQuestionStatus(index);

      const label =
        status === "done" ? "Selesai" : status === "active" ? "Aktif" : "Belum";

      return `
            <button type="button"
                class="question-item ${status === "active" ? "active" : ""} ${status === "done" ? "done" : ""}"
                data-index="${index}">

                <span class="question-number">${index + 1}</span>

                <span class="question-meta">
                    <strong>Soal ${index + 1}</strong>
                    <span>${escapeHTML(question.title)}</span>
                </span>

                <span class="question-status ${status}">
                    ${label}
                </span>
            </button>
        `;
    })
    .join("");

  document.querySelectorAll(".question-item").forEach((button) => {
    button.addEventListener("click", () => {
      currentIndex = Number(button.dataset.index);
      renderAll();
    });
  });
}

function renderQuestion() {
  const question = questions[currentIndex];

  if (!question) return;

  const header = `
        <div class="question-header">
            <div class="question-header-left">
                <div class="question-index">${currentIndex + 1}</div>
                <h3 class="question-title">Soal ${currentIndex + 1}</h3>
            </div>

            <span class="question-tag">
                ${escapeHTML(question.title)}
            </span>
        </div>
    `;

  if (question.type === "multiple_choice") {
    const optionsHTML = question.options
      .map((option, index) => {
        const checked =
          Number(question.selectedOptionId) === Number(option.id)
            ? "checked"
            : "";

        const letter = String.fromCharCode(65 + index);

        return `
                <div class="option-item">
                    <input
                        type="radio"
                        name="option-${question.id}"
                        id="option-${question.id}-${option.id}"
                        value="${option.id}"
                        ${checked}
                    >

                    <label for="option-${question.id}-${option.id}">
                        <span class="option-letter">${letter}</span>
                        <span>${escapeHTML(option.option_text)}</span>
                    </label>
                </div>
            `;
      })
      .join("");

    questionCard.innerHTML = `
            ${header}

            <div class="question-content">
                <p class="question-text">
                    ${escapeHTML(question.question_text)}
                </p>

                <div class="answer-list">
                    ${optionsHTML}
                </div>
            </div>
        `;

    document
      .querySelectorAll(`input[name="option-${question.id}"]`)
      .forEach((radio) => {
        radio.addEventListener("change", (event) => {
          question.selectedOptionId = Number(event.target.value);

          renderSidebar();
          updateProgress();

          saveAnswer(question);
        });
      });

    return;
  }

  const code =
    question.codeSubmission !== ""
      ? question.codeSubmission
      : question.starter_code || "";

  const lineCount = Math.max(code.split("\n").length, 1);

  const lineNumbers = Array.from(
    { length: lineCount },
    (_, index) => index + 1,
  ).join("<br>");

  const language = escapeHTML(question.programming_language || "Code");

  questionCard.innerHTML = `
        ${header}

        <div class="question-content">
            <p class="question-text">
                ${escapeHTML(question.question_text)}
            </p>

            <div class="editor-box">
                <div class="editor-topbar">
                    <div class="editor-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>

                    <span class="editor-file">${language}</span>
                </div>

                <div class="editor-frame">
                    <div class="line-numbers">${lineNumbers}</div>

                    <textarea
                        class="editor-textarea"
                        spellcheck="false"
                        placeholder="Tulis kode jawaban lu di sini..."
                    >${escapeHTML(code)}</textarea>
                </div>
            </div>
        </div>
    `;

  const editorTextarea = document.querySelector(".editor-textarea");

  editorTextarea.addEventListener("input", (event) => {
    question.codeSubmission = event.target.value;

    const currentLineCount = Math.max(event.target.value.split("\n").length, 1);

    document.querySelector(".line-numbers").innerHTML = Array.from(
      { length: currentLineCount },
      (_, index) => index + 1,
    ).join("<br>");

    renderSidebar();
    updateProgress();
    scheduleCodeSave(question);
  });
}

function renderControls() {
  prevQuestionBtn.disabled = currentIndex === 0;

  nextQuestionBtn.textContent =
    currentIndex === questions.length - 1 ? "Soal Terakhir" : "Berikutnya";

  nextQuestionBtn.disabled = false;
}

function renderAll() {
  renderSidebar();
  renderQuestion();
  renderControls();
  updateProgress();
}

async function loadQuestions() {
  try {
    if (!sessionId) {
      throw new Error("Session assessment tidak ditemukan.");
    }

    const response = await fetch(
      `/api/get-questions.php?session_id=${encodeURIComponent(sessionId)}`,
    );

    const data = await response.json();

    if (!response.ok || !data.success) {
      throw new Error(data.message || "Gagal mengambil soal.");
    }

    questions = data.questions.map((question) => ({
      ...question,

      selectedOptionId: question.selected_option_id
        ? Number(question.selected_option_id)
        : null,

      codeSubmission: question.code_submission || "",

      options: question.options || [],
    }));

    if (questions.length === 0) {
      throw new Error("Belum ada soal assessment.");
    }

    renderAll();
    showToast(`${questions.length} soal berhasil dimuat dari database.`);
  } catch (error) {
    console.error("Assessment Error:", error);

    questionCard.textContent = error.message;
    showToast(error.message);
  }
}

prevQuestionBtn.addEventListener("click", () => {
  if (currentIndex > 0) {
    currentIndex--;
    renderAll();
  }
});

nextQuestionBtn.addEventListener("click", () => {
  if (currentIndex < questions.length - 1) {
    currentIndex++;
    renderAll();
  } else {
    showToast("Ini adalah soal terakhir.");
  }
});

finishAssessmentBtn.addEventListener("click", async () => {
  const answeredCount = getAnsweredCount();

  // Pastikan semua soal sudah dijawab
  if (answeredCount < questions.length) {
    showToast(
      `Masih ada ${questions.length - answeredCount} soal yang belum dijawab.`,
    );
    return;
  }

  const confirmed = confirm(
    "Apakah kamu yakin ingin menyelesaikan assessment? Jawaban tidak dapat diubah setelah dikirim.",
  );

  if (!confirmed) return;

  finishAssessmentBtn.disabled = true;
  finishAssessmentBtn.textContent = "Memproses...";

  try {
    const response = await fetch("/api/finish-assessment.php", {
      method: "POST",
      headers: buildJsonHeaders(),
      body: JSON.stringify({
        session_id: sessionId,
        csrf_token: csrfToken,
      }),
    });

    const data = await response.json();

    if (response.status === 419 && data?.csrf_token) {
      updateCsrfToken(data.csrf_token);
      csrfToken = data.csrf_token;
      throw new Error("Token sesi kedaluwarsa. Refresh halaman lalu coba lagi.");
    }

    if (!response.ok || !data.success) {
      throw new Error(data.message || "Gagal menyelesaikan assessment.");
    }

    showToast("Assessment berhasil diselesaikan!");

    finishAssessmentBtn.textContent = "Assessment Selesai";
  } catch (error) {
    console.error("Finish Assessment Error:", error);

    showToast(error.message);

    finishAssessmentBtn.disabled = false;
    finishAssessmentBtn.textContent = "Selesaikan Assessment";
  }
});

setInterval(() => {
  if (totalSeconds > 0) {
    totalSeconds--;
    updateTimer();
  }
}, 1000);

updateTimer();
loadQuestions();
