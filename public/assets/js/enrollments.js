/**
 * Matrículas em turmas via Ajax (relação NxN Turma <-> Aluno).
 *
 * Os formulários da página /admin/classes/{id} funcionam sem JavaScript (POST + redirect).
 * Este script intercepta o submit e faz a mesma requisição de forma ASSÍNCRONA:
 *   - registro da relação: POST   /admin/classes/{id}/enrollments          (corpo: user_id)
 *   - remoção da relação:  DELETE /admin/classes/{id}/enrollments/{user_id}
 * O servidor responde JSON (cabeçalho "Accept: application/json") e a página é atualizada
 * sem recarregar. Abra o DevTools (F12) > Network (filtro Fetch/XHR) e Console para acompanhar.
 */
(function () {
  "use strict";

  const READY_STATES = ["0 UNSENT", "1 OPENED", "2 HEADERS_RECEIVED", "3 LOADING", "4 DONE"];

  /**
   * Envia uma requisição Ajax e devolve uma Promise com { status, data }.
   * Usa XMLHttpRequest para mostrar no console cada mudança de readyState (0 a 4).
   */
  function requestJson(method, url, body) {
    return new Promise(function (resolve, reject) {
      const xhr = new XMLHttpRequest();
      console.log(`[Ajax] ${method} ${url} -> readyState ${READY_STATES[xhr.readyState]}`);

      xhr.onreadystatechange = function () {
        console.log(`[Ajax] ${method} ${url} -> readyState ${READY_STATES[xhr.readyState]}`);

        if (xhr.readyState !== XMLHttpRequest.DONE) {
          return;
        }

        if (xhr.status === 0) {
          reject(new Error("Falha de rede: não foi possível falar com o servidor."));
          return;
        }

        try {
          const data = JSON.parse(xhr.responseText);
          console.log(`[Ajax] HTTP ${xhr.status} - resposta JSON:`, data);
          resolve({ status: xhr.status, data: data });
        } catch (error) {
          reject(new Error(`Resposta inválida do servidor (HTTP ${xhr.status}).`));
        }
      };

      xhr.open(method, url, true); // true = requisição assíncrona: o navegador não trava esperando
      xhr.setRequestHeader("Accept", "application/json");
      xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
      xhr.send(body || null);
    });
  }

  const table = document.getElementById("enrolled_students");
  const enrollForm = document.getElementById("enroll_form");
  if (!table || !enrollForm) {
    return;
  }

  const classId = table.dataset.classId;
  const tbody = table.querySelector("tbody");
  const select = document.getElementById("enroll_user_id");
  const feedback = document.getElementById("ajax_feedback");

  function showFeedback(type, message) {
    const alert = document.createElement("div");
    alert.className = `alert alert-${type} alert-dismissible`;
    alert.setAttribute("role", "alert");
    alert.id = "ajax_message";

    const text = document.createElement("div");
    text.textContent = message; // textContent: o texto nunca é interpretado como HTML (evita XSS)

    const close = document.createElement("button");
    close.type = "button";
    close.className = "btn-close";
    close.setAttribute("data-bs-dismiss", "alert");
    close.setAttribute("aria-label", "Fechar");

    alert.append(text, close);
    feedback.replaceChildren(alert);
  }

  function messageFrom(data) {
    return data.message || data.error || "Não foi possível concluir a operação.";
  }

  /** Atualiza o contador "X/Y alunos" e o selo LOTADA com os dados do servidor. */
  function updateCounter(trainingClass) {
    if (!trainingClass) {
      return;
    }
    document.getElementById("students_count").textContent = trainingClass.students_count;
    document.getElementById("students_capacity").textContent = trainingClass.capacity;
    document.getElementById("class_full_badge").classList.toggle("d-none", !trainingClass.is_full);
    document.getElementById("no_students").classList.toggle("d-none", trainingClass.students_count > 0);
  }

  /** Recria as opções do select com os alunos que ainda não estão na turma. */
  function updateSelect(availableStudents) {
    const placeholder = new Option(availableStudents.length ? "Selecione um aluno..." : "Nenhum aluno disponível", "");
    const options = availableStudents.map((student) => new Option(`${student.name} (${student.email})`, student.id));
    select.replaceChildren(placeholder, ...options);
  }

  function createStudentRow(student) {
    const row = document.createElement("tr");
    row.id = `enrolled_student_${student.id}`;

    const nameCell = document.createElement("td");
    nameCell.className = "ps-3 fw-semibold";
    const link = document.createElement("a");
    link.href = `/admin/users/${student.id}`;
    link.textContent = student.name;
    nameCell.append(link);

    const emailCell = document.createElement("td");
    emailCell.className = "text-muted small";
    emailCell.textContent = student.email;

    const statusCell = document.createElement("td");
    const badge = document.createElement("span");
    const active = student.status === "active";
    badge.className = `badge ${active ? "bg-success" : "bg-danger"}`;
    badge.textContent = active ? "ATIVO" : "BLOQUEADO";
    statusCell.append(badge);

    const actionCell = document.createElement("td");
    actionCell.className = "text-end pe-3";
    const form = document.createElement("form");
    form.action = `/admin/classes/${classId}/enrollments/${student.id}`;
    form.method = "POST";
    form.className = "m-0";
    form.dataset.ajax = "unenroll";
    form.dataset.studentName = student.name;
    const method = document.createElement("input");
    method.type = "hidden";
    method.name = "_method";
    method.value = "DELETE";
    const button = document.createElement("button");
    button.type = "submit";
    button.className = "btn btn-outline-danger btn-sm";
    button.id = `btn_unenroll_${student.id}`;
    button.innerHTML = '<i class="bi bi-person-dash"></i> Remover';
    form.append(method, button);
    actionCell.append(form);

    row.append(nameCell, emailCell, statusCell, actionCell);
    return row;
  }

  function setLoading(button, loading) {
    button.disabled = loading;
    button.classList.toggle("opacity-50", loading);
  }

  /* -------- Registro da relação (POST) -------- */
  enrollForm.addEventListener("submit", async function (event) {
    event.preventDefault(); // impede o envio tradicional (que recarregaria a página)

    const button = document.getElementById("btn_enroll");
    setLoading(button, true);

    try {
      const { status, data } = await requestJson("POST", enrollForm.action, new FormData(enrollForm));

      if (status === 201 && data.success) {
        tbody.append(createStudentRow(data.student));
        showFeedback("success", data.message);
      } else {
        showFeedback("danger", messageFrom(data));
      }

      updateCounter(data.training_class);
      if (data.available_students) {
        updateSelect(data.available_students);
      }
    } catch (error) {
      showFeedback("danger", error.message);
    } finally {
      setLoading(button, false);
    }
  });

  /* -------- Remoção da relação (DELETE) — delegação: vale também para linhas criadas via Ajax -------- */
  tbody.addEventListener("submit", async function (event) {
    const form = event.target;
    if (form.dataset.ajax !== "unenroll") {
      return;
    }
    event.preventDefault();

    if (!confirm(`Remover ${form.dataset.studentName} desta turma?`)) {
      return;
    }

    const button = form.querySelector("button");
    setLoading(button, true);

    try {
      const { status, data } = await requestJson("DELETE", form.action);

      if (status === 200 && data.success) {
        form.closest("tr").remove();
        showFeedback("success", data.message);
      } else {
        showFeedback("danger", messageFrom(data));
        setLoading(button, false);
      }

      updateCounter(data.training_class);
      if (data.available_students) {
        updateSelect(data.available_students);
      }
    } catch (error) {
      showFeedback("danger", error.message);
      setLoading(button, false);
    }
  });
})();
