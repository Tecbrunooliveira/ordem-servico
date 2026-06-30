import { NotificationCenterUtil } from './notification-center';

window.NotificationCenterUtil = NotificationCenterUtil;
NotificationCenterUtil.init();

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
    Alpine.data('notificationCenterPanel', () => ({
        open: false,
        soundEnabled: NotificationCenterUtil.prefs().sound,
        pushEnabled: NotificationCenterUtil.prefs().push,
        pushPermission: NotificationCenterUtil.pushPermission(),
        alarmRinging: NotificationCenterUtil.isAlarmActive(),
        notificationCenter: NotificationCenterUtil,

        init() {
            window.addEventListener('notification-alarm-started', () => {
                this.alarmRinging = true;
            });

            window.addEventListener('notification-alarm-stopped', () => {
                this.alarmRinging = false;
            });
        },

        silenceAlarm() {
            this.notificationCenter.silenceAlarm();
            this.alarmRinging = false;
        },

        toggle() {
            const next = ! this.open;
            this.open = next;

            this.notificationCenter.unlockAudio();

            if (next) {
                if (this.pushPermission === 'default') {
                    this.requestPush();
                }

                this.$wire.abrirPainel();
            }
        },

        async togglePush() {
            this.pushEnabled = NotificationCenterUtil.togglePush();
            this.pushPermission = NotificationCenterUtil.pushPermission();
        },

        async requestPush() {
            this.pushPermission = await NotificationCenterUtil.requestPushPermission();
            this.pushEnabled = NotificationCenterUtil.prefs().push;
        },
    }));

    Alpine.data('cnpjLookupField', ({ wireModel, applyMethod, variant = 'cliente', autoFetch = true }) => ({
        buscandoCnpj: false,
        ultimoCnpjConsultado: '',
        cnpjTimer: null,
        wireModel,
        applyMethod,
        variant,
        autoFetch,

        formatCnpj(digits) {
            digits = (digits || '').replace(/\D/g, '');

            if (digits.length !== 14) {
                return digits;
            }

            return digits.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
        },

        formatCep(cep) {
            cep = (cep || '').replace(/\D/g, '');

            if (cep.length !== 8) {
                return cep;
            }

            return cep.replace(/^(\d{5})(\d{3})$/, '$1-$2');
        },

        formatPhone(phone) {
            if (! phone) {
                return '';
            }

            const area = String(phone.area || '').replace(/\D/g, '');
            const number = String(phone.number || '').replace(/\D/g, '');

            if (! area || ! number) {
                return '';
            }

            if (number.length === 9) {
                return `(${area}) ${number.slice(0, 5)}-${number.slice(5)}`;
            }

            if (number.length === 8) {
                return `(${area}) ${number.slice(0, 4)}-${number.slice(4)}`;
            }

            return `(${area}) ${number}`;
        },

        mapClienteResponse(data) {
            const address = data?.address || {};
            const company = data?.company || {};
            const phones = Array.isArray(data?.phones) ? data.phones : [];
            const emails = Array.isArray(data?.emails) ? data.emails : [];
            const digits = String(data?.taxId || '').replace(/\D/g, '');

            return {
                nome: String(company.name || data?.alias || ''),
                documento: this.formatCnpj(digits),
                email: String(emails[0]?.address || ''),
                telefone: this.formatPhone(phones[0]),
                cidade: String(address.city || ''),
                estado: String(address.state || ''),
                rua: String(address.street || ''),
                numero: String(address.number || ''),
                bairro: String(address.district || ''),
            };
        },

        mapEmpresaResponse(data) {
            const address = data?.address || {};
            const company = data?.company || {};
            const phones = Array.isArray(data?.phones) ? data.phones : [];
            const emails = Array.isArray(data?.emails) ? data.emails : [];
            const digits = String(data?.taxId || '').replace(/\D/g, '');
            const rua = String(address.street || '');
            const numero = String(address.number || '');
            const bairro = String(address.district || '');

            const endereco = [rua, numero ? `nº ${numero}` : '', bairro].filter(Boolean).join(', ');

            return {
                cnpj: this.formatCnpj(digits),
                razao_social: String(company.name || data?.alias || ''),
                nome_empresa: String(data?.alias || company.name || ''),
                email: String(emails[0]?.address || ''),
                telefone: this.formatPhone(phones[0]),
                endereco,
                cidade: String(address.city || ''),
                estado: String(address.state || ''),
                cep: this.formatCep(String(address.zip || '')),
            };
        },

        mapResponse(data) {
            return this.variant === 'empresa'
                ? this.mapEmpresaResponse(data)
                : this.mapClienteResponse(data);
        },

        notificar(titulo, descricao) {
            const payload = {
                options: {
                    title: titulo,
                    description: descricao,
                    timeout: 3000,
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

        valorAtual() {
            const input = this.$el.querySelector('input');

            return (input?.value || this.$wire.get(this.wireModel) || '').replace(/\D/g, '');
        },

        async aplicarNoFormulario(mapped) {
            await this.$wire.call(this.applyMethod, mapped);
        },

        async buscarCnpj(forcar = false) {
            if (this.buscandoCnpj) {
                return;
            }

            const digits = this.valorAtual();

            if (digits.length !== 14) {
                this.notificar('CNPJ inválido', 'Informe um CNPJ válido com 14 dígitos.');

                return;
            }

            if (! forcar && digits === this.ultimoCnpjConsultado) {
                return;
            }

            this.buscandoCnpj = true;

            try {
                const response = await fetch(`https://open.cnpja.com/office/${digits}`);

                if (response.status === 404) {
                    this.notificar('CNPJ não encontrado', 'CNPJ não encontrado na base da Receita Federal.');

                    return;
                }

                if (response.status === 429) {
                    this.notificar('Limite excedido', 'Limite de consultas excedido. Aguarde um minuto e tente novamente.');

                    return;
                }

                if (! response.ok) {
                    throw new Error('consulta_falhou');
                }

                const data = await response.json();
                const mapped = this.mapResponse(data);

                this.ultimoCnpjConsultado = digits;

                try {
                    await this.aplicarNoFormulario(mapped);
                    this.notificar('CNPJ encontrado', 'Dados preenchidos automaticamente.');
                } catch {
                    this.notificar('Erro ao preencher', 'Não foi possível aplicar os dados do CNPJ.');
                }
            } catch {
                this.notificar(
                    'Consulta indisponível',
                    'Não foi possível consultar o CNPJ. Verifique sua conexão e tente novamente.',
                );
            } finally {
                this.buscandoCnpj = false;
            }
        },

        init() {
            if (! this.autoFetch) {
                return;
            }

            const input = this.$el.querySelector('input');

            if (! input) {
                return;
            }

            input.addEventListener('input', () => {
                if (this.buscandoCnpj) {
                    return;
                }

                clearTimeout(this.cnpjTimer);

                this.cnpjTimer = setTimeout(() => {
                    const digits = (input.value || '').replace(/\D/g, '');

                    if (digits.length === 14 && digits !== this.ultimoCnpjConsultado) {
                        this.buscarCnpj();
                    }
                }, 500);
            });
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

    Alpine.data('richMediaEditor', (content, uploadUrl) => ({
        content,
        uploadUrl: uploadUrl || '',
        uploading: false,

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

        async onBlur() {
            await this.replaceEmbeddedImages();
            this.sync();
        },

        async onPaste(event) {
            const clipboard = event.clipboardData;

            if (! clipboard) {
                return;
            }

            const imageItems = Array.from(clipboard.items).filter((item) => item.type.startsWith('image/'));

            if (imageItems.length > 0) {
                event.preventDefault();

                for (const item of imageItems) {
                    const file = item.getAsFile();

                    if (! file) {
                        continue;
                    }

                    try {
                        const data = await this.uploadFile(this.nameClipboardFile(file, item.type));

                        if (data?.url) {
                            this.insertHtml(`<img src="${data.url}" alt="Imagem" class="repositorio-media repositorio-media--image">`);
                        }
                    } catch (error) {
                        window.alert('Não foi possível enviar a imagem colada.');
                    }
                }

                return;
            }

            const html = clipboard.getData('text/html');

            if (html && html.includes('data:image')) {
                event.preventDefault();

                try {
                    await this.insertSanitizedHtml(html);
                } catch (error) {
                    window.alert('Não foi possível processar o conteúdo colado.');
                }
            }
        },

        nameClipboardFile(file, mimeType) {
            if (file.name && file.name !== 'image.png') {
                return file;
            }

            const extension = (mimeType.split('/')[1] || 'png').replace('jpeg', 'jpg');

            return new File([file], `colagem-${Date.now()}.${extension}`, { type: mimeType || file.type });
        },

        async dataUrlToFile(dataUrl) {
            const response = await fetch(dataUrl);
            const blob = await response.blob();
            const extension = (blob.type.split('/')[1] || 'png').replace('jpeg', 'jpg');

            return new File([blob], `colagem-${Date.now()}.${extension}`, { type: blob.type || 'image/png' });
        },

        async insertSanitizedHtml(html) {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const images = doc.querySelectorAll('img[src^="data:"]');

            for (const image of images) {
                const source = image.getAttribute('src');

                if (! source) {
                    continue;
                }

                try {
                    const file = await this.dataUrlToFile(source);
                    const data = await this.uploadFile(file);

                    if (data?.url) {
                        image.setAttribute('src', data.url);
                        image.classList.add('repositorio-media', 'repositorio-media--image');
                    } else {
                        image.remove();
                    }
                } catch (error) {
                    image.remove();
                }
            }

            const cleanHtml = doc.body.innerHTML.trim();

            if (cleanHtml !== '') {
                this.insertHtml(cleanHtml);
            }
        },

        async replaceEmbeddedImages() {
            if (! this.$refs.editor) {
                return;
            }

            const images = this.$refs.editor.querySelectorAll('img[src^="data:"]');

            for (const image of images) {
                const source = image.getAttribute('src');

                if (! source) {
                    continue;
                }

                try {
                    const file = await this.dataUrlToFile(source);
                    const data = await this.uploadFile(file);

                    if (data?.url) {
                        image.setAttribute('src', data.url);
                        image.classList.add('repositorio-media', 'repositorio-media--image');
                    } else {
                        image.remove();
                    }
                } catch (error) {
                    image.remove();
                }
            }
        },

        exec(command) {
            if (! this.$refs.editor) {
                return;
            }

            this.$refs.editor.focus();
            document.execCommand(command, false, null);
            this.sync();
        },

        insertHtml(html) {
            if (! this.$refs.editor) {
                return;
            }

            this.$refs.editor.focus();
            document.execCommand('insertHTML', false, html);
            this.sync();
        },

        async uploadFile(file) {
            if (! file || ! this.uploadUrl) {
                return null;
            }

            const formData = new FormData();
            formData.append('file', file);
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

            this.uploading = true;

            try {
                const response = await fetch(this.uploadUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        Accept: 'application/json',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    throw new Error('upload failed');
                }

                return await response.json();
            } finally {
                this.uploading = false;
            }
        },

        triggerImageUpload() {
            this.$refs.imageInput?.click();
        },

        async onImageSelected(event) {
            const file = event.target.files?.[0];
            event.target.value = '';

            if (! file) {
                return;
            }

            try {
                const data = await this.uploadFile(file);

                if (data?.url) {
                    this.insertHtml(`<img src="${data.url}" alt="Imagem" class="repositorio-media repositorio-media--image">`);
                }
            } catch (error) {
                window.alert('Não foi possível enviar a imagem.');
            }
        },

        triggerVideoUpload() {
            this.$refs.videoInput?.click();
        },

        async onVideoSelected(event) {
            const file = event.target.files?.[0];
            event.target.value = '';

            if (! file) {
                return;
            }

            try {
                const data = await this.uploadFile(file);

                if (data?.url) {
                    this.insertHtml(`<video controls class="repositorio-media repositorio-media--video"><source src="${data.url}"></video>`);
                }
            } catch (error) {
                window.alert('Não foi possível enviar o vídeo.');
            }
        },

        insertVideoUrl() {
            const url = window.prompt('Cole o link do vídeo (YouTube, Vimeo ou arquivo MP4):');

            if (! url?.trim()) {
                return;
            }

            const embed = this.buildVideoEmbed(url.trim());

            if (embed) {
                this.insertHtml(embed);
            } else {
                window.alert('Link de vídeo inválido.');
            }
        },

        buildVideoEmbed(url) {
            const youtubeMatch = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([\w-]{11})/);

            if (youtubeMatch) {
                return `<div class="repositorio-media repositorio-media--embed"><iframe src="https://www.youtube.com/embed/${youtubeMatch[1]}" frameborder="0" allowfullscreen loading="lazy"></iframe></div>`;
            }

            const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);

            if (vimeoMatch) {
                return `<div class="repositorio-media repositorio-media--embed"><iframe src="https://player.vimeo.com/video/${vimeoMatch[1]}" frameborder="0" allowfullscreen loading="lazy"></iframe></div>`;
            }

            if (/\.(mp4|webm|mov)(\?.*)?$/i.test(url)) {
                return `<video controls class="repositorio-media repositorio-media--video"><source src="${url}"></video>`;
            }

            return null;
        },
    }));
});
