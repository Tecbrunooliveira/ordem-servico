import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import ptBrLocale from '@fullcalendar/core/locales/pt-br';
import Sortable from 'sortablejs';

window.Sortable = Sortable;

const appBaseUrl = document.querySelector('meta[name="app-url"]')?.content?.replace(/\/$/, '') ?? '';
const wireuiIconBase = document.querySelector('meta[name="wireui-icons-base"]')?.content
    ?? `${appBaseUrl}/wireui/icons/outline/`;

function rewriteWireuiUrl(url) {
    if (! appBaseUrl || typeof url !== 'string') {
        return url;
    }

    if (url.startsWith('/wireui/')) {
        return `${appBaseUrl}${url}`;
    }

    try {
        const parsed = new URL(url, window.location.origin);

        if (parsed.pathname.startsWith('/wireui/') && ! parsed.pathname.startsWith(`${new URL(appBaseUrl).pathname}/wireui/`)) {
            return `${appBaseUrl}${parsed.pathname}${parsed.search}${parsed.hash}`;
        }
    } catch {
        return url;
    }

    return url;
}

if (appBaseUrl) {
    const nativeFetch = window.fetch.bind(window);

    window.fetch = (input, init) => {
        if (typeof input === 'string') {
            return nativeFetch(rewriteWireuiUrl(input), init);
        }

        if (input instanceof Request) {
            const nextUrl = rewriteWireuiUrl(input.url);

            if (nextUrl !== input.url) {
                input = new Request(nextUrl, input);
            }
        }

        return nativeFetch(input, init);
    };
}

document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status, content, preventDefault }) => {
            const html = typeof content === 'string' ? content : '';
            const isHostinger404 = status === 404
                && (html.includes('This Page Does Not Exist') || html.includes('GoogleAnalyticsObject'));

            if (isHostinger404) {
                preventDefault();
            }
        });
    });
});

document.addEventListener('alpine:init', () => {
    Alpine.data('ordemCronometro', ({ baseSeconds, running, startedAt }) => ({
        baseSeconds,
        running,
        startedAt,
        display: '00:00:00',
        intervalId: null,

        init() {
            this.tick();
            this.intervalId = setInterval(() => this.tick(), 1000);
        },

        destroy() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
            }
        },

        tick() {
            let total = this.baseSeconds;

            if (this.running && this.startedAt) {
                total += Math.floor(Date.now() / 1000) - this.startedAt;
            }

            this.display = this.formatSeconds(total);
        },

        formatSeconds(total) {
            const horas = Math.floor(total / 3600);
            const minutos = Math.floor((total % 3600) / 60);
            const segundos = total % 60;

            return [horas, minutos, segundos]
                .map((parte) => String(parte).padStart(2, '0'))
                .join(':');
        },
    }));

    Alpine.data('tableRowMenu', () => ({
        open: false,
        menuStyle: '',

        toggle() {
            this.open = ! this.open;

            if (this.open) {
                this.$nextTick(() => this.positionMenu());
            }
        },

        close() {
            this.open = false;
        },

        positionMenu() {
            const trigger = this.$refs.trigger;
            const menu = this.$refs.menu;

            if (! trigger || ! menu) {
                return;
            }

            const rect = trigger.getBoundingClientRect();
            const menuHeight = menu.offsetHeight || 260;
            const menuWidth = menu.offsetWidth || 224;
            const gap = 6;
            const padding = 8;

            let top = rect.bottom + gap;

            if (top + menuHeight > window.innerHeight - padding) {
                top = rect.top - menuHeight - gap;
            }

            top = Math.max(padding, Math.min(top, window.innerHeight - menuHeight - padding));

            let left = rect.right - menuWidth;
            left = Math.max(padding, Math.min(left, window.innerWidth - menuWidth - padding));

            this.menuStyle = `position:fixed;top:${top}px;left:${left}px;z-index:70;`;
        },
    }));

    Alpine.data('agendaCalendar', (initialEvents) => ({
        calendar: null,
        events: initialEvents,
        resizeObserver: null,

        init() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.buildCalendar();

                    requestAnimationFrame(() => {
                        this.calendar?.updateSize();
                    });
                });
            });

            this.resizeObserver = new ResizeObserver(() => {
                this.calendar?.updateSize();
            });

            this.resizeObserver.observe(this.$el);

            window.addEventListener('load', () => {
                this.calendar?.updateSize();
            });
        },

        buildCalendar() {
            if (this.calendar) {
                return;
            }

            this.calendar = new Calendar(this.$refs.calendar, {
                plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
                locale: ptBrLocale,
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay',
                },
                height: 'auto',
                expandRows: true,
                editable: true,
                selectable: true,
                dayMaxEvents: 3,
                events: this.events,
                eventClick: (info) => {
                    this.$wire.showEvent(parseInt(info.event.id, 10));
                },
                eventDrop: async (info) => {
                    const date = info.event.startStr.split('T')[0];

                    try {
                        await this.$wire.reschedule(parseInt(info.event.id, 10), date);
                    } catch {
                        info.revert();
                    }
                },
                dateClick: (info) => {
                    const appBase = document.querySelector('meta[name="app-url"]')?.content
                        ?? window.location.origin;
                    const url = new URL(`${appBase.replace(/\/$/, '')}/ordens-servico`);
                    url.searchParams.set('data', info.dateStr);
                    window.location.href = url.toString();
                },
                select: (info) => {
                    const appBase = document.querySelector('meta[name="app-url"]')?.content
                        ?? window.location.origin;
                    const url = new URL(`${appBase.replace(/\/$/, '')}/ordens-servico`);
                    url.searchParams.set('data', info.startStr.split('T')[0]);
                    window.location.href = url.toString();
                },
                buttonText: {
                    today: 'Hoje',
                    month: 'Mês',
                    week: 'Semana',
                    day: 'Dia',
                },
            });

            this.calendar.render();
        },

        destroy() {
            this.resizeObserver?.disconnect();
            this.calendar?.destroy();
        },

        async refreshEvents() {
            this.events = await this.$wire.events();
            this.calendar.removeAllEvents();
            this.calendar.addEventSource(this.events);
            this.calendar.updateSize();
        },
    }));

    Alpine.data('tarefasKanban', () => ({
        sortables: [],

        init() {
            this.$nextTick(() => this.mountSortable());
        },

        mountSortable() {
            this.destroySortables();

            this.$el.querySelectorAll('[data-kanban-column]').forEach((column) => {
                this.sortables.push(
                    Sortable.create(column, {
                        group: 'tarefas',
                        animation: 150,
                        draggable: '.kanban-card',
                        filter: 'button, a, input, label',
                        preventOnFilter: false,
                        ghostClass: 'kanban-card-ghost',
                        onEnd: (evt) => {
                            const taskId = parseInt(evt.item.dataset.taskId, 10);
                            const status = evt.to.dataset.status;

                            if (taskId && status && evt.from !== evt.to) {
                                this.$wire.updateStatus(taskId, status);
                            }
                        },
                    }),
                );
            });
        },

        refresh() {
            this.$nextTick(() => this.mountSortable());
        },

        destroySortables() {
            this.sortables.forEach((instance) => instance.destroy());
            this.sortables = [];
        },

        destroy() {
            this.destroySortables();
        },
    }));

    Alpine.data('tarefasCalendar', (initialEvents) => ({
        calendar: null,
        events: initialEvents,
        resizeObserver: null,

        init() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.buildCalendar();

                    requestAnimationFrame(() => {
                        this.calendar?.updateSize();
                    });
                });
            });

            this.resizeObserver = new ResizeObserver(() => {
                this.calendar?.updateSize();
            });

            this.resizeObserver.observe(this.$el);
        },

        buildCalendar() {
            if (this.calendar) {
                return;
            }

            this.calendar = new Calendar(this.$refs.calendar, {
                plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
                locale: ptBrLocale,
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay',
                },
                height: 'auto',
                expandRows: true,
                editable: true,
                dayMaxEvents: 4,
                events: this.events,
                eventClick: (info) => {
                    this.$wire.edit(parseInt(info.event.id, 10));
                },
                eventDrop: async (info) => {
                    const date = info.event.startStr.split('T')[0];

                    try {
                        await this.$wire.updateVencimento(parseInt(info.event.id, 10), date);
                    } catch {
                        info.revert();
                    }
                },
                dateClick: (info) => {
                    this.$wire.createWithDate(info.dateStr);
                },
                buttonText: {
                    today: 'Hoje',
                    month: 'Mês',
                    week: 'Semana',
                    day: 'Dia',
                },
            });

            this.calendar.render();
        },

        async refreshEvents() {
            this.events = await this.$wire.calendarEvents();
            this.calendar.removeAllEvents();
            this.calendar.addEventSource(this.events);
            this.calendar.updateSize();
        },

        destroy() {
            this.resizeObserver?.disconnect();
            this.calendar?.destroy();
        },
    }));

    Alpine.data('richTextEditor', (content) => ({
        content,

        init() {
            this.$nextTick(() => {
                if (this.$refs.editor) {
                    this.$refs.editor.innerHTML = this.content || '';
                }
            });

            this.$watch('content', (value) => {
                if (! this.$refs.editor) {
                    return;
                }

                const normalized = value || '';

                if (this.$refs.editor.innerHTML !== normalized) {
                    this.$refs.editor.innerHTML = normalized;
                }
            });
        },

        sync() {
            if (! this.$refs.editor) {
                return;
            }

            const html = this.$refs.editor.innerHTML;
            this.content = html === '<br>' ? '' : html;
        },

        exec(command) {
            if (! this.$refs.editor) {
                return;
            }

            this.$refs.editor.focus();
            document.execCommand(command, false, null);
            this.sync();
        },
    }));
});
