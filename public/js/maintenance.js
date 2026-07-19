(() => {
    const form = document.querySelector('[data-maintenance-form]');
    if (!form) return;
    const field = (name) => form.querySelector(`[data-field="${name}"]`);
    const storeAction = form.action;
    const title = form.querySelector('[data-maintenance-title]');
    const submit = form.querySelector('[data-maintenance-submit]');
    const names = ['vehicle_id','garage_id','name','description','maintenance_type','estimated_cost','next_due_date','reminder_days','interval_days','next_due_odometer_km','reminder_odometer_km','interval_odometer_km','next_due_engine_hours','reminder_engine_hours','interval_engine_hours'];
    const clearValidationErrors = () => {
        form.querySelectorAll('.is-invalid').forEach((element) => element.classList.remove('is-invalid'));
        form.querySelectorAll('[data-field-error]').forEach((element) => element.remove());
        form.removeAttribute('data-maintenance-validation-errors');
    };

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-maintenance-create]')) {
            clearValidationErrors();
            form.reset(); form.action = storeAction; field('method').value = 'POST';
            ['reminder_days','reminder_odometer_km','reminder_engine_hours'].forEach((name) => field(name).value = 0);
            title.textContent = title.dataset.createLabel; submit.textContent = submit.dataset.createLabel;
            return;
        }
        const edit = event.target.closest('[data-maintenance-edit]');
        if (edit) {
            clearValidationErrors();
            const plan = JSON.parse(edit.dataset.plan); form.reset(); form.action = edit.dataset.action; field('method').value = 'PUT';
            names.forEach((name) => { if (field(name)) field(name).value = plan[name] ?? ''; });
            field('vehicle_id').dispatchEvent(new Event('change', { bubbles: true }));
            field('garage_id').dispatchEvent(new Event('searchable-select:refresh'));
            field('is_recurring').checked = Boolean(plan.is_recurring);
            if (plan.next_due_date) field('next_due_date').value = String(plan.next_due_date).slice(0, 10);
            title.textContent = plan.name; submit.textContent = submit.dataset.saveLabel;
            return;
        }
        const complete = event.target.closest('[data-maintenance-complete]');
        if (complete) {
            const completeForm = document.querySelector('[data-complete-form]');
            completeForm.action = complete.dataset.action;
            completeForm.querySelector('[data-complete-plan-id]').value = complete.dataset.planId;
            completeForm.querySelector('[data-complete-plan-name]').value = complete.dataset.planName;
            completeForm.querySelector('[data-complete-title]').textContent = complete.dataset.planName;
        }
    });

    const toast = document.querySelector('[data-app-toast]');
    toast?.querySelector('[data-app-toast-close]')?.addEventListener('click', () => toast.classList.add('is-hiding'));
    if (toast) setTimeout(() => toast.classList.add('is-hiding'), 5200);
    if (form.hasAttribute('data-maintenance-validation-errors')) {
        if (form.dataset.oldVehicleId) {
            field('vehicle_id').value = form.dataset.oldVehicleId;
            field('vehicle_id').dispatchEvent(new Event('change', { bubbles: true }));
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('maintenanceModal')).show();
    }
    const completionForm = document.querySelector('[data-complete-form]');
    if (completionForm?.hasAttribute('data-completion-validation-errors')) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('completeMaintenanceModal')).show();
    }
})();
