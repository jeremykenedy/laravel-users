import assert from 'node:assert/strict';
import { test } from 'node:test';
import { createPackageOperationTracker, createPackageRequirementsTracker } from '../package-operation.js';

function fixture(t) {
    const previous = globalThis.window;
    globalThis.window = { location: { href: 'https://example.test/users/settings' } };
    t.after(() => { globalThis.window = previous; });
    t.mock.timers.enable({ apis: ['setTimeout'] });
    return { id: 'operation-id', status_url: 'https://example.test/users/settings/packages/operation-id', status: 'queued', stage: 'queue', package: 'toast', operation: 'install', message: 'Waiting for the worker.', queued_at: '2026-10-08T21:00:00Z' };
}

test('a trusted pending operation resumes on mount and remains available through completed status', async t => {
    const initial = fixture(t);
    const changes = [];
    let calls = 0;
    t.mock.method(globalThis, 'fetch', async (url, options) => {
        assert.equal(url.href, initial.status_url);
        assert.equal(options.headers['X-LaravelUsers-Runtime'], 'react');
        assert.equal(options.headers['X-CSRF-TOKEN'], 'csrf');
        calls++;
        return Response.json({ ...initial, status: calls === 1 ? 'running' : 'completed', stage: calls === 1 ? 'composer' : 'completed', message: calls === 1 ? 'Installing dependency.' : 'Installed successfully.' });
    });
    const tracker = createPackageOperationTracker('react', () => 'csrf', initial, value => changes.push(value));
    t.after(() => tracker.stop());
    assert.equal(changes[0].status, 'queued');
    await tracker.poll();
    assert.equal(tracker.current().status, 'running');
    await tracker.poll();
    assert.equal(tracker.current().status, 'completed');
    assert.equal(tracker.current().message, 'Installed successfully.');
    t.mock.timers.tick(4000);
    assert.equal(calls, 2);
});

test('temporary status failures preserve the job and permit retry while forbidden status clears it', async t => {
    const initial = fixture(t);
    let calls = 0;
    t.mock.method(globalThis, 'fetch', async () => {
        calls++;
        if (calls === 1) throw new Error('Temporary network failure.');
        return Response.json({ message: 'Forbidden.' }, { status: 403 });
    });
    const changes = [];
    const tracker = createPackageOperationTracker('vue', () => 'csrf', initial, value => changes.push(value));
    t.after(() => tracker.stop());
    await tracker.poll();
    assert.equal(tracker.current().id, initial.id);
    assert.equal(tracker.current().status, 'queued');
    assert.equal(tracker.current().transport_error, 'Temporary network failure.');
    await tracker.poll();
    assert.equal(tracker.current(), null);
    assert.equal(changes.at(-1), null);
});

test('requirements verification resumes a pending worker check and uses the existing authenticated form', async t => {
    fixture(t);
    const form = { action: 'https://example.test/users/settings/packages/verify', method: 'POST', values: { package: 'requirements', operation: 'verify' }, fields: [{ key: 'package', name: 'package', type: 'hidden' }, { key: 'operation', name: 'operation', type: 'hidden' }] };
    const changes = [];
    let calls = 0;
    t.mock.method(globalThis, 'fetch', async (url, options) => {
        assert.equal(url.href, form.action);
        assert.equal(options.method, 'POST');
        assert.equal(options.headers['X-CSRF-TOKEN'], 'csrf');
        assert.equal(options.body.get('_token'), 'csrf');
        assert.equal(options.body.get('package'), 'requirements');
        assert.equal(options.body.get('operation'), 'verify');
        calls++;
        if (calls === 1) throw new Error('Temporary network failure.');
        return Response.json({ status: 'completed', queue_ready: true, message: 'Worker verified.' });
    });
    const tracker = createPackageRequirementsTracker('svelte', () => 'csrf', form, { status: 'checking', queue_ready: false, message: 'Waiting for the worker.' }, value => changes.push(value));
    t.after(() => tracker.stop());
    await tracker.verify();
    assert.equal(tracker.current().status, 'checking');
    assert.equal(tracker.current().transport_error, 'Temporary network failure.');
    await tracker.verify();
    assert.equal(tracker.current().queue_ready, true);
    assert.equal(tracker.current().transport_error, undefined);
    assert.equal(changes.at(-1).message, 'Worker verified.');
    t.mock.timers.tick(4000);
    assert.equal(calls, 2);
});
