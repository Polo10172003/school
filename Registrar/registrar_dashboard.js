(function () {
  'use strict';

  const tableWrapper = document.getElementById('enrolledTableWrapper');
  const tableBody = document.getElementById('enrolledTableBody');
  const emptyState = document.getElementById('enrolledEmptyState');
  const actionsWrapper = document.getElementById('enrolledActions');
  const gradeFilter = document.getElementById('grade_filter');
  const masterCheckbox = document.getElementById('checkAll');
  const requirementsModal = document.getElementById('requirementsModal');
  const requirementsModalBody = document.getElementById('requirementsModalBody');
  const requirementsModalTitle = document.getElementById('requirementsModalTitle');
  const requirementsModalSubtitle = document.getElementById('requirementsModalSubtitle');
  const requirementsModalClose = document.getElementById('requirementsModalClose');
  const requirementsModalCancel = document.getElementById('requirementsModalCancel');
  const requirementsModalSave = document.getElementById('requirementsModalSave');
  let requirementsModalContext = null;

  if (typeof console !== 'undefined' && typeof console.debug === 'function') {
    console.debug('[registrar] dashboard script initialised');
  }

  const escapeHtml = (value) =>
    String(value ?? '').replace(/[&<>"']/g, (char) => {
      switch (char) {
        case '&':
          return '&amp;';
        case '<':
          return '&lt;';
        case '>':
          return '&gt;';
        case '"':
          return '&quot;';
        case '\'':
          return '&#039;';
        default:
          return char;
      }
    });

  const buildRowHtml = (student) => {
    const id = Number(student.id || 0);
    const firstname = (student.firstname || '').trim();
    const lastname = (student.lastname || '').trim();
    const rawName = `${firstname} ${lastname}`.trim();
    const name = rawName !== '' ? rawName : 'Student';
    const rawGrade = (student.year || student.grade_level || '').trim();
    const displayGrade = rawGrade !== '' ? rawGrade : 'Not Set';
    const section = student.section && student.section !== '' ? student.section : 'Not Assigned';
    const adviser = student.adviser && student.adviser !== '' ? student.adviser : 'Not Assigned';
    const studentNumberSource =
      (student.student_number_formatted || student.student_number || '').trim();
    const studentNumber =
      studentNumberSource !== '' ? studentNumberSource.toUpperCase() : 'Pending';
    const academicStatusRaw = student.academic_status || '';
    const portalStatusRaw = String(student.portal_status || '').toLowerCase();
    const portalActive = portalStatusRaw === 'activated';
    const requirementsSummary = student.requirements_summary || {};
    const requirementsStatusLabel = requirementsSummary.status_label || 'Not Checked';
    const requirementsStatusClass = requirementsSummary.status_class || 'pending';
    const requirementsScope = student.requirements_scope || requirementsSummary.scope || '';
    const requirementsSpanId = `requirements-status-${id}`;

    let academicStatusDisplay = academicStatusRaw || 'Ongoing';
    if (rawGrade === 'Grade 12' && academicStatusRaw === 'Passed') {
      academicStatusDisplay = '<span class="dashboard-status-pill success">Graduated</span>';
    } else if (academicStatusRaw === 'Graduated') {
      academicStatusDisplay = '<span class="dashboard-status-pill success">Graduated</span>';
    } else {
      academicStatusDisplay = escapeHtml(academicStatusDisplay);
    }

    const actions = [];
    if (academicStatusRaw === 'Graduated') {
      actions.push(`<a href="edit_student.php?id=${id}">Edit</a>`);
      actions.push(`<a href="archive_student.php?id=${id}" onclick="return confirm('Archive this student?')">Archive</a>`);
      actions.push(`<a href="update_section.php?id=${id}">Change Section</a>`);
    } else {
      actions.push(`<a href="edit_student.php?id=${id}">Edit</a>`);
      actions.push(`<a href="archive_student.php?id=${id}" onclick="return confirm('Archive this student?')">Archive</a>`);
      actions.push(`<a href="update_section.php?id=${id}">Change Section</a>`);
      actions.push(`<a href="update_student_status.php?id=${id}">Update Status</a>`);
    }
    actions.push(`<a href="registration_assessment.php?student_id=${id}" target="_blank">View RAF</a>`);

    const portalClass = portalActive ? 'success' : 'pending';
    const portalLabel = portalActive ? 'Activated' : 'Pending';

    const requirementsButton = `
      <button
        type="button"
        class="dashboard-btn secondary requirements-btn"
        data-student-id="${id}"
        data-student-name="${escapeHtml(name)}"
        data-student-number="${escapeHtml(studentNumber)}"
        data-grade-level="${escapeHtml(rawGrade)}"
        data-scope="${escapeHtml(requirementsScope || '')}"
        data-status-target="${requirementsSpanId}"
      >Files</button>`;

    return `
      <tr data-student-row="${id}">
        <td><input type="checkbox" name="student_ids[]" value="${id}"></td>
        <td>${escapeHtml(studentNumber)}</td>
        <td>${escapeHtml(name)}</td>
        <td>${escapeHtml(displayGrade)}</td>
        <td>${escapeHtml(section)}</td>
        <td>${escapeHtml(adviser)}</td>
        <td>${academicStatusDisplay}</td>
        <td>
          <div class="requirements-cell">
            <span id="${requirementsSpanId}" class="dashboard-status-pill ${requirementsStatusClass}">${escapeHtml(requirementsStatusLabel)}</span>
            ${requirementsButton}
          </div>
        </td>
        <td class="dashboard-table-actions">
          ${actions.join(' ')}
          <span id="portal-status-${id}" class="dashboard-status-pill ${portalClass}">${portalLabel}</span>
        </td>
      </tr>
    `;
  };

  const setRequirementsModalContent = (html) => {
    if (requirementsModalBody) {
      requirementsModalBody.innerHTML = html;
    }
  };

  const setSaveButtonBusy = (isBusy) => {
    if (!requirementsModalSave) {
      return;
    }
    if (!requirementsModalSave.dataset.originalText) {
      requirementsModalSave.dataset.originalText = requirementsModalSave.textContent || 'Save';
    }
    if (isBusy) {
      requirementsModalSave.disabled = true;
      requirementsModalSave.classList.add('is-loading');
      requirementsModalSave.textContent = 'Saving...';
    } else {
      requirementsModalSave.disabled = false;
      requirementsModalSave.classList.remove('is-loading');
      requirementsModalSave.textContent = requirementsModalSave.dataset.originalText;
    }
  };

  const openRequirementsModal = () => {
    if (!requirementsModal) {
      return;
    }
    requirementsModal.classList.add('is-visible');
    requirementsModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
  };

  const closeRequirementsModal = (options = {}) => {
    if (!requirementsModal) {
      return;
    }
    requirementsModal.classList.remove('is-visible');
    requirementsModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
    if (options.resetContent !== false) {
      setRequirementsModalContent('<p class="text-muted">Loading requirements...</p>');
    }
    setSaveButtonBusy(false);

    const trigger = requirementsModalContext && requirementsModalContext.trigger;
    requirementsModalContext = null;

    if (options.restoreFocus !== false && trigger && typeof trigger.focus === 'function') {
      trigger.focus();
    }
  };

  const applyRequirementSummaryToRow = (targetId, summary) => {
    if (!targetId || !summary) {
      return;
    }
    const span = document.getElementById(targetId);
    if (!span) {
      return;
    }
    ['success', 'pending', 'warning'].forEach((cls) => span.classList.remove(cls));
    span.classList.add(summary.status_class || 'pending');
    span.textContent = summary.status_label || 'Not Checked';
  };

  const renderRequirementsForm = (data) => {
    if (!requirementsModalBody) {
      return;
    }
    const fields = Array.isArray(data.fields) ? data.fields : [];
    const summary = data.summary || {};
    const scopeLabel = data.scope_label || '';

    if (fields.length === 0) {
      setRequirementsModalContent('<p class="text-muted">No requirements configured for this student.</p>');
      return;
    }

    const fieldHtml = fields.map((field) => {
      const key = field.key || '';
      const label = field.label || key;
      const required = !!field.required;
      const locked = !!field.locked;
      const checked = field.value ? 'checked' : '';
      const lockAttributes = locked ? 'disabled data-locked="true"' : '';
      const badge = required
        ? '<span class="requirement-tag requirement-tag--required">Required</span>'
        : '<span class="requirement-tag requirement-tag--optional">Optional</span>';
      return `
        <label class="requirement-toggle">
          <input type="checkbox" data-requirement-key="${escapeHtml(key)}" ${checked} ${lockAttributes}>
          <span>
            ${escapeHtml(label)}
            ${badge}
            ${locked ? '<span class="requirement-tag requirement-tag--locked">Locked</span>' : ''}
          </span>
        </label>
      `;
    }).join('');

    let html = '';
    if (scopeLabel) {
      html += `<p class="requirements-scope">Requirement scope: <strong>${escapeHtml(scopeLabel)}</strong></p>`;
    }
    html += `<div class="requirements-grid">${fieldHtml}</div>`;

    if (Array.isArray(summary.missing_labels) && summary.missing_labels.length > 0) {
      html += `<p class="requirements-summary requirements-summary--pending">Missing: ${escapeHtml(summary.missing_labels.join(', '))}</p>`;
    } else if (summary.complete) {
      html += '<p class="requirements-summary requirements-summary--complete">All required documents are marked as received.</p>';
    } else {
      html += '<p class="requirements-summary requirements-summary--pending">Requirements have not been checked yet.</p>';
    }

    if (data.updated_at) {
      html += `<p class="requirements-meta text-muted">Last updated: ${escapeHtml(String(data.updated_at))}</p>`;
    }

    setRequirementsModalContent(html);
  };

  const loadRequirementsData = async (context) => {
    if (!context || !context.studentId) {
      return;
    }

    setRequirementsModalContent('<p class="text-muted">Loading requirements...</p>');
    if (requirementsModalSave) {
      requirementsModalSave.disabled = true;
    }

    try {
      const params = new URLSearchParams();
      params.set('student_id', String(context.studentId));

      const response = await fetch(`student_requirements.php?${params.toString()}`, {
        method: 'GET',
        credentials: 'include',
        cache: 'no-store',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const payload = await response.json();
      if (!payload || payload.success !== true) {
        throw new Error((payload && payload.error) || 'Unexpected response');
      }

      const requirementsData = payload.requirements || {};
      const studentMeta = payload.student || {};

      requirementsModalContext = {
        ...context,
        studentName: studentMeta.full_name || context.studentName || 'Student',
        studentNumber: studentMeta.student_number
          ? String(studentMeta.student_number).toUpperCase()
          : (context.studentNumber || ''),
        scope: requirementsData.scope || context.scope || '',
        gradeLevel: studentMeta.grade_level || context.gradeLevel || '',
        fields: requirementsData.fields || [],
        values: requirementsData.values || {},
        summary: requirementsData.summary || {},
        trigger: context.trigger,
      };

      if (requirementsModalSubtitle) {
        const subtitleParts = [];
        const fullName = studentMeta.full_name || context.studentName || 'Student';
        subtitleParts.push(fullName);

        if (studentMeta.student_number) {
          subtitleParts.push(String(studentMeta.student_number).toUpperCase());
        } else if (context.studentNumber) {
          subtitleParts.push(context.studentNumber);
        }

        const gradeLabel = studentMeta.grade_level || context.gradeLevel;
        if (gradeLabel) {
          subtitleParts.push(gradeLabel);
        }

        requirementsModalSubtitle.textContent = subtitleParts.join(' - ');
      }

      renderRequirementsForm(requirementsData);

      if (requirementsModalSave) {
        requirementsModalSave.disabled = false;
      }
    } catch (error) {
      console.error('[registrar] Failed to load requirements', error);
      setRequirementsModalContent('<p class="text-danger">Unable to load requirements. Please try again.</p>');
      if (requirementsModalSave) {
        requirementsModalSave.disabled = true;
      }
    }
  };

  if (requirementsModalClose) {
    requirementsModalClose.addEventListener('click', () => closeRequirementsModal());
  }

  if (requirementsModalCancel) {
    requirementsModalCancel.addEventListener('click', () => closeRequirementsModal());
  }

  if (requirementsModal) {
    requirementsModal.addEventListener('click', (event) => {
      if (event.target === requirementsModal) {
        closeRequirementsModal();
      }
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && requirementsModal && requirementsModal.classList.contains('is-visible')) {
      closeRequirementsModal();
    }
  });

  document.addEventListener('click', (event) => {
    const button = event.target.closest('.requirements-btn');
    if (!button) {
      return;
    }
    event.preventDefault();

    const studentId = Number(button.dataset.studentId || 0);
    if (!studentId) {
      alert('Unable to load student details for this record.');
      return;
    }

    const context = {
      studentId,
      trigger: button,
      studentName: button.dataset.studentName || 'Student',
      studentNumber: button.dataset.studentNumber || '',
      gradeLevel: button.dataset.gradeLevel || '',
      scope: button.dataset.scope || '',
      statusTarget: button.dataset.statusTarget || '',
    };

    if (requirementsModalTitle) {
      requirementsModalTitle.textContent = 'Student Files Tracking';
    }
    if (requirementsModalSubtitle) {
      const subtitleParts = [context.studentName];
      if (context.studentNumber) {
        subtitleParts.push(context.studentNumber);
      }
      if (context.gradeLevel) {
        subtitleParts.push(context.gradeLevel);
      }
      requirementsModalSubtitle.textContent = subtitleParts.join(' - ');
    }

    requirementsModalContext = context;
    openRequirementsModal();
    loadRequirementsData(context);
  });

  if (requirementsModalSave) {
    requirementsModalSave.addEventListener('click', async () => {
      if (!requirementsModalContext) {
        return;
      }

      const inputs = requirementsModalBody
        ? Array.from(requirementsModalBody.querySelectorAll('input[data-requirement-key]'))
        : [];

      if (inputs.length === 0) {
        closeRequirementsModal();
        return;
      }

      const values = {};
      inputs.forEach((input) => {
        if (!input.dataset || !input.dataset.requirementKey) {
          return;
        }
        values[input.dataset.requirementKey] = input.checked;
      });

      setSaveButtonBusy(true);

      try {
        const payload = {
          student_id: requirementsModalContext.studentId,
          grade_level: requirementsModalContext.gradeLevel || '',
          scope: requirementsModalContext.scope || '',
          values,
        };

        const response = await fetch('student_requirements.php', {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(payload),
        });

        const json = await response.json();
        if (!response.ok || !json || json.success !== true) {
          throw new Error((json && json.error) || `HTTP ${response.status}`);
        }

        const requirementsData = json.requirements || {};
        const summary = requirementsData.summary || {};
        const statusTarget = requirementsModalContext.statusTarget;
        const trigger = requirementsModalContext.trigger;

        applyRequirementSummaryToRow(statusTarget, summary);

        if (trigger && requirementsData.scope) {
          trigger.dataset.scope = requirementsData.scope;
        }

        closeRequirementsModal();
        refreshEnrolledStudents({ silent: true });
        alert('Requirements updated successfully.');
      } catch (error) {
        console.error('[registrar] Failed to save requirements', error);
        alert('Unable to save requirements. Please try again.');
      } finally {
        setSaveButtonBusy(false);
      }
    });
  }

  const toggleState = (hasStudents) => {
    if (tableWrapper) {
      tableWrapper.style.display = hasStudents ? '' : 'none';
    }
    if (actionsWrapper) {
      actionsWrapper.style.display = hasStudents ? '' : 'none';
    }
    if (emptyState) {
      emptyState.style.display = hasStudents ? 'none' : '';
    }
    if (masterCheckbox) {
      masterCheckbox.checked = false;
    }
    if (typeof clearSelections === 'function') {
      clearSelections();
    }
  };

  async function refreshEnrolledStudents(options = {}) {
    const { silent = false } = options;
    if (!tableBody) {
      return;
    }

    const params = new URLSearchParams();
    if (gradeFilter && gradeFilter.value) {
      params.set('grade_filter', gradeFilter.value);
    }
    const query = params.toString();
    const url = `fetch_enrolled_students.php${query ? `?${query}` : ''}`;

    try {
      const response = await fetch(url, {
        method: 'GET',
        cache: 'no-store',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (response.status === 401) {
        if (!silent) {
          console.warn('[registrar] Session expired while fetching enrolled students. Redirecting to login.');
        }
        if (typeof window.__ESR_SKIP_AUTO_LOGOUT__ !== 'boolean') {
          window.__ESR_SKIP_AUTO_LOGOUT__ = true;
        } else {
          window.__ESR_SKIP_AUTO_LOGOUT__ = true;
        }
        window.location.href = 'registrar_login.php?session=expired';
        return;
      }

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const payload = await response.json();
      if (!payload || payload.success !== true) {
        throw new Error(payload && payload.error ? payload.error : 'Unexpected response');
      }

      if (payload.requirements_error && typeof console !== 'undefined') {
        console.warn('[registrar] requirements: ' + payload.requirements_error);
      }

      const students = Array.isArray(payload.students) ? payload.students : [];
      const rows = students.map(buildRowHtml).join('');
      tableBody.innerHTML = rows;
      toggleState(students.length > 0);
    } catch (error) {
      if (!silent) {
        console.error('[registrar] Unable to refresh enrolled students.', error);
      }
    }
  }

  const initRealtime = () => {
    if (typeof Pusher === 'undefined') {
      return null;
    }
    const cfg = window.PUSHER_CONFIG || {};
    const key = cfg.key || '';
    const cluster = cfg.cluster || '';
    if (!key || !cluster) {
      return null;
    }

    try {
      const client = new Pusher(key, {
        cluster,
        forceTLS: cfg.forceTLS !== undefined ? !!cfg.forceTLS : true,
      });

      const channelName = cfg.channel || 'registrar-enrollments';
      const eventName = cfg.event || 'student-enrolled';
      const channel = client.subscribe(channelName);

      channel.bind(eventName, (payload) => {
        const silent = document.hidden === true;
        refreshEnrolledStudents({ silent });
        if (!silent && payload && payload.firstname) {
          console.info(`[registrar] New enrollment: ${payload.firstname} ${payload.lastname || ''}`.trim());
        }
      });

      if (client.connection && typeof client.connection.bind === 'function') {
        client.connection.bind('state_change', (states) => {
          console.debug('[registrar] Realtime state change:', states);
        });
        client.connection.bind('error', (error) => {
          console.error('[registrar] Realtime connection error:', error);
        });
      }

      return { client, channel };
    } catch (error) {
      console.error('[registrar] Failed to initialise realtime updates.', error);
      return null;
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    initRealtime();
    refreshEnrolledStudents({ silent: true });
  });

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      refreshEnrolledStudents({ silent: true });
    }
  });

  window.refreshEnrolledStudents = refreshEnrolledStudents;
})();
