import 'bootstrap';
import '../css/app.css';

// Client-side interactions for MizanFlow
document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile sidebar toggle and backdrop handling
    const sidebarToggle = document.getElementById('sidebarToggle');
    const appSidebar = document.getElementById('appSidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    const openSidebar = () => {
        if (appSidebar) appSidebar.classList.add('show');
        if (sidebarBackdrop) sidebarBackdrop.classList.add('show');
        document.body.style.overflow = window.innerWidth < 992 ? 'hidden' : '';
    };

    const closeSidebar = () => {
        if (appSidebar) appSidebar.classList.remove('show');
        if (sidebarBackdrop) sidebarBackdrop.classList.remove('show');
        document.body.style.overflow = '';
    };

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (appSidebar && appSidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', closeSidebar);
    }

    // Close mobile sidebar on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (appSidebar && appSidebar.classList.contains('show')) {
                closeSidebar();
            }
            closeCommandPalette();
        }
    });

    // Close mobile sidebar when clicking any navigation link
    const sidebarLinks = document.querySelectorAll('.sidebar-sub-link, .sidebar-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992) {
                closeSidebar();
            }
        });
    });

    // 2. Auto-dismiss alert messages after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible:not(.alert-persistent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            try {
                const bsAlert = window.bootstrap?.Alert?.getOrCreateInstance(alert);
                bsAlert?.close();
            } catch (e) {
                // Ignore if already closed
            }
        }, 5000);
    });

    // 3. Smart auto-focus for barcode or search inputs
    const autofocusTarget = document.querySelector('[data-autofocus], #barcode, #search');
    if (autofocusTarget && !document.querySelector('.modal.show')) {
        setTimeout(() => autofocusTarget.focus(), 100);
    }

    // 4. Command Palette (Ctrl + K / Cmd + K)
    const commandPalette = document.getElementById('commandPalette');
    const commandPaletteInput = document.getElementById('commandPaletteInput');
    const commandPaletteTriggers = document.querySelectorAll('#commandPaletteTrigger, #mobileSearchTrigger, [data-command-palette-trigger]');

    const openCommandPalette = () => {
        if (!commandPalette) return;
        commandPalette.classList.add('show');
        document.body.style.overflow = 'hidden';
        if (commandPaletteInput) {
            commandPaletteInput.value = '';
            filterCommandItems('');
            setTimeout(() => commandPaletteInput.focus(), 50);
        }
    };

    const closeCommandPalette = () => {
        if (!commandPalette) return;
        commandPalette.classList.remove('show');
        document.body.style.overflow = '';
    };

    commandPaletteTriggers.forEach(trigger => {
        trigger.addEventListener('click', openCommandPalette);
    });

    if (commandPalette) {
        commandPalette.addEventListener('click', (e) => {
            if (e.target === commandPalette) {
                closeCommandPalette();
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (commandPalette && commandPalette.classList.contains('show')) {
                closeCommandPalette();
            } else {
                openCommandPalette();
            }
        }
    });

    const filterCommandItems = (query) => {
        const items = document.querySelectorAll('.command-palette-item');
        const groups = document.querySelectorAll('.command-palette-group');
        const q = query.trim().toLowerCase();

        items.forEach(item => {
            const text = (item.textContent || '').toLowerCase();
            if (!q || text.includes(q)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });

        groups.forEach(group => {
            const visibleItems = group.querySelectorAll('.command-palette-item:not([style*="display: none"])');
            group.style.display = visibleItems.length > 0 ? 'block' : 'none';
        });
    };

    if (commandPaletteInput) {
        commandPaletteInput.addEventListener('input', (e) => {
            filterCommandItems(e.target.value);
        });
    }

    // 5. One-Click Copy to Clipboard with floating micro-toast
    const copyToast = document.getElementById('copyToast') || createCopyToast();

    function createCopyToast() {
        const toast = document.createElement('div');
        toast.id = 'copyToast';
        toast.className = 'copy-toast';
        toast.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i><span>تم نسخ الرمز إلى الحافظة ✓</span>';
        document.body.appendChild(toast);
        return toast;
    }

    document.addEventListener('click', (e) => {
        const target = e.target.closest('.code-tag, [data-copy]');
        if (target) {
            const textToCopy = target.getAttribute('data-copy') || target.innerText.trim();
            if (textToCopy && navigator.clipboard) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    copyToast.classList.add('show');
                    setTimeout(() => copyToast.classList.remove('show'), 2000);
                }).catch(() => {});
            }
        }
    });
});

// 6. Global CSV Exporter Utility (Arabic UTF-8 with BOM for Excel)
window.exportTableToCSV = function(tableSelector, filename = 'export.csv') {
    const table = document.querySelector(tableSelector);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        // Skip hidden rows or empty state rows
        if (row.offsetParent === null || row.querySelector('.mz-empty-state')) return;

        let rowData = [];
        const cols = row.querySelectorAll('th, td');

        cols.forEach(col => {
            // Get clean text without extra whitespaces
            let text = col.innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/\s+/g, ' ').trim();
            // Escape double quotes
            text = text.replace(/"/g, '""');
            rowData.push('"' + text + '"');
        });

        if (rowData.length > 0) {
            csv.push(rowData.join(','));
        }
    });

    const csvString = '\uFEFF' + csv.join('\r\n');
    const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
