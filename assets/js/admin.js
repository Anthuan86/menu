/**
 * JavaScript para el panel de administración de menús
 */

const API_BASE_URL = '../api';

// Estado global
const state = {
    currentSegment: 'all',
    currentUser: null,
    currentUserType: null,
    modules: [],
    users: [],
    permissions: {}
};

// =====================================
// Utility Functions
// =====================================

function showLoading(show = true) {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.toggle('active', show);
    }
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.minWidth = '300px';
    alertDiv.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.style.transition = 'opacity 0.3s ease';
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
    }, 3000);
}

async function fetchAPI(endpoint, options = {}) {
    try {
        const response = await fetch(`${API_BASE_URL}/${endpoint}`, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Error en la petición');
        }

        return data.data;
    } catch (error) {
        console.error('API Error:', error);
        showAlert(error.message, 'danger');
        throw error;
    }
}

// =====================================
// Tab Management
// =====================================

function switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.classList.add('active');

    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`tab-${tabName}`).classList.add('active');

    // Load tab data
    switch(tabName) {
        case 'dashboard':
            loadDashboard();
            break;
        case 'modules':
            loadModules();
            break;
        case 'users':
            loadUsers();
            break;
        case 'permissions':
            // Permissions are loaded on demand
            break;
    }
}

// =====================================
// Dashboard Functions
// =====================================

async function loadDashboard() {
    showLoading(true);

    try {
        // Load user counts
        const userCounts = await fetchAPI('users.php?action=counts');
        document.getElementById('stat-colaboradores').textContent = userCounts.colaboradores || 0;
        document.getElementById('stat-clientes').textContent = userCounts.clientes || 0;
        document.getElementById('stat-partners').textContent = userCounts.partners || 0;

        // Load module stats
        const moduleStats = await fetchAPI('modules.php?action=stats');
        document.getElementById('stat-total-modules').textContent = moduleStats.total || 0;
        document.getElementById('stat-active-modules').textContent = moduleStats.active || 0;
        document.getElementById('stat-inactive-modules').textContent = moduleStats.inactive || 0;
    } catch (error) {
        console.error('Error loading dashboard:', error);
    } finally {
        showLoading(false);
    }
}

// =====================================
// Modules Functions
// =====================================

async function loadModules(segment = null) {
    showLoading(true);

    try {
        let endpoint = 'modules.php';
        if (segment && segment !== 'all') {
            endpoint += `?segment=${segment}`;
        }

        state.modules = await fetchAPI(endpoint);
        renderModulesTable(state.modules);
    } catch (error) {
        console.error('Error loading modules:', error);
    } finally {
        showLoading(false);
    }
}

function renderModulesTable(modules) {
    const tbody = document.getElementById('modules-table-body');

    if (!modules || modules.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No hay módulos disponibles</td></tr>';
        return;
    }

    tbody.innerHTML = modules.map(module => `
        <tr>
            <td>${module.id}</td>
            <td>
                ${module.icon ? `<i class="fas ${module.icon}"></i>` : ''}
                ${module.name}
            </td>
            <td>
                <span class="badge badge-${getSegmentColor(module.segment)}">
                    ${getSegmentLabel(module.segment)}
                </span>
            </td>
            <td><small>${module.url}</small></td>
            <td>${module.icon || '-'}</td>
            <td>${module.parent_id || '-'}</td>
            <td>${module.order_position}</td>
            <td>
                <span class="badge ${module.is_active == 1 ? 'badge-success' : 'badge-danger'}">
                    ${module.is_active == 1 ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editModule(${module.id})" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-secondary" onclick="toggleModuleStatus(${module.id}, ${module.is_active == 1 ? 0 : 1})" title="${module.is_active == 1 ? 'Desactivar' : 'Activar'}">
                    <i class="fas fa-${module.is_active == 1 ? 'toggle-on' : 'toggle-off'}"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteModule(${module.id})" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function filterModules(segment) {
    // Update active pill
    document.querySelectorAll('#tab-modules .segment-pill').forEach(pill => {
        pill.classList.remove('active');
    });
    event.target.classList.add('active');

    state.currentSegment = segment;
    loadModules(segment === 'all' ? null : segment);
}

function getSegmentColor(segment) {
    const colors = {
        'colaborador': 'info',
        'cliente': 'success',
        'partner': 'warning'
    };
    return colors[segment] || 'info';
}

function getSegmentLabel(segment) {
    const labels = {
        'colaborador': 'Colaborador',
        'cliente': 'Cliente',
        'partner': 'Partner'
    };
    return labels[segment] || segment;
}

// Modal Functions
function openModuleModal(moduleId = null) {
    const modal = document.getElementById('moduleModal');
    const title = document.getElementById('moduleModalTitle');
    const form = document.getElementById('moduleForm');

    form.reset();

    if (moduleId) {
        title.textContent = 'Editar Módulo';
        loadModuleData(moduleId);
    } else {
        title.textContent = 'Nuevo Módulo';
        document.getElementById('module-id').value = '';
    }

    modal.classList.add('active');
}

function closeModuleModal() {
    document.getElementById('moduleModal').classList.remove('active');
}

async function loadModuleData(moduleId) {
    showLoading(true);

    try {
        const module = await fetchAPI(`modules.php?module_id=${moduleId}`);

        document.getElementById('module-id').value = module.id;
        document.getElementById('module-name').value = module.name;
        document.getElementById('module-description').value = module.description || '';
        document.getElementById('module-url').value = module.url;
        document.getElementById('module-icon').value = module.icon || '';
        document.getElementById('module-segment').value = module.segment;
        document.getElementById('module-order').value = module.order_position;
        document.getElementById('module-active').checked = module.is_active == 1;

        await loadParentModules();
        document.getElementById('module-parent').value = module.parent_id || '';
    } catch (error) {
        console.error('Error loading module:', error);
    } finally {
        showLoading(false);
    }
}

async function loadParentModules() {
    const segment = document.getElementById('module-segment').value;
    const parentSelect = document.getElementById('module-parent');
    const currentModuleId = document.getElementById('module-id').value;

    parentSelect.innerHTML = '<option value="">-- Sin padre (menú principal) --</option>';

    if (!segment) return;

    try {
        const modules = await fetchAPI(`modules.php?segment=${segment}`);

        modules.forEach(module => {
            // No permitir seleccionar el módulo actual como padre
            if (module.id != currentModuleId && !module.parent_id) {
                const option = document.createElement('option');
                option.value = module.id;
                option.textContent = module.name;
                parentSelect.appendChild(option);
            }
        });
    } catch (error) {
        console.error('Error loading parent modules:', error);
    }
}

async function saveModule(event) {
    event.preventDefault();
    showLoading(true);

    const moduleId = document.getElementById('module-id').value;
    const moduleData = {
        name: document.getElementById('module-name').value,
        description: document.getElementById('module-description').value,
        url: document.getElementById('module-url').value,
        icon: document.getElementById('module-icon').value,
        segment: document.getElementById('module-segment').value,
        parent_id: document.getElementById('module-parent').value || null,
        order_position: parseInt(document.getElementById('module-order').value),
        is_active: document.getElementById('module-active').checked ? 1 : 0
    };

    try {
        if (moduleId) {
            // Update
            await fetchAPI(`modules.php?module_id=${moduleId}`, {
                method: 'PUT',
                body: JSON.stringify(moduleData)
            });
            showAlert('Módulo actualizado correctamente', 'success');
        } else {
            // Create
            await fetchAPI('modules.php', {
                method: 'POST',
                body: JSON.stringify(moduleData)
            });
            showAlert('Módulo creado correctamente', 'success');
        }

        closeModuleModal();
        loadModules(state.currentSegment === 'all' ? null : state.currentSegment);
    } catch (error) {
        console.error('Error saving module:', error);
    } finally {
        showLoading(false);
    }
}

function editModule(moduleId) {
    openModuleModal(moduleId);
}

async function toggleModuleStatus(moduleId, isActive) {
    showLoading(true);

    try {
        await fetchAPI(`modules.php?module_id=${moduleId}&action=toggle`, {
            method: 'PUT',
            body: JSON.stringify({ is_active: isActive })
        });

        showAlert('Estado actualizado correctamente', 'success');
        loadModules(state.currentSegment === 'all' ? null : state.currentSegment);
    } catch (error) {
        console.error('Error toggling module status:', error);
    } finally {
        showLoading(false);
    }
}

async function deleteModule(moduleId) {
    if (!confirm('¿Está seguro de eliminar este módulo? Esta acción no se puede deshacer.')) {
        return;
    }

    showLoading(true);

    try {
        await fetchAPI(`modules.php?module_id=${moduleId}`, {
            method: 'DELETE'
        });

        showAlert('Módulo eliminado correctamente', 'success');
        loadModules(state.currentSegment === 'all' ? null : state.currentSegment);
    } catch (error) {
        console.error('Error deleting module:', error);
    } finally {
        showLoading(false);
    }
}

// =====================================
// Users Functions
// =====================================

async function loadUsers(segment = null) {
    showLoading(true);

    try {
        let endpoint = 'users.php';
        if (segment && segment !== 'all') {
            endpoint += `?segment=${segment}`;
        }

        const data = await fetchAPI(endpoint);

        // Flatten data if it's segmented
        if (data.colaboradores || data.clientes || data.partners) {
            state.users = [
                ...(data.colaboradores || []),
                ...(data.clientes || []),
                ...(data.partners || [])
            ];
        } else {
            state.users = data;
        }

        renderUsersTable(state.users);
    } catch (error) {
        console.error('Error loading users:', error);
    } finally {
        showLoading(false);
    }
}

function renderUsersTable(users) {
    const tbody = document.getElementById('users-table-body');

    if (!users || users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay usuarios disponibles</td></tr>';
        return;
    }

    tbody.innerHTML = users.map(user => {
        const fullName = `${user.first_name || ''} ${user.last_name || ''}`.trim();
        const account = user.accountname || user.user_name || '-';

        return `
            <tr>
                <td>${user.id}</td>
                <td>${fullName}</td>
                <td>${user.email}</td>
                <td>
                    <span class="badge badge-${getSegmentColor(user.user_type)}">
                        ${getSegmentLabel(user.user_type)}
                    </span>
                </td>
                <td>${account}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="manageUserPermissions('${user.id}', '${user.user_type}')" title="Gestionar Permisos">
                        <i class="fas fa-shield-alt"></i> Permisos
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function filterUsers(segment) {
    // Update active pill
    document.querySelectorAll('#tab-users .segment-pill').forEach(pill => {
        pill.classList.remove('active');
    });
    event.target.classList.add('active');

    if (segment === 'all') {
        renderUsersTable(state.users);
    } else {
        const filtered = state.users.filter(u => u.user_type === segment);
        renderUsersTable(filtered);
    }
}

async function searchUsers() {
    const searchTerm = document.getElementById('user-search').value.trim();

    if (!searchTerm) {
        renderUsersTable(state.users);
        return;
    }

    if (searchTerm.length < 2) {
        return;
    }

    showLoading(true);

    try {
        const results = await fetchAPI(`users.php?search=${encodeURIComponent(searchTerm)}`);
        renderUsersTable(results);
    } catch (error) {
        console.error('Error searching users:', error);
    } finally {
        showLoading(false);
    }
}

function manageUserPermissions(userId, userType) {
    // Switch to permissions tab
    switchTab('permissions');

    // Set segment and user
    document.getElementById('permission-segment').value = userType;
    loadUsersForPermissions();

    setTimeout(() => {
        document.getElementById('permission-user').value = userId;
        loadUserPermissions();
    }, 500);
}

// =====================================
// Permissions Functions
// =====================================

async function loadUsersForPermissions() {
    const segment = document.getElementById('permission-segment').value;
    const userSelect = document.getElementById('permission-user');

    userSelect.innerHTML = '<option value="">-- Seleccione un usuario --</option>';
    document.getElementById('permissions-container').style.display = 'none';

    if (!segment) return;

    showLoading(true);

    try {
        const users = await fetchAPI(`users.php?segment=${segment}`);

        users.forEach(user => {
            const fullName = `${user.first_name || ''} ${user.last_name || ''}`.trim();
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = `${fullName} (${user.email})`;
            userSelect.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading users for permissions:', error);
    } finally {
        showLoading(false);
    }
}

async function loadUserPermissions() {
    const userId = document.getElementById('permission-user').value;
    const userType = document.getElementById('permission-segment').value;

    if (!userId || !userType) return;

    state.currentUser = userId;
    state.currentUserType = userType;

    showLoading(true);

    try {
        const modules = await fetchAPI(`permissions.php?action=modules&user_id=${userId}&user_type=${userType}`);
        renderPermissionsList(modules);
        document.getElementById('permissions-container').style.display = 'block';
    } catch (error) {
        console.error('Error loading user permissions:', error);
    } finally {
        showLoading(false);
    }
}

function renderPermissionsList(modules) {
    const container = document.getElementById('permissions-list');

    if (!modules || modules.length === 0) {
        container.innerHTML = '<p>No hay módulos disponibles para este segmento</p>';
        return;
    }

    // Group by parent
    const grouped = {};
    modules.forEach(module => {
        const parentId = module.parent_id || 'root';
        if (!grouped[parentId]) {
            grouped[parentId] = [];
        }
        grouped[parentId].push(module);
    });

    // Render root modules
    let html = '<div class="tree">';

    if (grouped['root']) {
        grouped['root'].forEach(module => {
            html += renderPermissionItem(module, grouped);
        });
    }

    html += '</div>';
    container.innerHTML = html;
}

function renderPermissionItem(module, grouped) {
    const hasChildren = grouped[module.id] && grouped[module.id].length > 0;
    const isChecked = module.has_permission == 1 ? 'checked' : '';

    let html = `
        <div class="tree-item">
            <div class="form-check">
                <input
                    type="checkbox"
                    class="form-check-input permission-checkbox"
                    id="perm-${module.id}"
                    data-module-id="${module.id}"
                    ${isChecked}
                    ${module.is_active == 0 ? 'disabled' : ''}
                >
                <label for="perm-${module.id}">
                    ${module.icon ? `<i class="fas ${module.icon}"></i>` : ''}
                    ${module.name}
                    ${module.is_active == 0 ? '<span class="badge badge-danger">Inactivo</span>' : ''}
                </label>
            </div>
    `;

    if (hasChildren) {
        html += '<div class="tree-children">';
        grouped[module.id].forEach(child => {
            html += renderPermissionItem(child, grouped);
        });
        html += '</div>';
    }

    html += '</div>';
    return html;
}

async function savePermissions() {
    const userId = state.currentUser;
    const userType = state.currentUserType;

    if (!userId || !userType) {
        showAlert('Seleccione un usuario primero', 'warning');
        return;
    }

    showLoading(true);

    try {
        // Get all checkboxes
        const checkboxes = document.querySelectorAll('.permission-checkbox');
        const permissions = {};

        checkboxes.forEach(checkbox => {
            const moduleId = checkbox.dataset.moduleId;
            permissions[moduleId] = checkbox.checked ? 1 : 0;
        });

        await fetchAPI('permissions.php?action=bulk', {
            method: 'POST',
            body: JSON.stringify({
                user_id: userId,
                user_type: userType,
                permissions: permissions
            })
        });

        showAlert('Permisos guardados correctamente', 'success');
    } catch (error) {
        console.error('Error saving permissions:', error);
    } finally {
        showLoading(false);
    }
}

async function resetPermissions() {
    if (!confirm('¿Está seguro de resetear todos los permisos de este usuario?')) {
        return;
    }

    const userId = state.currentUser;
    const userType = state.currentUserType;

    showLoading(true);

    try {
        await fetchAPI(`permissions.php?user_id=${userId}&user_type=${userType}&action=reset`, {
            method: 'DELETE'
        });

        showAlert('Permisos reseteados correctamente', 'success');
        loadUserPermissions();
    } catch (error) {
        console.error('Error resetting permissions:', error);
    } finally {
        showLoading(false);
    }
}

// =====================================
// Initialize
// =====================================

document.addEventListener('DOMContentLoaded', function() {
    loadDashboard();
});
