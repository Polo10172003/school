(function () {
  'use strict';

  const tableWrapper = document.getElementById('enrolledTableWrapper');
  const tableBody = document.getElementById('enrolledTableBody');
  const emptyState = document.getElementById('enrolledEmptyState');
  const actionsWrapper = document.getElementById('enrolledActions');
  const gradeFilter = document.getElementById('grade_filter');
  const masterCheckbox = document.getElementById('checkAll');

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
    const firstname = student.firstname || '';
    const lastname = student.lastname || '';
    const name = `${firstname} ${lastname}`.trim();
    const gradeLevel = student.year || student.grade_level || '';
    const section = student.section && student.section !== '' ? student.section : 'Not Assigned';
    const adviser = student.adviser && student.adviser !== '' ? student.adviser : 'Not Assigned';
    const academicStatusRaw = student.academic_status || '';
    const portalStatusRaw = String(student.portal_status || '').toLowerCase();
    const portalActive = portalStatusRaw === 'activated';

    let academicStatusDisplay = academicStatusRaw || 'Ongoing';
    if (gradeLevel === 'Grade 12' && academicStatusRaw === 'Passed') {
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
      actions.push(`<a href="delete_student.php?id=${id}" onclick="return confirm('Are you sure?')">Delete</a>`);
      actions.push(`<a href="update_section.php?id=${id}">Change Section</a>`);
      actions.push(`<a href="update_student_status.php?id=${id}">Update Status</a>`);
    }

    const portalClass = portalActive ? 'success' : 'pending';
    const portalLabel = portalActive ? 'Activated' : 'Not Activated';

    return `
      <tr data-student-row="${id}">
        <td><input type="checkbox" name="student_ids[]" value="${id}"></td>
        <td>${id}</td>
        <td>${escapeHtml(name)}</td>
        <td>${escapeHtml(gradeLevel)}</td>
        <td>${escapeHtml(section)}</td>
        <td>${escapeHtml(adviser)}</td>
        <td>${academicStatusDisplay}</td>
        <td class="dashboard-table-actions">
          ${actions.join(' ')}
          <span id="portal-status-${id}" class="dashboard-status-pill ${portalClass}">${portalLabel}</span>
        </td>
      </tr>
    `;
  };

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
          console.warn('[registrar] Session expired while fetching enrolled students. Reloading.');
        }
        window.location.reload();
        return;
      }

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const payload = await response.json();
      if (!payload || payload.success !== true) {
        throw new Error(payload && payload.error ? payload.error : 'Unexpected response');
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
