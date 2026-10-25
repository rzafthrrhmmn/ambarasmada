/**
 * Offline Form Submission Queue
 * Handles queuing form submissions when offline and syncing when online
 */

const QUEUE_DB_NAME = 'offline-forms-queue';
const QUEUE_DB_VERSION = 1;
const QUEUE_STORE_NAME = 'submissions';
const SYNC_TAG = 'sync-offline-submissions';

// Open IndexedDB for offline queue
function openQueueDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(QUEUE_DB_NAME, QUEUE_DB_VERSION);
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(QUEUE_STORE_NAME)) {
                const store = db.createObjectStore(QUEUE_STORE_NAME, { keyPath: 'id', autoIncrement: true });
                store.createIndex('status', 'status', { unique: false });
                store.createIndex('createdAt', 'createdAt', { unique: false });
                store.createIndex('tag', 'tag', { unique: false });
            }
        };
        request.onsuccess = (event) => resolve(event.target.result);
        request.onerror = (e) => reject(e.target.error);
    });
}

// Queue a form submission
export async function queueSubmission(submission) {
    try {
        const db = await openQueueDB();
        const tx = db.transaction(QUEUE_STORE_NAME, 'readwrite');
        const store = tx.objectStore(QUEUE_STORE_NAME);
        
        const item = {
            ...submission,
            status: 'pending',
            createdAt: Date.now(),
            tag: SYNC_TAG
        };
        
        return new Promise((resolve, reject) => {
            const request = store.add(item);
            request.onsuccess = () => {
                db.close();
                resolve(request.result);
                registerSync();
                if (navigator.onLine) {
                    flushQueue();
                }
            };
            request.onerror = () => {
                db.close();
                reject(request.error);
            };
        });
    } catch (error) {
        console.error('Failed to queue submission:', error);
        throw error;
    }
}

// Register background sync
export async function registerSync() {
    if (!('serviceWorker' in navigator) || !('SyncManager' in window)) {
        return false;
    }
    
    try {
        const registration = await navigator.serviceWorker.ready;
        if ('sync' in registration) {
            await registration.sync.register(SYNC_TAG);
            return true;
        }
    } catch (error) {
        console.error('Failed to register sync:', error);
    }
    return false;
}

// Flush queue via service worker message
export async function flushQueue() {
    if (!('serviceWorker' in navigator)) return;
    
    try {
        const registration = await navigator.serviceWorker.ready;
        if (registration.active) {
            registration.active.postMessage({
                type: 'FLUSH_OFFLINE_QUEUE'
            });
        }
    } catch (error) {
        console.error('Failed to flush queue:', error);
    }
}

// Get pending count
export async function getPendingCount() {
    try {
        const db = await openQueueDB();
        const tx = db.transaction(QUEUE_STORE_NAME, 'readonly');
        const store = tx.objectStore(QUEUE_STORE_NAME);
        const request = store.getAll();
        
        return new Promise((resolve, reject) => {
            request.onsuccess = () => {
                db.close();
                const results = request.result || [];
                resolve(results.filter(item => item.status === 'pending').length);
            };
            request.onerror = () => {
                db.close();
                reject(request.error);
            };
        });
    } catch (error) {
        console.error('Failed to get pending count:', error);
        return 0;
    }
}

// Check if offline submission is possible
export function isOfflineSubmissionSupported() {
    return 'serviceWorker' in navigator && 'indexedDB' in window;
}

// Auto-flush on online — ditangani oleh useNetworkStatus.js sebagai single source of truth.
// Listener di sini dihapus untuk menghindari duplikasi dengan useNetworkStatus.initialise().

export default {
    queueSubmission,
    registerSync,
    flushQueue,
    getPendingCount,
    isOfflineSubmissionSupported
};
