const loginForm = document.getElementById("loginForm");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const rememberInput = document.getElementById("remember");
const message = document.getElementById("message");
const togglePassword = document.getElementById("togglePassword");
const forgotPassword = document.getElementById("forgotPassword");
const registerLink = document.getElementById("registerLink");

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

if (loginForm && emailInput && passwordInput && message) {
  const savedUsername = localStorage.getItem("jagateduRememberedUsername");

  if (savedUsername) {
    emailInput.value = savedUsername;

    if (rememberInput) {
      rememberInput.checked = true;
    }
  }

  loginForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const email = emailInput.value.trim();
    const password = passwordInput.value;

    message.className = "message";
    message.textContent = "";

    if (!email || !password) {
      showMessage("Email dan password wajib diisi.", "error");
      return;
    }

    if (!isValidEmail(email)) {
      showMessage("Format email belum benar.", "error");
      emailInput.focus();
      return;
    }

    if (rememberInput && rememberInput.checked) {
      localStorage.setItem("jagateduRememberedUsername", email);
    } else {
      localStorage.removeItem("jagateduRememberedUsername");
    }

    try {
      const response = await fetch("controllers/AuthController.php", {
        method: "POST",
        headers: buildJsonHeaders(),
        body: JSON.stringify({
          email: email,
          password: password,
          csrf_token: csrfToken,
        }),
      });

      const result = await response.json();

      if (response.status === 419 && result?.csrf_token) {
        updateCsrfToken(result.csrf_token);
        csrfToken = result.csrf_token;
        showMessage("Token sesi kedaluwarsa, sedang memperbarui form. Silakan coba lagi.", "error");
        return;
      }

      if (!response.ok || !result.success) {
        showMessage(result.message || "Login gagal.", "error");
        return;
      }

      showMessage("Login berhasil! Mengarahkan...", "success");

      window.setTimeout(function () {
        window.location.href = result.redirect;
      }, 700);
    } catch (error) {
      console.error("Login Error:", error);

      showMessage("Terjadi kesalahan: " + error.message, "error");
    }
  });
}

if (togglePassword && passwordInput) {
  togglePassword.addEventListener("click", function () {
    const isHidden = passwordInput.type === "password";

    passwordInput.type = isHidden ? "text" : "password";
    togglePassword.textContent = isHidden ? "Sembunyikan" : "Lihat";
    togglePassword.setAttribute(
      "aria-label",
      isHidden ? "Sembunyikan password" : "Tampilkan password",
    );
  });
}

if (forgotPassword && message) {
  forgotPassword.addEventListener("click", function (event) {
    event.preventDefault();
    showMessage(
      "Silakan hubungi admin untuk mengatur ulang password.",
      "error",
    );
  });
}

if (registerLink && message) {
  registerLink.addEventListener("click", function (event) {
    event.preventDefault();
    showMessage(
      "Pendaftaran akun belum tersedia. Silakan hubungi admin.",
      "error",
    );
  });
}

function isValidEmail(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function showMessage(text, type) {
  if (!message) {
    return;
  }

  message.textContent = text;
  message.className = "message " + type;
  message.setAttribute("role", "alert");
}
