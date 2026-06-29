import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import ptBrLocale from '@fullcalendar/core/locales/pt-br';
import Sortable from 'sortablejs';

window.Sortable = Sortable;

document.addEventListener('alpine:init', () => {
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
                    const url = new URL(window.location.origin + '/ordens-servico');
                    url.searchParams.set('data', info.dateStr);
                    window.location.href = url.toString();
                },
                select: (info) => {
                    const url = new URL(window.location.origin + '/ordens-servico');
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
