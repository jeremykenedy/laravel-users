const { spawn } = require('node:child_process');

async function startPackageWorker(port) {
    const worker = spawn('php', ['tests/browser/package-worker.php', String(port)], { stdio: ['ignore', 'pipe', 'pipe'] });
    try {
        await new Promise((resolve, reject) => {
            let output = '';
            const timeout = setTimeout(() => reject(new Error(`Package worker did not start. ${output}`)), 30000);
            const fail = error => { clearTimeout(timeout); reject(error); };
            worker.stdout.on('data', data => {
                output = (output + data.toString()).slice(-8000);
                if (output.includes('Package worker ready.')) { clearTimeout(timeout); resolve(); }
            });
            worker.stderr.on('data', data => { output = (output + data.toString()).slice(-8000); });
            worker.once('error', fail);
            worker.once('exit', code => fail(new Error(`Package worker exited (${code}). ${output}`)));
        });
        return worker;
    } catch (error) {
        worker.kill('SIGTERM');
        throw error;
    }
}

module.exports = { startPackageWorker };
