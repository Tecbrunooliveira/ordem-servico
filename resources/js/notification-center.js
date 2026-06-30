const STORAGE_KEY = 'notification-center-prefs';
const ALERTED_KEY = 'notification-center-alerted';

const DEFAULT_PREFS = {
    sound: true,
    push: true,
};

function readPrefs() {
    try {
        return { ...DEFAULT_PREFS, ...JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}') };
    } catch {
        return { ...DEFAULT_PREFS };
    }
}

function writePrefs(prefs) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ ...readPrefs(), ...prefs }));
}

function readAlertedIds() {
    try {
        return JSON.parse(sessionStorage.getItem(ALERTED_KEY) || '[]');
    } catch {
        return [];
    }
}

function markAlertedIds(ids) {
    const merged = [...new Set([...readAlertedIds(), ...ids])];

    sessionStorage.setItem(ALERTED_KEY, JSON.stringify(merged));
}

let audioContext = null;
let audioUnlocked = false;

function getAudioContext() {
    if (! audioContext) {
        const Ctx = window.AudioContext || window.webkitAudioContext;

        if (! Ctx) {
            return null;
        }

        audioContext = new Ctx();
    }

    return audioContext;
}

async function unlockAudio() {
    const ctx = getAudioContext();

    if (! ctx) {
        return false;
    }

    if (ctx.state === 'suspended') {
        try {
            await ctx.resume();
        } catch {
            return false;
        }
    }

    audioUnlocked = true;

    return true;
}

async function playTone(frequency, duration = 0.14, volume = 0.12) {
    const ctx = getAudioContext();

    if (! ctx) {
        return;
    }

    if (ctx.state === 'suspended') {
        await unlockAudio();
    }

    const oscillator = ctx.createOscillator();
    const gain = ctx.createGain();

    oscillator.type = 'sine';
    oscillator.frequency.value = frequency;
    gain.gain.value = volume;

    oscillator.connect(gain);
    gain.connect(ctx.destination);

    const now = ctx.currentTime;
    gain.gain.setValueAtTime(volume, now);
    gain.gain.exponentialRampToValueAtTime(0.001, now + duration);

    oscillator.start(now);
    oscillator.stop(now + duration);
}

function extractItemsFromEvent(detail) {
    if (Array.isArray(detail)) {
        if (detail[0]?.items) {
            return detail[0].items;
        }

        if (detail[0]?.title) {
            return detail;
        }

        return detail.items ?? [];
    }

    if (detail?.items) {
        return detail.items;
    }

    return [];
}

export const NotificationCenterUtil = {
    prefs() {
        return readPrefs();
    },

    setPrefs(prefs) {
        writePrefs(prefs);
    },

    async unlockAudio() {
        return unlockAudio();
    },

    toggleSound() {
        const prefs = readPrefs();
        prefs.sound = ! prefs.sound;
        writePrefs(prefs);

        if (prefs.sound) {
            unlockAudio().then(() => this.playSound('info'));
        }

        return prefs.sound;
    },

    async togglePush() {
        const prefs = readPrefs();
        prefs.push = ! prefs.push;
        writePrefs(prefs);

        if (prefs.push) {
            await this.requestPushPermission();
        }

        return prefs.push;
    },

    async requestPushPermission() {
        if (! ('Notification' in window)) {
            return 'unsupported';
        }

        if (Notification.permission === 'granted') {
            return 'granted';
        }

        if (Notification.permission === 'denied') {
            return 'denied';
        }

        return Notification.requestPermission();
    },

    pushPermission() {
        if (! ('Notification' in window)) {
            return 'unsupported';
        }

        return Notification.permission;
    },

    async playSound(type = 'info') {
        if (! readPrefs().sound) {
            return;
        }

        await unlockAudio();

        const tones = {
            warning: [440, 330],
            info: [523, 659],
            success: [659, 784],
            danger: [330, 220],
        };

        const sequence = tones[type] || tones.info;

        for (const [index, frequency] of sequence.entries()) {
            await new Promise((resolve) => {
                setTimeout(async () => {
                    await playTone(frequency, 0.12, 0.1);
                    resolve();
                }, index * 140);
            });
        }
    },

    showPush(notification) {
        if (! readPrefs().push) {
            return false;
        }

        if (! ('Notification' in window) || Notification.permission !== 'granted') {
            return false;
        }

        const instance = new Notification(notification.title, {
            body: notification.message,
            tag: notification.id,
            icon: document.querySelector('link[rel="icon"]')?.href || undefined,
        });

        instance.onclick = () => {
            window.focus();

            if (notification.url) {
                window.location.href = notification.url;
            }

            instance.close();
        };

        return true;
    },

    notifyWireui(notification) {
        const payload = {
            options: {
                title: notification.title,
                description: notification.message,
                timeout: 5000,
                icon: notification.type === 'warning'
                    ? 'warning'
                    : (notification.type === 'success' ? 'success' : 'info'),
            },
        };

        if (window.Livewire?.dispatch) {
            window.Livewire.dispatch('wireui:notification', payload);

            return;
        }

        window.dispatchEvent(new CustomEvent('wireui:notification', {
            bubbles: true,
            detail: payload,
        }));
    },

    handleNew(items = [], { force = false } = {}) {
        if (! Array.isArray(items) || items.length === 0) {
            return;
        }

        const alerted = new Set(readAlertedIds());
        const fresh = force
            ? items
            : items.filter((notification) => notification?.id && ! alerted.has(notification.id));

        if (fresh.length === 0) {
            return;
        }

        fresh.forEach((notification) => {
            this.playSound(notification.type || 'info');
            this.showPush(notification);
            this.notifyWireui(notification);
        });

        markAlertedIds(fresh.map((notification) => notification.id));
    },

    handleEvent(detail, options = {}) {
        this.handleNew(extractItemsFromEvent(detail), options);
    },

    async testAlert() {
        await unlockAudio();
        await this.requestPushPermission();

        const sample = {
            id: `teste-${Date.now()}`,
            type: 'info',
            title: 'Teste de notificação',
            message: 'Som, push e toast estão funcionando.',
            url: window.location.href,
        };

        this.handleNew([sample], { force: true });
    },

    bindWindowEvents() {
        window.addEventListener('notificacoes-novas', (event) => {
            this.handleEvent(event.detail);
        });
    },

    init() {
        this.bindWindowEvents();

        ['click', 'keydown', 'touchstart'].forEach((eventName) => {
            document.addEventListener(eventName, () => {
                unlockAudio();
            }, { once: true, passive: true });
        });
    },
};
