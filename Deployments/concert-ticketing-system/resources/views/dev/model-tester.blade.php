<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Model Tester | Dev Tools</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8 font-sans">
    <div class="max-w-5xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-1 text-gray-800">Model Tester</h1>
        <p class="text-sm text-gray-500 mb-6">Test Eloquent model methods interactively</p>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">1. Model Name (e.g. User, Concert)</label>
                <div class="flex gap-2">
                    <input type="text" id="modelInput" placeholder="Concert" value="Concert"
                        class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <button id="loadMethodsBtn" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700">
                        Load
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">2. Select Function</label>
                <select id="methodSelect" disabled
                    class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">-- Load a model first --</option>
                </select>
            </div>
        </div>

        <div id="instanceIdContainer" class="mb-6 hidden bg-blue-50 p-4 rounded-md border border-blue-100">
            <label class="block text-sm font-bold text-blue-800 mb-2">Instance ID (Primary Key)</label>
            <input type="text" id="instanceIdInput" placeholder="Enter UUID..."
                class="w-full border-gray-300 rounded-md shadow-sm p-2 border">
            <p class="text-xs text-blue-600 mt-1">Required for instance methods (e.g. relationships)</p>
        </div>

        <div id="paramsContainer" class="mb-6 hidden">
            <h3 class="text-sm font-bold text-gray-700 mb-2">Parameters</h3>
            <div id="paramsList" class="space-y-3 p-4 bg-gray-50 border border-gray-200 rounded-md"></div>
        </div>

        <button id="executeBtn" disabled
            class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded hover:bg-blue-700 disabled:opacity-50 transition-colors">
            Execute
        </button>

        <div class="mt-8">
            <h3 class="text-lg font-semibold mb-2 text-gray-800">Result:</h3>
            <pre id="resultOutput"
                class="bg-gray-900 text-green-400 p-4 rounded-md overflow-x-auto min-h-[150px] max-h-[500px] text-sm font-mono shadow-inner">Waiting for input...</pre>
        </div>
    </div>

    <script>
        const API = '/dev/models';
        const modelInput        = document.getElementById('modelInput');
        const loadMethodsBtn    = document.getElementById('loadMethodsBtn');
        const methodSelect      = document.getElementById('methodSelect');
        const paramsContainer   = document.getElementById('paramsContainer');
        const paramsList        = document.getElementById('paramsList');
        const executeBtn        = document.getElementById('executeBtn');
        const resultOutput      = document.getElementById('resultOutput');
        const instanceIdCont    = document.getElementById('instanceIdContainer');
        const instanceIdInput   = document.getElementById('instanceIdInput');

        let methods = [];
        let selected = null;

        loadMethodsBtn.addEventListener('click', async () => {
            const model = modelInput.value.trim();
            if (!model) return;
            resultOutput.textContent = 'Loading methods for: ' + model + '...';
            try {
                const res  = await fetch(API + '/methods', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ model })
                });
                const data = await res.json();
                if (!Array.isArray(data) || data.length === 0) {
                    resultOutput.textContent = 'Model not found: ' + model;
                    return;
                }
                methods = data;
                methodSelect.innerHTML = '<option value="">-- Select a function --</option>';
                data.forEach(m => {
                    const tag = m.type === 'static' ? '[Static]' : '[Instance]';
                    const opt = document.createElement('option');
                    opt.value = m.name;
                    opt.textContent = m.name + ' ' + tag;
                    methodSelect.appendChild(opt);
                });
                methodSelect.disabled = false;
                resultOutput.textContent = 'Loaded ' + data.length + ' methods. Select one above.';
            } catch (e) {
                resultOutput.textContent = 'Error: ' + e.message;
            }
        });

        methodSelect.addEventListener('change', function () {
            selected = methods.find(m => m.name === this.value) || null;
            paramsContainer.classList.add('hidden');
            instanceIdCont.classList.add('hidden');
            executeBtn.disabled = !selected;
            if (!selected) return;

            if (selected.type === 'instance') instanceIdCont.classList.remove('hidden');

            if (selected.params.length > 0) {
                paramsContainer.classList.remove('hidden');
                paramsList.innerHTML = selected.params.map(p =>
                    '<div>' +
                    '<label class="text-sm text-gray-700 font-medium mb-1 block">' + p + '</label>' +
                    '<input type="text" data-param="' + p + '" placeholder="Enter value for: ' + p + '" ' +
                    'class="param-input w-full border-gray-300 rounded-md shadow-sm p-2 border text-sm">' +
                    '</div>'
                ).join('');
            }
        });

        executeBtn.addEventListener('click', async function () {
            if (!selected) return;
            const params = {};
            document.querySelectorAll('.param-input').forEach(i => { params[i.dataset.param] = i.value; });
            resultOutput.textContent = 'Executing ' + selected.name + '...';
            this.disabled = true;
            try {
                const res = await fetch(API + '/execute', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        model: modelInput.value.trim(),
                        method_name: selected.name,
                        type: selected.type,
                        params,
                        instance_id: instanceIdInput.value
                    })
                });
                const result = await res.json();
                resultOutput.textContent = result.success
                    ? JSON.stringify(result.data, null, 2)
                    : 'ERROR:\n' + (result.error || JSON.stringify(result));
            } catch (e) {
                resultOutput.textContent = 'Network Error:\n' + e.message;
            } finally {
                this.disabled = false;
            }
        });
    </script>
</body>
</html>