(function () {
    'use strict';
    if (window.sxAgentIndexLoaded) return;
    window.sxAgentIndexLoaded = true;
    document.addEventListener('sx:job-status', function (event) {
        const cell = event.target.closest('[data-sx-agent-job]');
        const row = cell && cell.closest('tr');
        if (!row) return;
        const run = event.detail && event.detail.run;
        const state = run && ({
            failed: 'danger', timed_out: 'danger',
            succeeded_with_warnings: 'warning', succeeded: 'success'
        })[run.status];
        ['danger', 'warning', 'success'].forEach(function (value) {
            row.classList.toggle('sx-collection-item--' + value, state === value);
        });
    });
})();
