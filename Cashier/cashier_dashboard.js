(function () {
  'use strict';

  const bindPaymentModal = () => {
    const modal = document.getElementById('paymentModal');
    if (!modal) {
      return;
    }

    const closeModal = document.getElementById('closeModal');
    const acceptBtn = document.getElementById('acceptPaymentBtn');
    const declineBtn = document.getElementById('declinePaymentBtn');
    const screenshotSection = document.getElementById('screenshotSection');
    const modalScreenshot = document.getElementById('modalScreenshot');
    let currentType = '';
    let activeButton = null;

    document.querySelectorAll('.payment-mode').forEach((select) => {
      const containerId = select.dataset.target;
      const fieldsContainer = containerId ? document.getElementById(containerId) : null;
      if (!fieldsContainer) {
        return;
      }
      const cashField = fieldsContainer.querySelector('.cash-field');
      const gcashField = fieldsContainer.querySelector('.gcash-field');
      const orInput = fieldsContainer.querySelector('input[name="or_number"]');
      const refInput = fieldsContainer.querySelector('input[name="reference_number"]');

      const syncFields = () => {
        const mode = select.value;
        if (mode === 'Cash') {
          if (cashField) cashField.style.display = 'block';
          if (gcashField) gcashField.style.display = 'none';
          if (orInput) orInput.required = true;
          if (refInput) refInput.required = false;
        } else {
          if (cashField) cashField.style.display = 'none';
          if (gcashField) gcashField.style.display = 'block';
          if (orInput) orInput.required = false;
          if (refInput) refInput.required = true;
        }
      };

      select.addEventListener('change', syncFields);
      syncFields();
    });

    const setProcessingState = (isProcessing, action) => {
      const targetBtn = action === 'accept' ? acceptBtn : declineBtn;
      const otherBtn = action === 'accept' ? declineBtn : acceptBtn;

      if (!targetBtn || !otherBtn) {
        return;
      }

      targetBtn.disabled = isProcessing;
      otherBtn.disabled = isProcessing;
      targetBtn.dataset.originalText = targetBtn.dataset.originalText || targetBtn.textContent;
      otherBtn.dataset.originalText = otherBtn.dataset.originalText || otherBtn.textContent;

      if (isProcessing) {
        targetBtn.textContent = 'Processing…';
        targetBtn.classList.add('btn-loading');
        modal.classList.add('modal-processing');
      } else {
        targetBtn.textContent = targetBtn.dataset.originalText;
        otherBtn.textContent = otherBtn.dataset.originalText;
        targetBtn.classList.remove('btn-loading');
        modal.classList.remove('modal-processing');
        targetBtn.disabled = false;
        otherBtn.disabled = false;
      }
    };

    document.querySelectorAll('.view-payment-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        activeButton = btn;
        document.getElementById('modalStudent').textContent = btn.dataset.student || '';
        document.getElementById('modalType').textContent = btn.dataset.type || '';
        const rawAmount = parseFloat(btn.dataset.amount) || 0;
document.getElementById('modalAmount').textContent = rawAmount.toLocaleString('en-PH', {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2
});
        document.getElementById('modalStatus').textContent = btn.dataset.status || '';
        document.getElementById('modalPaymentId').value = btn.dataset.id || '';
        const studentIdInput = document.getElementById('modalStudentId');
        if (studentIdInput) {
          studentIdInput.value = btn.dataset.studentId || '';
        }

        const shot = (btn.dataset.screenshot || '').trim();
        if (shot) {
          modalScreenshot.src =  shot;
          screenshotSection.style.display = 'block';
        } else {
          screenshotSection.style.display = 'none';
        }

        currentType = (btn.dataset.type || '').toLowerCase();
        if (currentType === 'cash') {
          document.getElementById('modalLabel').textContent = 'Official Receipt #:';
          document.getElementById('modalRefOr').textContent = btn.dataset.or ||btn.dataset.ref || 'N/A';

          if (acceptBtn) acceptBtn.style.display = 'none';
          if (declineBtn) declineBtn.style.display = 'none';
        } else {
          document.getElementById('modalLabel').textContent = 'Reference #:';
          document.getElementById('modalRefOr').textContent = btn.dataset.reference || btn.dataset.ref ||'N/A';

          if (acceptBtn) acceptBtn.style.display = 'inline-block';
          if (declineBtn) declineBtn.style.display = 'inline-block';
        }

        modal.style.display = 'flex';
      });
    });

    if (closeModal) {
      closeModal.addEventListener('click', () => {
        modal.style.display = 'none';
      });
    }

    window.addEventListener('click', (event) => {
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    });

    const postForm = async (url, obj) => {
      const body = new URLSearchParams(obj).toString();
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body,
      });
      const text = await res.text();
      try {
        return JSON.parse(text);
      } catch (error) {
        alert('Server returned a non-JSON response:\n\n' + text);
        return { success: false, error: 'Non-JSON response' };
      }
    };

    if (acceptBtn) {
      acceptBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        const id = document.getElementById('modalPaymentId').value;
        setProcessingState(true, 'accept');

        const payload = { id, status: 'paid' };
        const studentIdInput = document.getElementById('modalStudentId');
        if (studentIdInput && studentIdInput.value) {
          payload.student_id = studentIdInput.value;
        }
        if (currentType === 'cash') {
          payload.or_number = document.getElementById('modalRefOr').textContent;
        }

        try {
          const data = await postForm('update_payment_status.php', payload);
          if (data && data.success) {
            const statusCell = document.getElementById('status-' + id);
            if (statusCell) {
              statusCell.innerHTML = "<span style='color: green;'>Paid" + (currentType === 'cash' ? ' (Cash)' : '') + '</span>';
            }
            if (activeButton) {
              activeButton.dataset.status = 'Paid';
              if (payload.or_number) {
                activeButton.dataset.or = payload.or_number;
              }
            }
            document.getElementById('modalStatus').textContent = 'Paid';
            alert('✅ Student’s payment has been accepted.');
            modal.style.display = 'none';
          } else if (data) {
            alert('Error: ' + (data.error || 'Unknown error'));
          }
        } catch (error) {
          console.error(error);
          alert('Error communicating with server. Please try again.');
        } finally {
          setProcessingState(false, 'accept');
        }
      });
    }

    if (declineBtn) {
      declineBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        const id = document.getElementById('modalPaymentId').value;
        setProcessingState(true, 'decline');

        try {
          const declinePayload = { id, status: 'declined' };
          const studentIdInput = document.getElementById('modalStudentId');
          if (studentIdInput && studentIdInput.value) {
            declinePayload.student_id = studentIdInput.value;
          }
          const data = await postForm('update_payment_status.php', declinePayload);
          if (data && data.success) {
            const statusCell = document.getElementById('status-' + id);
            if (statusCell) {
              statusCell.innerHTML = "<span style='color: red;'>Declined</span>";
            }
            if (activeButton) {
              activeButton.dataset.status = 'Declined';
            }
            document.getElementById('modalStatus').textContent = 'Declined';
            alert('❌ Student’s payment has been declined.');
            modal.style.display = 'none';
          } else if (data) {
            alert('Error: ' + (data.error || 'Unknown error'));
          }
        } catch (error) {
          console.error(error);
          alert('Error communicating with server. Please try again.');
        } finally {
          setProcessingState(false, 'decline');
        }
      });
    }
  };

  const bindFinancialViewSwitchers = () => {
    const activateView = (studentId, viewKey) => {
      document
        .querySelectorAll(`.cashier-view[data-student="${studentId}"]`)
        .forEach((view) => {
          view.style.display = view.dataset.view === viewKey ? '' : 'none';
        });
    };

    document.querySelectorAll('.cashier-view-selector').forEach((selector) => {
      const studentId = selector.dataset.student;
      if (!studentId) {
        return;
      }

      selector.addEventListener('change', () => {
        activateView(studentId, selector.value);
      });

      activateView(studentId, selector.value);
    });
  };

    const bindPlanSelectors = () => {
    document.querySelectorAll('[data-plan-container]').forEach((container) => {
      const dropdown = container.querySelector('.payment-plan-select');
      if (!dropdown) {
        return;
      }

      const panels = new Map();
      const wrapper = dropdown.closest('.cashier-select');
      container.querySelectorAll('[data-plan-panel]').forEach((panel) => {
        panels.set(panel.dataset.planPanel, panel);
      });

      const labelDisplay = container.querySelector('[data-plan-selected-label]');
      const view = container.closest('.cashier-view');
      const studentId = view ? view.dataset.student : null;
      const formWrapper = studentId ? document.querySelector(`.cashier-payment-entry[data-student="${studentId}"]`) : null;
      const form = formWrapper ? formWrapper.querySelector('form') : null;
      const planInput = form ? form.querySelector('input[name="payment_plan"]') : null;

      const normaliseKey = (value) => String(value || '').toLowerCase().replace(/-/g, '_');

      const setActivePlan = (planKey) => {
        const normalised = normaliseKey(planKey);
        let hasActive = false;

        panels.forEach((panel, key) => {
          const keyNormalised = normaliseKey(key);
          const isActive = normalised !== '' && keyNormalised === normalised;
          panel.classList.toggle('active', isActive);
          panel.style.display = isActive ? '' : 'none';
          if (isActive) {
            hasActive = true;
          }
        });

        if (!hasActive) {
          panels.forEach((panel) => {
            panel.classList.remove('active');
            panel.style.display = 'none';
          });
        }

        if (planInput) {
          planInput.value = planKey || '';
        }
      };

      const updatePlaceholderState = () => {
        if (!wrapper) {
          return;
        }
        const hasValue = (dropdown.value || '').trim() !== '';
        wrapper.classList.toggle('cashier-select--placeholder', !hasValue);
      };

      const applySelection = (planKey) => {
        setActivePlan(planKey);

        if (labelDisplay) {
          const selectedOption = planKey
            ? dropdown.querySelector(`option[value="${planKey}"]`)
            : null;
          labelDisplay.textContent = selectedOption ? selectedOption.textContent : '';
        }

        updatePlaceholderState();
      };

      applySelection(dropdown.value || '');

      dropdown.addEventListener('change', () => {
        applySelection(dropdown.value);
      });
    });
  };

  const triggerReceiptPrint = () => {
    if (!window.cashierReceiptData) {
      return;
    }

    const templateEl = document.getElementById('cashier-receipt-template');
    if (!templateEl) {
      return;
    }

    const data = window.cashierReceiptData || {};
    const cashierName = window.cashierReceiptCashier || 'Cashier';
    const statusRaw = (data.payment_status || '').toString();
    const statusFormatted =
      statusRaw !== ''
        ? statusRaw.charAt(0).toUpperCase() + statusRaw.slice(1)
        : 'Paid';

    const replacements = {
      or_number: data.or_number || 'N/A',
      payment_date: data.payment_date || '',
      generated_at: data.generated_at || '',
      student_name: data.student_name || 'Student',
      student_number: data.student_number || 'N/A',
      grade_level: data.grade_level || 'N/A',
      school_year: data.school_year || 'N/A',
      payment_type: data.payment_type || 'Cash',
      reference_number:
        data.reference_number ||
        (data.payment_type && data.payment_type.toLowerCase() === 'cash' ? 'N/A' : ''),
      amount_formatted: data.amount_formatted || '0.00',
      payment_status: statusFormatted,
      cashier_name: cashierName || 'Cashier',
    };

    let html = templateEl.innerHTML;
    Object.keys(replacements).forEach((key) => {
      const value = (replacements[key] ?? '').toString() || 'N/A';
      const pattern = new RegExp(`{{${key}}}`, 'g');
      html = html.replace(pattern, value);
    });

    const printWindow = window.open('', '_blank', 'width=820,height=960');
    if (!printWindow) {
      console.warn('Unable to open print dialog for receipt.');
      return;
    }

    printWindow.document.open();
    printWindow.document.write(`<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Official Receipt</title>
  </head>
  <body>
    ${html}
  </body>
</html>`);
    printWindow.document.close();
    printWindow.focus();

    setTimeout(() => {
      try {
        printWindow.print();
      } catch (error) {
        console.error('Failed to trigger print dialog.', error);
      }
    }, 350);

    window.cashierReceiptData = null;
  };

  const bootReceiptIfNeeded = () => {
    if (!window.cashierReceiptData) {
      return;
    }
    triggerReceiptPrint();
  };

  document.addEventListener('DOMContentLoaded', () => {
    bindPaymentModal();
    bindFinancialViewSwitchers();
    bindPlanSelectors();
    bootReceiptIfNeeded();
  });

  window.calculatePayment = function () {
    const tuitionInput = document.getElementById('tuition_fee');
    const miscInput = document.getElementById('miscellaneous_fee');
    const entranceInput = document.getElementById('entrance_fee');
    const scheduleSelect = document.getElementById('payment_schedule');
    const display = document.getElementById('calculated_payment');

    if (!tuitionInput || !miscInput || !entranceInput || !scheduleSelect || !display) {
      return;
    }

    const tuition = parseFloat(tuitionInput.value || '0');
    const misc = parseFloat(miscInput.value || '0');
    const entrance = parseFloat(entranceInput.value || '0');
    const total = tuition + misc + entrance;

    let result = total;
    switch (scheduleSelect.value) {
      case 'cash':
        result = entrance * 0.9 + misc + tuition;
        break;
      case 'semi_annual':
        result = entrance + misc + tuition / 2;
        break;
      case 'quarterly':
        result = entrance + misc / 4 + tuition / 4;
        break;
      case 'monthly':
        result = entrance + misc / 4 + tuition / 10;
        break;
      default:
        result = total;
    }
  
    display.value = result.toFixed(2);
  };
})();
