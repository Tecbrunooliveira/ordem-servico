const STORAGE_KEY = 'notification-center-prefs';
const ALERTED_KEY = 'notification-center-alerted';

const DEFAULT_PREFS = {
    sound: true,
    push: true,
};

const TYPE_PRIORITY = {
    danger: 4,
    warning: 3,
    info: 2,
    success: 1,
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

function pickAlertType(current, next) {
    const currentScore = TYPE_PRIORITY[current] ?? 0;
    const nextScore = TYPE_PRIORITY[next] ?? 0;

    return nextScore > currentScore ? next : current;
}

let audioContext = null;

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

const alarmState = {
    active: false,
    type: 'info',
    cycleId: 0,
    timeoutId: null,
};

function dispatchAlarmEvent(name) {
    window.dispatchEvent(new CustomEvent(name));
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

const TONE_SEQUENCES = {
    warning: [440, 330, 440],
    info: [523, 659],
    success: [659, 784],
    danger: [330, 220, 330],
};

async function playSequence(type = 'info') {
    const sequence = TONE_SEQUENCES[type] || TONE_SEQUENCES.info;

    for (const [index, frequency] of sequence.entries()) {
        await new Promise((resolve) => {
            setTimeout(async () => {
                await playTone(frequency, 0.14, 0.1);
                resolve();
            }, index * 160);
        });
    }
}

function scheduleAlarmCycle(cycleId) {
    alarmState.timeoutId = setTimeout(async () => {
        if (! alarmState.active || cycleId !== alarmState.cycleId) {
            return;
        }

        await playSequence(alarmState.type);

        if (! alarmState.active || cycleId !== alarmState.cycleId) {
            return;
        }

        scheduleAlarmCycle(cycleId);
    }, 700);
}

function stopAlarm() {
    if (! alarmState.active && ! alarmState.timeoutId) {
        return;
    }

    alarmState.active = false;
    alarmState.cycleId += 1;

    if (alarmState.timeoutId) {
        clearTimeout(alarmState.timeoutId);
        alarmState.timeoutId = null;
    }

    dispatchAlarmEvent('notification-alarm-stopped');
}

async function startAlarm(type = 'info') {
    if (! readPrefs().sound) {
        return;
    }

    await unlockAudio();

    const wasActive = alarmState.active;
    alarmState.type = pickAlertType(alarmState.type, type);
    alarmState.active = true;

    if (wasActive) {
        return;
    }

    alarmState.cycleId += 1;
    const cycleId = alarmState.cycleId;

    dispatchAlarmEvent('notification-alarm-started');

    await playSequence(alarmState.type);

    if (! alarmState.active || cycleId !== alarmState.cycleId) {
        return;
    }

    scheduleAlarmCycle(cycleId);
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

    isAlarmActive() {
        return alarmState.active;
    },

    silenceAlarm() {
        stopAlarm();
    },

    toggleSound() {
        const prefs = readPrefs();
        prefs.sound = ! prefs.sound;
        writePrefs(prefs);

        if (! prefs.sound) {
            stopAlarm();
        } else {
            unlockAudio().then(() => startAlarm('info'));
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
        await startAlarm(type);
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
            stopAlarm();

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

        const alertType = fresh.reduce(
            (best, notification) => pickAlertType(best, notification.type || 'info'),
            'info',
        );

        startAlarm(alertType);

        fresh.forEach((notification) => {
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
            type: 'warning',
            title: 'Teste de notificação',
            message: 'O alarme tocará em loop até você silenciar.',
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
