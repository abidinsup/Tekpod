/**
 * TEKPOD - Main JavaScript
 * Handles interactivity, live preview, search, and UI enhancements
 */

// ============================================
// SIDEBAR TOGGLE (Mobile)
// ============================================

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
    if (overlay) {
        overlay.classList.toggle('active');
    }
}

// Close sidebar on window resize
window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
    }
});

// ============================================
// TOAST NOTIFICATIONS
// ============================================

function showToast(message, type = 'success', duration = 4000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };

    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || '📌'}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;

    container.appendChild(toast);

    // Auto remove
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ============================================
// MODAL
// ============================================

function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal on overlay click
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Close modal on Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});

// ============================================
// NUMBER INPUT ADJUSTERS
// ============================================

function adjustNumber(inputId, amount) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    let current = parseInt(input.value) || 0;
    let newValue = current + amount;
    if (newValue < 0) newValue = 0;
    
    input.value = newValue;
    
    // Trigger input event for live preview
    input.dispatchEvent(new Event('input', { bubbles: true }));
    
    // Button animation
    const btn = event.target.closest('button');
    if (btn) {
        btn.style.transform = 'scale(0.9)';
        setTimeout(() => btn.style.transform = '', 150);
    }
}

// ============================================
// SEARCH ORDERS (Dashboard)
// ============================================

function initSearch() {
    const searchInput = document.getElementById('searchOrders');
    if (!searchInput) return;

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.order-row');
        
        rows.forEach(row => {
            const searchData = row.getAttribute('data-search') || '';
            if (query === '' || searchData.includes(query)) {
                row.style.display = '';
                row.style.animation = 'fadeIn 0.3s ease';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

// ============================================
// AJAX FORM SUBMISSION
// ============================================

async function handleAjaxSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    if (!submitBtn) return;

    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner"></span> Menyimpan...';
    submitBtn.disabled = true;

    try {
        const formData = new FormData(form);
        const response = await fetch(form.action || window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message || 'Data berhasil disimpan!', 'success');
            if (form.id === 'addOrderForm') {
                closeModal('addOrderModal');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                form.reset();
                if (typeof updateLivePreview === 'function') updateLivePreview();
                if (typeof updateQCPreview === 'function') updateQCPreview();
                
                // If it's QC, we might want to reload to reflect the "Selesai" status in sidebar/dashboard
                if (form.id === 'qcForm') {
                    setTimeout(() => window.location.reload(), 1500);
                }
            }
        } else {
            showToast(result.message || 'Gagal menyimpan data', 'error');
        }
    } catch (error) {
        console.error('Submission error:', error);
        showToast('Terjadi kesalahan koneksi', 'error');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

function initFormHandlers() {
    const forms = ['productionForm', 'qcForm', 'addOrderForm'];
    forms.forEach(id => {
        const form = document.getElementById(id);
        if (form) {
            form.addEventListener('submit', handleAjaxSubmit);
        }
    });
}

// ============================================
// LIVE PREVIEW (Input Produksi)
// ============================================

function updateLivePreview() {
    const preview = document.getElementById('livePreview');
    if (!preview) return;

    const orderSelect = document.getElementById('order_id');
    const processSelect = document.getElementById('process_id');
    const bgsInput = document.getElementById('hasil_bgs');
    const ncInput = document.getElementById('hasil_nc');
    const ngInput = document.getElementById('hasil_ng');
    
    // QC mode inputs
    const totalOkInput = document.getElementById('total_ok');
    const totalRejectInput = document.getElementById('total_reject');
    
    const isQC = !processSelect;

    const orderText = orderSelect && orderSelect.selectedIndex > 0 
        ? orderSelect.options[orderSelect.selectedIndex].text 
        : null;

    if (!orderText) {
        preview.innerHTML = `
            <div class="empty-state" style="padding:var(--space-6) 0;">
                <div class="empty-state-icon">📋</div>
                <p class="empty-state-desc">Isi form untuk melihat preview</p>
            </div>
        `;
        return;
    }

    if (isQC) {
        const ok = parseInt(totalOkInput?.value) || 0;
        const reject = parseInt(totalRejectInput?.value) || 0;
        const total = ok + reject;
        const keterangan = document.getElementById('keterangan')?.value || '-';
        
        preview.innerHTML = `
            <div style="padding:var(--space-2) 0;">
                <div style="font-size:var(--font-size-xs);color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:var(--space-1);">Order</div>
                <div style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-primary);margin-bottom:var(--space-4);">${orderText}</div>
                
                <div style="font-size:var(--font-size-xs);color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:var(--space-1);">Proses</div>
                <div style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-primary);margin-bottom:var(--space-4);">✅ QC / Sortir</div>
                
                <div style="display:flex;gap:var(--space-4);margin-bottom:var(--space-4);">
                    <div style="flex:1;background:rgba(34,197,94,0.08);border-radius:var(--radius-lg);padding:var(--space-3);text-align:center;">
                        <div style="font-size:var(--font-size-2xl);font-weight:800;color:var(--status-success);">${ok.toLocaleString('id-ID')}</div>
                        <div style="font-size:var(--font-size-xs);color:var(--text-muted);">OK</div>
                    </div>
                    <div style="flex:1;background:rgba(239,68,68,0.08);border-radius:var(--radius-lg);padding:var(--space-3);text-align:center;">
                        <div style="font-size:var(--font-size-2xl);font-weight:800;color:var(--status-danger);">${reject.toLocaleString('id-ID')}</div>
                        <div style="font-size:var(--font-size-xs);color:var(--text-muted);">Reject</div>
                    </div>
                </div>
                
                <div style="font-size:var(--font-size-xs);color:var(--text-muted);margin-bottom:var(--space-1);">Total</div>
                <div style="font-size:var(--font-size-lg);font-weight:800;color:var(--accent-primary);margin-bottom:var(--space-3);">${total.toLocaleString('id-ID')}</div>
                
                <div style="font-size:var(--font-size-xs);color:var(--text-muted);margin-bottom:var(--space-1);">Keterangan</div>
                <div style="font-size:var(--font-size-sm);color:var(--text-secondary);">${keterangan || '-'}</div>
            </div>
        `;
        return;
    }

    const processText = processSelect && processSelect.selectedIndex > 0
        ? processSelect.options[processSelect.selectedIndex].text
        : 'Belum dipilih';

    const bgs = parseInt(bgsInput?.value) || 0;
    const nc = parseInt(ncInput?.value) || 0;
    const ng = parseInt(ngInput?.value) || 0;
    const total = bgs + nc + ng;
    
    const bgsP = total > 0 ? ((bgs / total) * 100).toFixed(1) : 0;
    const ncP = total > 0 ? ((nc / total) * 100).toFixed(1) : 0;
    const ngP = total > 0 ? ((ng / total) * 100).toFixed(1) : 0;

    preview.innerHTML = `
        <div style="padding:var(--space-2) 0;">
            <div style="font-size:var(--font-size-xs);color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:var(--space-1);">Order</div>
            <div style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-primary);margin-bottom:var(--space-4);">${orderText}</div>
            
            <div style="font-size:var(--font-size-xs);color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:var(--space-1);">Proses</div>
            <div style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-primary);margin-bottom:var(--space-4);">${processText}</div>
            
            <div style="display:flex;gap:var(--space-3);margin-bottom:var(--space-4);">
                <div style="flex:1;background:rgba(34,197,94,0.08);border-radius:var(--radius-lg);padding:var(--space-3);text-align:center;">
                    <div style="font-size:var(--font-size-xl);font-weight:800;color:var(--status-success);">${bgs.toLocaleString('id-ID')}</div>
                    <div style="font-size:var(--font-size-xs);color:var(--text-muted);">BGS</div>
                </div>
                <div style="flex:1;background:rgba(245,158,11,0.08);border-radius:var(--radius-lg);padding:var(--space-3);text-align:center;">
                    <div style="font-size:var(--font-size-xl);font-weight:800;color:var(--status-warning);">${nc.toLocaleString('id-ID')}</div>
                    <div style="font-size:var(--font-size-xs);color:var(--text-muted);">NC</div>
                </div>
                <div style="flex:1;background:rgba(239,68,68,0.08);border-radius:var(--radius-lg);padding:var(--space-3);text-align:center;">
                    <div style="font-size:var(--font-size-xl);font-weight:800;color:var(--status-danger);">${ng.toLocaleString('id-ID')}</div>
                    <div style="font-size:var(--font-size-xs);color:var(--text-muted);">NG</div>
                </div>
            </div>
            
            <div style="font-size:var(--font-size-xs);color:var(--text-muted);margin-bottom:var(--space-2);">Total Output</div>
            <div style="font-size:var(--font-size-2xl);font-weight:800;color:var(--accent-primary);margin-bottom:var(--space-4);">${total.toLocaleString('id-ID')}</div>
            
            ${total > 0 ? `
            <div style="display:flex;flex-direction:column;gap:var(--space-2);">
                <div style="display:flex;align-items:center;gap:var(--space-2);">
                    <span style="font-size:var(--font-size-xs);width:28px;color:var(--text-muted);">BGS</span>
                    <div style="flex:1;height:6px;background:rgba(148,163,184,0.08);border-radius:99px;overflow:hidden;">
                        <div style="width:${bgsP}%;height:100%;background:linear-gradient(90deg,#22c55e,#4ade80);border-radius:99px;transition:width 0.4s ease;"></div>
                    </div>
                    <span style="font-size:var(--font-size-xs);color:var(--status-success);width:40px;text-align:right;">${bgsP}%</span>
                </div>
                <div style="display:flex;align-items:center;gap:var(--space-2);">
                    <span style="font-size:var(--font-size-xs);width:28px;color:var(--text-muted);">NC</span>
                    <div style="flex:1;height:6px;background:rgba(148,163,184,0.08);border-radius:99px;overflow:hidden;">
                        <div style="width:${ncP}%;height:100%;background:linear-gradient(90deg,#f59e0b,#fbbf24);border-radius:99px;transition:width 0.4s ease;"></div>
                    </div>
                    <span style="font-size:var(--font-size-xs);color:var(--status-warning);width:40px;text-align:right;">${ncP}%</span>
                </div>
                <div style="display:flex;align-items:center;gap:var(--space-2);">
                    <span style="font-size:var(--font-size-xs);width:28px;color:var(--text-muted);">NG</span>
                    <div style="flex:1;height:6px;background:rgba(148,163,184,0.08);border-radius:99px;overflow:hidden;">
                        <div style="width:${ngP}%;height:100%;background:linear-gradient(90deg,#ef4444,#f87171);border-radius:99px;transition:width 0.4s ease;"></div>
                    </div>
                    <span style="font-size:var(--font-size-xs);color:var(--status-danger);width:40px;text-align:right;">${ngP}%</span>
                </div>
            </div>
            ` : ''}
        </div>
    `;
}

// ============================================
// CONDITIONAL FORM FIELDS
// ============================================

function initConditionalFields() {
    const processSelect = document.getElementById('process_id');
    const tipeGroup = document.getElementById('tipeProses-group');
    
    if (processSelect && tipeGroup) {
        processSelect.addEventListener('change', () => {
            // Show tipe_proses for Laminating (id=2)
            if (processSelect.value === '2') {
                tipeGroup.style.display = 'block';
                tipeGroup.style.animation = 'fadeInUp 0.3s ease';
            } else {
                tipeGroup.style.display = 'none';
            }
        });
    }
}

// ============================================
// FORM LIVE PREVIEW LISTENERS
// ============================================

function initLivePreviewListeners() {
    const watchIds = ['order_id', 'process_id', 'hasil_bgs', 'hasil_nc', 'hasil_ng', 'total_ok', 'total_reject', 'keterangan'];
    
    watchIds.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', updateLivePreview);
            element.addEventListener('change', updateLivePreview);
        }
    });
}

// ============================================
// ANIMATE PROGRESS BARS ON SCROLL
// ============================================

function initProgressAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const fills = entry.target.querySelectorAll('.progress-bar-fill, .result-bar-value');
                fills.forEach(fill => {
                    const width = fill.style.width;
                    fill.style.width = '0%';
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            fill.style.width = width;
                        });
                    });
                });
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });

    document.querySelectorAll('.progress-bar-container, .process-result-card, .stat-card').forEach(el => {
        observer.observe(el);
    });
}

// ============================================
// COUNTER ANIMATION
// ============================================

function animateCounters() {
    document.querySelectorAll('.stat-card-value').forEach(el => {
        const text = el.textContent.trim();
        const target = parseInt(text.replace(/\./g, '')) || 0;
        
        if (target === 0) return;
        
        let current = 0;
        const duration = 1000;
        const steps = 30;
        const increment = target / steps;
        const stepDuration = duration / steps;
        
        el.textContent = '0';
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            el.textContent = Math.round(current).toLocaleString('id-ID');
        }, stepDuration);
    });
}

// ============================================
// SIDEBAR OVERLAY STYLES (injected)
// ============================================

function injectDynamicStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.active {
            display: block;
        }
        @media (max-width: 1024px) {
            .sidebar.open {
                transform: translateX(0) !important;
                box-shadow: 4px 0 24px rgba(0, 0, 0, 0.5);
            }
        }
    `;
    document.head.appendChild(style);
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    injectDynamicStyles();
    initSearch();
    initConditionalFields();
    initLivePreviewListeners();
    initFormHandlers();
    
    // Delay animations slightly for better UX
    setTimeout(() => {
        initProgressAnimations();
        animateCounters();
    }, 200);
});
