<!-- Telcos Tab Content -->
<div class="space-y-6">
    <!-- Statistics Card -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Total Telcos</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white"><?= $totalTelcos ?></p>
            </div>
            <button onclick="showTelcoModal()" class="inline-flex items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i> Add Telco
            </button>
        </div>
    </div>

    <!-- Telcos Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">All Telcos</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage telco providers (MTN, AirtelTigo, Telecel, etc.)</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($telcos)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                <i class="fas fa-signal text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                                <p>No telcos found. Click "Add Telco" to create one.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($telcos as $telco): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($telco['name']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($telco['is_active']): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?= date('M j, Y', strtotime($telco['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick='editTelco(<?= json_encode($telco) ?>)' class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 text-xs font-semibold rounded-lg transition-colors mr-2">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </button>
                                    <button onclick="toggleTelcoStatus(<?= $telco['telco_id'] ?>, <?= $telco['is_active'] ? 0 : 1 ?>)" class="inline-flex items-center px-3 py-1.5 <?= $telco['is_active'] ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400' ?> text-xs font-semibold rounded-lg transition-colors mr-2">
                                        <i class="fas fa-<?= $telco['is_active'] ? 'ban' : 'check' ?> mr-1"></i> <?= $telco['is_active'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                    <button onclick="deleteTelco(<?= $telco['telco_id'] ?>, '<?= addslashes(htmlspecialchars($telco['name'])) ?>')" class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 text-xs font-semibold rounded-lg transition-colors">
                                        <i class="fas fa-trash mr-1"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Telco Modal -->
<div id="telcoModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="telcoModalTitle">Add Telco</h3>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" id="telcoAction" value="create_telco">
            <input type="hidden" name="telco_id" id="telcoId" value="">

            <div>
                <label for="telcoName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Telco Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="telcoName" required placeholder="e.g., MTN, AirtelTigo, Telecel"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="flex justify-end space-x-3 pt-2">
                <button type="button" onclick="hideTelcoModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                    <i class="fas fa-save mr-2"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showTelcoModal() {
    document.getElementById('telcoModalTitle').textContent = 'Add Telco';
    document.getElementById('telcoAction').value = 'create_telco';
    document.getElementById('telcoId').value = '';
    document.getElementById('telcoName').value = '';
    document.getElementById('telcoModal').classList.remove('hidden');
}

function editTelco(telco) {
    document.getElementById('telcoModalTitle').textContent = 'Edit Telco';
    document.getElementById('telcoAction').value = 'update_telco';
    document.getElementById('telcoId').value = telco.telco_id;
    document.getElementById('telcoName').value = telco.name;
    document.getElementById('telcoModal').classList.remove('hidden');
}

function hideTelcoModal() {
    document.getElementById('telcoModal').classList.add('hidden');
}

function toggleTelcoStatus(id, newStatus) {
    const label = newStatus === 1 ? 'activate' : 'deactivate';
    if (confirm(`Are you sure you want to ${label} this telco?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="toggle_telco_status">
            <input type="hidden" name="telco_id" value="${id}">
            <input type="hidden" name="new_status" value="${newStatus}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteTelco(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"?\n\nThis will remove it from all incident records.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_telco">
            <input type="hidden" name="telco_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
