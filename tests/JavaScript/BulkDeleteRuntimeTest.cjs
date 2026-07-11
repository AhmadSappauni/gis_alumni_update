const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

function extractSource(source, startMarker, endMarker) {
    const start = source.indexOf(startMarker);
    assert.notEqual(start, -1, `Marker awal tidak ditemukan: ${startMarker}`);

    const end = source.indexOf(endMarker, start);
    assert.notEqual(end, -1, `Marker akhir tidak ditemukan: ${endMarker}`);

    return source.slice(start, end).trim();
}

test('select_all reports 10 deleted, 10 failed, and 10 unprocessed when batch 2 of 3 fails', async (t) => {
    const bladePath = path.resolve(
        __dirname,
        '../../resources/views/admin/index.blade.php'
    );
    const bladeSource = fs.readFileSync(bladePath, 'utf8');
    const errorFactorySource = extractSource(
        bladeSource,
        'function createBulkDeleteBatchError',
        '    function updateDeleteProgress'
    );
    const batchProcessorSource = extractSource(
        bladeSource,
        'async function deleteSelectedInBatches',
        '    async function handleBulkDeleteFailure'
    );

    let requestCount = 0;
    let reloadCount = 0;
    let successDialogCount = 0;
    const requests = [];
    const progressUpdates = [];

    const context = vm.createContext({
        currentDeleteFilters: () => ({ angkatan: '2020' }),
        showDeleteProgress: () => {},
        updateDeleteProgress: (done, total) => {
            progressUpdates.push([done, total]);
        },
        deleteBatch: async (payload, options = {}) => {
            requestCount++;
            requests.push(JSON.parse(JSON.stringify(payload)));

            if (requestCount === 2) {
                throw new Error('Simulated HTTP 500 on batch 2');
            }

            return options.returnMeta
                ? { deleted: 10, total: 30 }
                : 10;
        },
        Swal: {
            fire: async () => {
                successDialogCount++;
            },
        },
        window: {
            location: {
                reload: () => {
                    reloadCount++;
                },
            },
        },
    });

    vm.runInContext(
        `${errorFactorySource}
${batchProcessorSource}
globalThis.runBulkDelete = deleteSelectedInBatches;`,
        context
    );

    let caughtError;
    try {
        await context.runBulkDelete([], 30, true);
    } catch (error) {
        caughtError = error;
    }

    assert.ok(caughtError, 'Simulasi harus menghasilkan error pada batch kedua');

    const actualReport = JSON.parse(
        JSON.stringify(caughtError.bulkDeleteReport)
    );

    assert.deepEqual(actualReport, {
        deletedTotal: 10,
        failedBatchSize: 10,
        unprocessedTotal: 10,
        batchNumber: 2,
        totalBatches: 3,
    });
    assert.equal(caughtError.message, 'Simulated HTTP 500 on batch 2');
    assert.equal(requestCount, 2);
    assert.deepEqual(requests, [
        {
            angkatan: '2020',
            select_all: true,
            batch_size: 10,
            include_total: true,
        },
        {
            angkatan: '2020',
            select_all: true,
            batch_size: 10,
        },
    ]);
    assert.deepEqual(progressUpdates, [[10, 30]]);
    assert.equal(successDialogCount, 0);
    assert.equal(reloadCount, 0);

    t.diagnostic(`actual bulkDeleteReport: ${JSON.stringify(actualReport)}`);
    t.diagnostic(`actual requests before stop: ${JSON.stringify(requests)}`);
    t.diagnostic(`actual reload count: ${reloadCount}`);
});

test('select_all expands a DOM snapshot of 20 to the first server total of 30 and completes all batches', async (t) => {
    const bladePath = path.resolve(
        __dirname,
        '../../resources/views/admin/index.blade.php'
    );
    const bladeSource = fs.readFileSync(bladePath, 'utf8');
    const errorFactorySource = extractSource(
        bladeSource,
        'function createBulkDeleteBatchError',
        '    function updateDeleteProgress'
    );
    const updateProgressSource = extractSource(
        bladeSource,
        'function updateDeleteProgress',
        '    function showDeleteProgress'
    );
    const batchProcessorSource = extractSource(
        bladeSource,
        'async function deleteSelectedInBatches',
        '    async function handleBulkDeleteFailure'
    );

    let requestCount = 0;
    let reloadCount = 0;
    const initialProgressTotals = [];
    const requests = [];
    const dialogs = [];
    const progressTextHistory = [];
    const progressWidthHistory = [];
    const progressPercentHistory = [];
    const progressText = {};
    const progressPercent = {};
    const progressBarStyle = {};

    Object.defineProperty(progressText, 'textContent', {
        set: (value) => progressTextHistory.push(value),
    });
    Object.defineProperty(progressPercent, 'textContent', {
        set: (value) => progressPercentHistory.push(value),
    });
    Object.defineProperty(progressBarStyle, 'width', {
        set: (value) => progressWidthHistory.push(value),
    });

    const context = vm.createContext({
        currentDeleteFilters: () => ({ angkatan: '2020' }),
        showDeleteProgress: (total) => {
            initialProgressTotals.push(total);
        },
        document: {
            getElementById: (id) => {
                if (id === 'bulk-delete-progress-text') return progressText;
                if (id === 'bulk-delete-progress-bar') {
                    return { style: progressBarStyle };
                }
                if (id === 'bulk-delete-progress-percent') return progressPercent;
                return null;
            },
        },
        deleteBatch: async (payload, options = {}) => {
            requestCount++;
            requests.push(JSON.parse(JSON.stringify(payload)));

            return options.returnMeta
                ? { deleted: 10, total: 30 }
                : 10;
        },
        Swal: {
            fire: async (options) => {
                dialogs.push(JSON.parse(JSON.stringify(options)));
            },
        },
        window: {
            location: {
                reload: () => {
                    reloadCount++;
                },
            },
        },
    });

    vm.runInContext(
        `${errorFactorySource}
${updateProgressSource}
${batchProcessorSource}
globalThis.runBulkDelete = deleteSelectedInBatches;`,
        context
    );

    await context.runBulkDelete([], 20, true);

    assert.deepEqual(initialProgressTotals, [20]);
    assert.equal(requestCount, 3);
    assert.deepEqual(requests, [
        {
            angkatan: '2020',
            select_all: true,
            batch_size: 10,
            include_total: true,
        },
        {
            angkatan: '2020',
            select_all: true,
            batch_size: 10,
        },
        {
            angkatan: '2020',
            select_all: true,
            batch_size: 10,
        },
    ]);
    assert.deepEqual(progressTextHistory, [
        '10 dari 30 data berhasil dihapus',
        '20 dari 30 data berhasil dihapus',
        '30 dari 30 data berhasil dihapus',
        '30 dari 30 data berhasil dihapus',
    ]);
    assert.deepEqual(progressWidthHistory, [
        '33%',
        '67%',
        '100%',
        '100%',
    ]);
    assert.deepEqual(progressPercentHistory, [
        '33%',
        '67%',
        '100%',
        '100%',
    ]);
    assert.equal(dialogs.length, 1);
    assert.equal(dialogs[0].icon, 'success');
    assert.equal(dialogs[0].text, 'Semua data terpilih berhasil dihapus');
    assert.equal(reloadCount, 1);

    t.diagnostic(`initial progress total: ${initialProgressTotals[0]}`);
    t.diagnostic(`actual progress text history: ${JSON.stringify(progressTextHistory)}`);
    t.diagnostic(`actual progress width history: ${JSON.stringify(progressWidthHistory)}`);
    t.diagnostic(`actual request batch sizes: ${JSON.stringify(requests.map(request => request.batch_size))}`);
    t.diagnostic(`actual final success message: ${dialogs[0].text}`);
});

test('select_all recalculates totalBatches to 3 when total grows from 20 to 30 before batch 2 fails', async (t) => {
    const bladePath = path.resolve(
        __dirname,
        '../../resources/views/admin/index.blade.php'
    );
    const bladeSource = fs.readFileSync(bladePath, 'utf8');
    const errorFactorySource = extractSource(
        bladeSource,
        'function createBulkDeleteBatchError',
        '    function updateDeleteProgress'
    );
    const batchProcessorSource = extractSource(
        bladeSource,
        'async function deleteSelectedInBatches',
        '    async function handleBulkDeleteFailure'
    );

    let requestCount = 0;

    const context = vm.createContext({
        currentDeleteFilters: () => ({}),
        showDeleteProgress: () => {},
        updateDeleteProgress: () => {},
        deleteBatch: async (_payload, options = {}) => {
            requestCount++;

            if (requestCount === 2) {
                throw new Error('Simulated HTTP 500 on batch 2');
            }

            return options.returnMeta
                ? { deleted: 10, total: 30 }
                : 10;
        },
        Swal: {
            fire: async () => {},
        },
        window: {
            location: {
                reload: () => {},
            },
        },
    });

    vm.runInContext(
        `${errorFactorySource}
${batchProcessorSource}
globalThis.runBulkDelete = deleteSelectedInBatches;`,
        context
    );

    let caughtError;
    try {
        await context.runBulkDelete([], 20, true);
    } catch (error) {
        caughtError = error;
    }

    const actualReport = JSON.parse(
        JSON.stringify(caughtError.bulkDeleteReport)
    );

    assert.deepEqual(actualReport, {
        deletedTotal: 10,
        failedBatchSize: 10,
        unprocessedTotal: 10,
        batchNumber: 2,
        totalBatches: 3,
        totalChangedSincePageLoad: true,
    });

    t.diagnostic(`actual upward-adjusted failure report: ${JSON.stringify(actualReport)}`);
});

test('select_all replaces a stale DOM total of 30 with the first server total of 25', async (t) => {
    const bladePath = path.resolve(
        __dirname,
        '../../resources/views/admin/index.blade.php'
    );
    const bladeSource = fs.readFileSync(bladePath, 'utf8');
    const errorFactorySource = extractSource(
        bladeSource,
        'function createBulkDeleteBatchError',
        '    function updateDeleteProgress'
    );
    const batchProcessorSource = extractSource(
        bladeSource,
        'async function deleteSelectedInBatches',
        '    async function handleBulkDeleteFailure'
    );

    let requestCount = 0;
    let reloadCount = 0;
    const requests = [];
    const progressUpdates = [];
    const successDialogs = [];

    const context = vm.createContext({
        currentDeleteFilters: () => ({ angkatan: '2020' }),
        showDeleteProgress: () => {},
        updateDeleteProgress: (done, total) => {
            progressUpdates.push([done, total]);
        },
        deleteBatch: async (payload, options = {}) => {
            requestCount++;
            requests.push(JSON.parse(JSON.stringify(payload)));

            if (options.returnMeta) {
                return { deleted: 10, total: 25 };
            }

            return requestCount === 2 ? 10 : 5;
        },
        Swal: {
            fire: async (options) => {
                successDialogs.push(JSON.parse(JSON.stringify(options)));
            },
        },
        window: {
            location: {
                reload: () => {
                    reloadCount++;
                },
            },
        },
    });

    vm.runInContext(
        `${errorFactorySource}
${batchProcessorSource}
globalThis.runBulkDelete = deleteSelectedInBatches;`,
        context
    );

    await context.runBulkDelete([], 30, true);

    assert.equal(requestCount, 3);
    assert.deepEqual(requests, [
        {
            angkatan: '2020',
            select_all: true,
            batch_size: 10,
            include_total: true,
        },
        {
            angkatan: '2020',
            select_all: true,
            batch_size: 10,
        },
        {
            angkatan: '2020',
            select_all: true,
            batch_size: 5,
        },
    ]);
    assert.deepEqual(progressUpdates, [
        [10, 25],
        [20, 25],
        [25, 25],
        [25, 25],
    ]);
    assert.equal(reloadCount, 1);
    assert.equal(successDialogs.length, 1);
    assert.equal(successDialogs[0].icon, 'success');
    assert.equal(successDialogs[0].text, 'Semua data terpilih berhasil dihapus');
    assert.doesNotMatch(successDialogs[0].text, /25|30|berubah/);

    t.diagnostic(`actual request batch sizes: ${JSON.stringify(requests.map(request => request.batch_size))}`);
    t.diagnostic(`actual progress totals: ${JSON.stringify(progressUpdates)}`);
    t.diagnostic(`actual request count: ${requestCount}`);
    t.diagnostic(`actual success message: ${successDialogs[0].text}`);
});

test('select_all failure reports the server-adjusted total and notes that the total changed', async (t) => {
    const bladePath = path.resolve(
        __dirname,
        '../../resources/views/admin/index.blade.php'
    );
    const bladeSource = fs.readFileSync(bladePath, 'utf8');
    const errorFactorySource = extractSource(
        bladeSource,
        'function createBulkDeleteBatchError',
        '    function updateDeleteProgress'
    );
    const batchProcessorSource = extractSource(
        bladeSource,
        'async function deleteSelectedInBatches',
        '    async function handleBulkDeleteFailure'
    );
    const failureHandlerSource = extractSource(
        bladeSource,
        'async function handleBulkDeleteFailure',
        '    deleteBtn.addEventListener'
    );

    let requestCount = 0;
    const dialogCalls = [];

    const context = vm.createContext({
        currentDeleteFilters: () => ({}),
        showDeleteProgress: () => {},
        updateDeleteProgress: () => {},
        deleteBatch: async (_payload, options = {}) => {
            requestCount++;

            if (requestCount === 2) {
                throw new Error('Simulated HTTP 500 on batch 2');
            }

            return options.returnMeta
                ? { deleted: 10, total: 25 }
                : 10;
        },
        Swal: {
            fire: async (options) => {
                dialogCalls.push(JSON.parse(JSON.stringify(options)));
            },
        },
        window: {
            location: {
                href: 'http://localhost/admin/alumni',
                reload: () => {},
            },
            alumniFetchAndRender: async () => {},
        },
    });

    vm.runInContext(
        `${errorFactorySource}
${batchProcessorSource}
${failureHandlerSource}
globalThis.runBulkDelete = deleteSelectedInBatches;
globalThis.handleFailure = handleBulkDeleteFailure;`,
        context
    );

    let caughtError;
    try {
        await context.runBulkDelete([], 30, true);
    } catch (error) {
        caughtError = error;
    }

    const actualReport = JSON.parse(
        JSON.stringify(caughtError.bulkDeleteReport)
    );

    assert.deepEqual(actualReport, {
        deletedTotal: 10,
        failedBatchSize: 10,
        unprocessedTotal: 5,
        batchNumber: 2,
        totalBatches: 3,
        totalChangedSincePageLoad: true,
    });

    await context.handleFailure(caughtError);

    assert.equal(dialogCalls.length, 1);
    assert.match(
        dialogCalls[0].text,
        /Catatan: jumlah data berubah sejak halaman terakhir dimuat\./
    );

    t.diagnostic(`actual adjusted failure report: ${JSON.stringify(actualReport)}`);
    t.diagnostic(`actual failure message: ${dialogCalls[0].text}`);
});

test('partial failure refreshes alumni data only after the error dialog is closed', async (t) => {
    const bladePath = path.resolve(
        __dirname,
        '../../resources/views/admin/index.blade.php'
    );
    const bladeSource = fs.readFileSync(bladePath, 'utf8');
    const errorFactorySource = extractSource(
        bladeSource,
        'function createBulkDeleteBatchError',
        '    function updateDeleteProgress'
    );
    const batchProcessorSource = extractSource(
        bladeSource,
        'async function deleteSelectedInBatches',
        '    async function handleBulkDeleteFailure'
    );
    const failureHandlerSource = extractSource(
        bladeSource,
        'async function handleBulkDeleteFailure',
        '    deleteBtn.addEventListener'
    );

    let requestCount = 0;
    let refreshCount = 0;
    let reloadCount = 0;
    let resolveErrorDialog;
    let databaseIds = Array.from({ length: 30 }, (_, index) => index + 1);
    let renderedIds = [...databaseIds];

    const errorDialogClosed = new Promise((resolve) => {
        resolveErrorDialog = resolve;
    });

    const context = vm.createContext({
        currentDeleteFilters: () => ({}),
        showDeleteProgress: () => {},
        updateDeleteProgress: () => {},
        deleteBatch: async (_payload, options = {}) => {
            requestCount++;

            if (requestCount === 2) {
                throw new Error('Simulated HTTP 500 on batch 2');
            }

            databaseIds = databaseIds.slice(10);
            return options.returnMeta
                ? { deleted: 10, total: 30 }
                : 10;
        },
        Swal: {
            fire: () => errorDialogClosed,
        },
        window: {
            location: {
                href: 'http://localhost/admin/alumni',
                reload: () => {
                    reloadCount++;
                },
            },
            alumniFetchAndRender: async () => {
                refreshCount++;
                renderedIds = [...databaseIds];
            },
        },
    });

    vm.runInContext(
        `${errorFactorySource}
${batchProcessorSource}
${failureHandlerSource}
globalThis.runBulkDelete = deleteSelectedInBatches;
globalThis.handleFailure = handleBulkDeleteFailure;`,
        context
    );

    let caughtError;
    try {
        await context.runBulkDelete([], 30, true);
    } catch (error) {
        caughtError = error;
    }

    const handlingPromise = context.handleFailure(caughtError);
    await new Promise((resolve) => setImmediate(resolve));

    assert.equal(refreshCount, 0, 'Data tidak boleh di-refresh saat dialog masih terbuka');
    assert.deepEqual(renderedIds, Array.from({ length: 30 }, (_, index) => index + 1));

    resolveErrorDialog();
    await handlingPromise;

    assert.equal(refreshCount, 1);
    assert.equal(reloadCount, 0);
    assert.deepEqual(
        renderedIds,
        Array.from({ length: 20 }, (_, index) => index + 11)
    );

    t.diagnostic(`rendered IDs after dialog close: ${JSON.stringify(renderedIds)}`);
    t.diagnostic(`AJAX refresh count after dialog close: ${refreshCount}`);
    t.diagnostic(`full reload count: ${reloadCount}`);
});

test('failure on batch 1 does not refresh when deletedTotal is zero', async (t) => {
    const bladePath = path.resolve(
        __dirname,
        '../../resources/views/admin/index.blade.php'
    );
    const bladeSource = fs.readFileSync(bladePath, 'utf8');
    const errorFactorySource = extractSource(
        bladeSource,
        'function createBulkDeleteBatchError',
        '    function updateDeleteProgress'
    );
    const batchProcessorSource = extractSource(
        bladeSource,
        'async function deleteSelectedInBatches',
        '    async function handleBulkDeleteFailure'
    );
    const failureHandlerSource = extractSource(
        bladeSource,
        'async function handleBulkDeleteFailure',
        '    deleteBtn.addEventListener'
    );

    let refreshCount = 0;
    let reloadCount = 0;

    const context = vm.createContext({
        currentDeleteFilters: () => ({}),
        showDeleteProgress: () => {},
        updateDeleteProgress: () => {},
        deleteBatch: async () => {
            throw new Error('Simulated HTTP 500 on batch 1');
        },
        Swal: {
            fire: async () => {},
        },
        window: {
            location: {
                href: 'http://localhost/admin/alumni',
                reload: () => {
                    reloadCount++;
                },
            },
            alumniFetchAndRender: async () => {
                refreshCount++;
            },
        },
    });

    vm.runInContext(
        `${errorFactorySource}
${batchProcessorSource}
${failureHandlerSource}
globalThis.runBulkDelete = deleteSelectedInBatches;
globalThis.handleFailure = handleBulkDeleteFailure;`,
        context
    );

    let caughtError;
    try {
        await context.runBulkDelete([], 30, true);
    } catch (error) {
        caughtError = error;
    }

    await context.handleFailure(caughtError);

    const actualReport = JSON.parse(
        JSON.stringify(caughtError.bulkDeleteReport)
    );

    assert.equal(actualReport.deletedTotal, 0);
    assert.equal(refreshCount, 0);
    assert.equal(reloadCount, 0);

    t.diagnostic(`actual batch-1 report: ${JSON.stringify(actualReport)}`);
    t.diagnostic(`AJAX refresh count: ${refreshCount}`);
    t.diagnostic(`full reload count: ${reloadCount}`);
});

test('refresh rejection shows a second warning and does not force a reload', async (t) => {
    const bladePath = path.resolve(
        __dirname,
        '../../resources/views/admin/index.blade.php'
    );
    const bladeSource = fs.readFileSync(bladePath, 'utf8');
    const failureHandlerSource = extractSource(
        bladeSource,
        'async function handleBulkDeleteFailure',
        '    deleteBtn.addEventListener'
    );

    const dialogCalls = [];
    let refreshCount = 0;
    let reloadCount = 0;
    let refreshOptions = null;

    const context = vm.createContext({
        Swal: {
            fire: async (options) => {
                dialogCalls.push(JSON.parse(JSON.stringify(options)));
            },
        },
        window: {
            location: {
                href: 'http://localhost/admin/alumni',
                reload: () => {
                    reloadCount++;
                },
            },
            alumniFetchAndRender: async (_url, options) => {
                refreshCount++;
                refreshOptions = JSON.parse(JSON.stringify(options));
                throw new Error('Simulated refresh network failure');
            },
        },
    });

    vm.runInContext(
        `${failureHandlerSource}
globalThis.handleFailure = handleBulkDeleteFailure;`,
        context
    );

    await context.handleFailure({
        bulkDeleteReport: {
            deletedTotal: 10,
            failedBatchSize: 10,
            unprocessedTotal: 10,
            batchNumber: 2,
            totalBatches: 3,
        },
    });

    assert.equal(refreshCount, 1);
    assert.deepEqual(refreshOptions, { throwOnError: true });
    assert.equal(dialogCalls.length, 2);
    assert.equal(dialogCalls[0].title, 'Bulk delete berhenti');
    assert.equal(dialogCalls[1].title, 'Tampilan data belum diperbarui');
    assert.equal(
        dialogCalls[1].text,
        'Gagal menyegarkan tampilan data secara otomatis. Sebagian data sudah terhapus dari database. Silakan refresh halaman secara manual untuk melihat data terbaru.'
    );
    assert.equal(reloadCount, 0);

    t.diagnostic(`dialog titles: ${JSON.stringify(dialogCalls.map(dialog => dialog.title))}`);
    t.diagnostic(`refresh options: ${JSON.stringify(refreshOptions)}`);
    t.diagnostic(`full reload count: ${reloadCount}`);
});

test('alumniFetchAndRender propagates a network error when throwOnError is enabled', async (t) => {
    const filterDataPath = path.resolve(
        __dirname,
        '../../public/js/admin/filter-data.js'
    );
    const filterDataSource = fs.readFileSync(filterDataPath, 'utf8');
    const fetchAndRenderSource = extractSource(
        filterDataSource,
        'async function fetchAndRender',
        '    function triggerClientSearch'
    );

    let assignCount = 0;
    const loadingStates = [];

    const context = vm.createContext({
        AbortController,
        fetch: async () => {
            throw new Error('Simulated fetch failure');
        },
        setLoading: (state) => {
            loadingStates.push(state);
        },
        window: {
            location: {
                assign: () => {
                    assignCount++;
                },
            },
        },
    });

    vm.runInContext(
        `let requestSeq = 0;
let activeController = null;
${fetchAndRenderSource}
globalThis.runFetchAndRender = fetchAndRender;`,
        context
    );

    await assert.rejects(
        context.runFetchAndRender('http://localhost/admin/alumni', {
            throwOnError: true,
        }),
        /Simulated fetch failure/
    );

    assert.deepEqual(loadingStates, [true, false]);
    assert.equal(assignCount, 0);

    t.diagnostic(`loading states: ${JSON.stringify(loadingStates)}`);
    t.diagnostic(`location.assign count: ${assignCount}`);
});
