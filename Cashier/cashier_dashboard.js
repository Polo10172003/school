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
          const data = await postForm('update_payment_status.php', { id, status: 'declined' });
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
    const formatCurrency = (value) => {
      const amount = Number(value);
      if (Number.isNaN(amount)) {
        return '0.00';
      }
      return amount.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    };

    document.querySelectorAll('[data-plan-container]').forEach((container) => {
      const dropdown = container.querySelector('.payment-plan-select');
      const panels = new Map();
      container.querySelectorAll('[data-plan-panel]').forEach((panel) => {
        panels.set(panel.dataset.planPanel, panel);
      });
      if (!dropdown || panels.size === 0) {
        return;
      }


      

      const metas = new Map();
      container.querySelectorAll('.cashier-plan-meta').forEach((meta) => {
        metas.set(meta.dataset.plan, meta);
      });

      const view = container.closest('.cashier-view');
      const studentId = view ? view.dataset.student : null;
      const summaryWrapper = view ? view.querySelector('[data-plan-wrapper="summary"]') : null;
      const labelEl = view ? view.querySelector('[data-plan-bind="label"]') : null;
      const nextWrapper = view ? view.querySelector('[data-plan-bind="next-wrapper"]') : null;
      const nextPrefix = view ? view.querySelector('[data-plan-bind="next-prefix"]') : null;
      const nextTextEl = view ? view.querySelector('[data-plan-bind="next-text"]') : null;
      const remainingEl = view ? view.querySelector('[data-plan-bind="remaining"]') : null;
      const totalEl = view ? view.querySelector('[data-plan-bind="total"]') : null;
      const messageEl = view ? view.querySelector('[data-plan-bind="schedule-message"]') : null;
      const totalDisplay = container.querySelector('[data-plan-selected-total]');
      const labelDisplay = container.querySelector('[data-plan-selected-label]');

      const form = studentId ? document.querySelector(`.cashier-payment-entry[data-student="${studentId}"]`) : null;
      const planInput = form ? form.querySelector('[data-plan-field="plan"]') : null;
      const amountInput = form ? form.querySelector('[data-plan-field="amount"]') : null;
      const hintEl = form ? form.querySelector('[data-plan-bind="amount-hint"]') : null;
      const submitBtn = form ? form.querySelector('[data-plan-bind="submit-button"]') : null;

      

const setActivePlan = (planKey) => {
  const normalizedKey = String(planKey).toLowerCase().replace(/-/g, '_');
  panels.forEach((panel, key) => {
    const normKey = key.toLowerCase().replace(/-/g, '_');
    const isActive = normKey === normalizedKey;
    panel.classList.toggle('active', isActive);
    panel.style.display = isActive ? '' : 'none';
  });
};


// initialize
setActivePlan(dropdown.value);

// update on change
dropdown.addEventListener('change', () => {
  setActivePlan(dropdown.value);
});


// initialize
setActivePlan(dropdown.value);

// update on change
dropdown.addEventListener('change', () => {
  setActivePlan(dropdown.value);
});
    });
  };
    
    

  document.addEventListener('DOMContentLoaded', () => {
    bindPaymentModal();
    bindFinancialViewSwitchers();
    bindPlanSelectors();
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
