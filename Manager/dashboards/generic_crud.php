<!-- Modal for Add/Edit -->
<div id="crud-modal-backdrop" class="hidden fixed inset-0 bg-slate-900/60 flex items-center justify-center px-4 z-50">
    <div id="crud-modal" class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden transform transition-all" style="opacity: 0; transform: scale(0.95);">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 id="modal-title" class="text-xl font-semibold text-slate-800"></h3>
            <button id="close-modal-btn" class="p-2 rounded-full hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="modal-body" class="px-6 py-6 max-h-[70vh] overflow-y-auto">
            <form id="crud-form"></form>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 text-right">
             <button id="modal-submit-btn" class="bg-sky-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-sky-700">
                حفظ
            </button>
        </div>
    </div>
</div>

<div id="generic-crud-container" class="bg-white rounded-2xl shadow p-8 border border-slate-100">
    <!-- This will be populated by JavaScript -->
</div>

<script>
if (typeof initGenericCrud !== 'function') {
    window.initGenericCrud = function(tableName) {
        const container = document.getElementById('generic-crud-container');
        if (!container) return;

        let schema = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const modalBackdrop = document.getElementById('crud-modal-backdrop');
        const modal = document.getElementById('crud-modal');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const modalSubmitBtn = document.getElementById('modal-submit-btn');
        const crudForm = document.getElementById('crud-form');

        // --- Secure Fetch Wrapper ---
        function secureFetch(url, options = {}) {
            const defaultOptions = {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };
            
            // For POST requests with FormData, headers are set by browser
            if (options.body instanceof FormData) {
                 options.headers = { ...defaultOptions.headers, ...options.headers };
            } else {
                options.headers = {
                    'Content-Type': 'application/json',
                    ...defaultOptions.headers,
                    ...options.headers,
                };
            }

            return fetch(url, options);
        }


        // --- Main Functions ---
        function fetchSchema() {
            container.innerHTML = '<p>جاري تحميل بنية الجدول...</p>';
            secureFetch(`<?php echo $managerBaseUrl; ?>/api/generic_crud_handler.php?action=describe&table=${tableName}`)
                .then(res => res.json()).then(handleSchemaResponse).catch(handleError);
        }

        function fetchData() {
            const tbody = document.getElementById('data-table-body');
            if(tbody) tbody.innerHTML = `<tr><td colspan="${(schema?.columns?.length || 0) + 1}" class="text-center p-4">جاري تحميل البيانات...</td></tr>`;
            secureFetch(`<?php echo $managerBaseUrl; ?>/api/generic_crud_handler.php?action=read&table=${tableName}`)
                .then(res => res.json()).then(populateTable).catch(handleError);
        }

        // --- UI Building ---
        function buildUI() {
            container.innerHTML = '';
            const header = el('div', { class: 'flex justify-between items-center mb-6' }, [
                el('h3', { class: 'text-2xl font-bold text-slate-800' }, [`إدارة جدول: ${tableName}`]),
                el('button', { id: 'add-new-btn', class: 'bg-sky-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-sky-700 flex items-center gap-2' }, [
                    el('i', { 'data-lucide': 'plus' }), 'إضافة سجل جديد'
                ])
            ]);
            const table = el('table', { id: 'data-table', class: 'w-full text-sm text-left text-slate-500' });
            const thead = el('thead', { class: 'text-xs text-slate-700 uppercase bg-slate-50' });
            const headerRow = el('tr', {}, schema.columns.map(c => el('th', { scope: 'col', class: 'px-6 py-3' }, [c.name])));
            headerRow.appendChild(el('th', { scope: 'col', class: 'px-6 py-3' }, ['إجراءات']));
            thead.appendChild(headerRow);
            table.append(thead, el('tbody', { id: 'data-table-body' }));
            container.append(header, el('div', { class: 'overflow-x-auto' }, [table]));
            
            lucide.createIcons();
            attachUIEventListeners();
            fetchData();
        }

        function populateTable(response) {
            const tbody = document.getElementById('data-table-body');
            tbody.innerHTML = '';
            if (response.success && response.data.length > 0) {
                response.data.forEach(row => {
                    const tr = el('tr', { class: 'bg-white border-b hover:bg-slate-50' });
                    tr.dataset.row = JSON.stringify(row);
                    schema.columns.forEach(col => tr.appendChild(el('td', { class: 'px-6 py-4' }, [row[col.name] || ''])));
                    const pk_val = row[schema.primary_key];
                    const actionsTd = el('td', { class: 'px-6 py-4 flex items-center gap-4' }, [
                        el('button', { 'data-action': 'edit', 'data-pk': pk_val, class: 'font-medium text-blue-600 hover:underline' }, ['تعديل']),
                        el('button', { 'data-action': 'delete', 'data-pk': pk_val, class: 'font-medium text-red-600 hover:underline' }, ['حذف'])
                    ]);
                    tr.appendChild(actionsTd);
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="${schema.columns.length + 1}" class="text-center p-4">لا توجد بيانات لعرضها.</td></tr>`;
            }
        }

        // --- Modal Logic ---
        function openModal(record = null) {
            crudForm.innerHTML = '';
            const isEditing = record !== null;
            document.getElementById('modal-title').textContent = isEditing ? 'تعديل السجل' : 'إضافة سجل جديد';
            
            schema.columns.forEach(col => {
                if (isEditing && col.is_primary) return; // Don't edit primary key

                const value = isEditing ? record[col.name] : '';
                const fieldGroup = el('div', { class: 'mb-4' });
                const label = el('label', { for: `field-${col.name}`, class: 'block text-sm font-medium text-slate-700 mb-1' }, [col.name]);
                const input = el('input', {
                    type: col.type.includes('INT') ? 'number' : 'text',
                    id: `field-${col.name}`,
                    name: col.name,
                    value: value,
                    class: 'w-full border-slate-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-sky-500'
                });
                fieldGroup.append(label, input);
                crudForm.appendChild(fieldGroup);
            });

            crudForm.onsubmit = (e) => handleFormSubmit(e, record);
            modalSubmitBtn.onclick = () => crudForm.requestSubmit();

            modalBackdrop.classList.remove('hidden');
            setTimeout(() => modal.style.opacity = 1, 10);
        }

        function closeModal() {
            modal.style.opacity = 0;
            setTimeout(() => modalBackdrop.classList.add('hidden'), 200);
        }

        // --- Event Handlers ---
        function handleSchemaResponse(response) {
            if (response.success) {
                schema = response.schema;
                if (!schema.primary_key) {
                    handleError({ message: 'لا يمكن إدارة جدول بدون مفتاح أساسي (Primary Key).' });
                    return;
                }
                buildUI();
            } else {
                handleError(response);
            }
        }

        function handleFormSubmit(event, record) {
            event.preventDefault();
            const formData = new FormData(crudForm);
            formData.append('action', record ? 'update' : 'create');
            formData.append('table', tableName);
            if (record) {
                formData.append('pk_col', schema.primary_key);
                formData.append('pk_val', record[schema.primary_key]);
            }
            
            secureFetch('<?php echo $managerBaseUrl; ?>/api/generic_crud_handler.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        showToast(response.message, 'success');
                        closeModal();
                        fetchData();
                    } else {
                        showToast(response.message, 'error');
                    }
                }).catch(handleError);
        }

        function handleDelete(pk_val) {
            if (!confirm('هل أنت متأكد من رغبتك في حذف هذا السجل؟ لا يمكن التراجع عن هذا الإجراء.')) return;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('table', tableName);
            formData.append('pk_col', schema.primary_key);
            formData.append('pk_val', pk_val);

            secureFetch('<?php echo $managerBaseUrl; ?>/api/generic_crud_handler.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        showToast(response.message, 'success');
                        fetchData();
                    } else {
                        showToast(response.message, 'error');
                    }
                }).catch(handleError);
        }

        function attachUIEventListeners() {
            document.getElementById('add-new-btn').addEventListener('click', () => openModal());
            document.getElementById('data-table-body').addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                if (action === 'edit') {
                    const rowData = JSON.parse(e.target.closest('tr').dataset.row);
                    openModal(rowData);
                } else if (action === 'delete') {
                    handleDelete(e.target.dataset.pk);
                }
            });
            closeModalBtn.addEventListener('click', closeModal);
            modalBackdrop.addEventListener('click', (e) => { if (e.target === modalBackdrop) closeModal(); });
        }

        function handleError(error) {
            const message = error.message || 'حدث خطأ غير متوقع.';
            container.innerHTML = `<p class="text-red-500">${message}</p>`;
            console.error(error);
        }

        // --- Utilities ---
        function el(tag, attributes = {}, children = []) {
            const element = document.createElement(tag);
            Object.entries(attributes).forEach(([key, value]) => element.setAttribute(key, value));
            children.forEach(child => element.append(typeof child === 'string' ? document.createTextNode(child) : child));
            return element;
        }

        // --- Init ---
        fetchSchema();
    }
}
</script>
