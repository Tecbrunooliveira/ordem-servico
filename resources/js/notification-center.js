const STORAGE_KEY = 'notification-center-prefs';

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

function playTone(frequency, duration = 0.12, volume = 0.08) {
    const ctx = getAudioContext();

    if (! ctx) {
        return;
    }

    if (ctx.state === 'suspended') {
        ctx.resume().catch(() => {});
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

export const NotificationCenterUtil = {
    prefs() {
        return readPrefs();
    },

    setPrefs(prefs) {
        writePrefs(prefs);
    },

    toggleSound() {
        const prefs = readPrefs();
        prefs.sound = ! prefs.sound;
        writePrefs(prefs);

        if (prefs.sound) {
            this.playSound('info');
        }

        return prefs.sound;
    },

    togglePush() {
        const prefs = readPrefs();
        prefs.push = ! prefs.push;
        writePrefs(prefs);

        if (prefs.push) {
            this.requestPushPermission();
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

    playSound(type = 'info') {
        if (! readPrefs().sound) {
            return;
        }

        const tones = {
            warning: [440, 330],
            info: [523, 659],
            success: [659, 784],
            danger: [330, 220],
        };

        const sequence = tones[type] || tones.info;

        sequence.forEach((frequency, index) => {
            setTimeout(() => playTone(frequency, 0.1, 0.07), index * 130);
        });
    },

    showPush(notification) {
        if (! readPrefs().push) {
            return;
        }

        if (! ('Notification' in window) || Notification.permission !== 'granted') {
            return;
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

    handleNew(items = []) {
        if (! Array.isArray(items) || items.length === 0) {
            return;
        }

        items.forEach((notification) => {
            this.playSound(notification.type || 'info');
            this.showPush(notification);
            this.notifyWireui(notification);
        });
    },

    init() {
        document.addEventListener('livewire:init', () => {
            Livewire.on('notificacoes-novas', (payload) => {
                const items = payload?.items ?? payload?.[0]?.items ?? [];

                this.handleNew(items);
            });
        });

        document.addEventListener('click', () => {
            getAudioContext()?.resume?.().catch(() => {});
        }, { once: true });
    },
};
