document.addEventListener('DOMContentLoaded', () => {
    const preview = document.getElementById('certificatePreview');
    const templateSelector = document.getElementById('templateSelector');
    const saveTemplateBtn = document.getElementById('saveTemplateBtn');
    const addTextBtn = document.getElementById('addTextBtn');
    const propertiesPanel = document.getElementById('propertiesPanel');
    const fontSizeInput = document.getElementById('fontSize');
    const fontColorInput = document.getElementById('fontColor');
    const fontFamilyInput = document.getElementById('fontFamily');

    let selectedElement = null;
    let templates = [];

    function loadTemplates() {
        fetch('api/manage_certificate_templates.php?action=getAll')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    templates = data.templates;
                    templateSelector.innerHTML = '<option value="">اختر قالب محفوظ</option>';
                    templates.forEach(template => {
                        const option = document.createElement('option');
                        option.value = template.id;
                        option.textContent = template.name;
                        templateSelector.appendChild(option);
                    });
                }
            });
    }

    function loadTemplate(templateId) {
        const template = templates.find(t => t.id == templateId);
        if (!template) return;

        const templateData = JSON.parse(template.template_json);
        preview.innerHTML = '';
        preview.style.backgroundColor = templateData.backgroundColor;

        templateData.elements.forEach(element => {
            const el = document.createElement('div');
            el.innerHTML = element.html;
            el.style.cssText = element.style;
            el.classList.add('draggable');
            el.setAttribute('data-x', element.x);
            el.setAttribute('data-y', element.y);
            preview.appendChild(el);
        });
        initDesigner();
    }

    templateSelector.addEventListener('change', (e) => {
        if (e.target.value) {
            loadTemplate(e.target.value);
        }
    });

    saveTemplateBtn.addEventListener('click', () => {
        const templateName = prompt("يرجى إدخال اسم للقالب:");
        if (!templateName) return;

        const elements = [];
        preview.querySelectorAll('.draggable').forEach(el => {
            elements.push({
                html: el.innerHTML,
                style: el.style.cssText,
                x: el.getAttribute('data-x') || 0,
                y: el.getAttribute('data-y') || 0,
                width: el.style.width,
                height: el.style.height,
            });
        });
        const templateData = {
            backgroundColor: preview.style.backgroundColor || '#ffffff',
            elements: elements
        };

        fetch('api/manage_certificate_templates.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: templateName,
                template_json: JSON.stringify(templateData)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('تم حفظ القالب بنجاح!', 'success');
                loadTemplates();
            } else {
                showToast('فشل حفظ القالب.', 'error');
            }
        });
    });

    function initDesigner() {
        interact('.draggable').draggable({
            listeners: {
                move(event) {
                    const target = event.target;
                    const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                    const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

                    target.style.transform = `translate(${x}px, ${y}px)`;

                    target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
                }
            },
            inertia: true,
            modifiers: [
                interact.modifiers.restrictRect({
                    restriction: 'parent',
                    endOnly: true
                })
            ]
        }).resizable({
            edges: { left: true, right: true, bottom: true, top: true },
            listeners: {
                move(event) {
                    let { x, y } = event.target.dataset;

                    x = (parseFloat(x) || 0);
                    y = (parseFloat(y) || 0);

                    Object.assign(event.target.style, {
                        width: `${event.rect.width}px`,
                        height: `${event.rect.height}px`,
                    });
                }
            },
            modifiers: [
                interact.modifiers.restrictSize({
                    min: { width: 50, height: 20 }
                })
            ],
            inertia: true
        }).on('tap', (event) => {
            const target = event.currentTarget;
            if (selectedElement) {
                selectedElement.classList.remove('selected');
            }
            selectedElement = target;
            selectedElement.classList.add('selected');
            showProperties(selectedElement);
            event.stopPropagation();
        });
    }

    function showProperties(element) {
        propertiesPanel.style.transform = 'translateX(0)';
        fontSizeInput.value = parseInt(window.getComputedStyle(element).fontSize, 10);
        fontColorInput.value = rgbToHex(window.getComputedStyle(element).color);
        fontFamilyInput.value = window.getComputedStyle(element).fontFamily.split(',')[0].replace(/"/g, '');
    }

    function hideProperties() {
        propertiesPanel.style.transform = 'translateX(100%)';
        if (selectedElement) {
            selectedElement.classList.remove('selected');
            selectedElement = null;
        }
    }

    preview.addEventListener('click', (event) => {
        if (event.target === preview) {
            hideProperties();
        }
    });

    fontSizeInput.addEventListener('input', (e) => {
        if (selectedElement) selectedElement.style.fontSize = `${e.target.value}px`;
    });

    fontColorInput.addEventListener('input', (e) => {
        if (selectedElement) selectedElement.style.color = e.target.value;
    });

    fontFamilyInput.addEventListener('change', (e) => {
        if (selectedElement) selectedElement.style.fontFamily = e.target.value;
    });

    addTextBtn.addEventListener('click', () => {
        const newText = document.createElement('div');
        newText.classList.add('draggable');
        newText.textContent = 'نص جديد';
        newText.style.position = 'absolute';
        newText.style.top = '100px';
        newText.style.left = '100px';
        preview.appendChild(newText);
        initDesigner();
    });

    function rgbToHex(rgb) {
        if (!rgb || !rgb.includes('rgb')) return '#000000';
        let sep = rgb.indexOf(",") > -1 ? "," : " ";
        rgb = rgb.substr(4).split(")")[0].split(sep);

        let r = (+rgb[0]).toString(16),
            g = (+rgb[1]).toString(16),
            b = (+rgb[2]).toString(16);

        if (r.length == 1) r = "0" + r;
        if (g.length == 1) g = "0" + g;
        if (b.length == 1) b = "0" + b;

        return "#" + r + g + b;
    }

    loadTemplates();
    initDesigner();
});
