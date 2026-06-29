<script>
    (function () {
        window.Wireui = {
            cache: {},
            hook(hook, callback) {
                window.addEventListener('wireui:' + hook, function () {
                    callback();
                });
            },
            dispatchHook(hook) {
                window.dispatchEvent(new Event('wireui:' + hook));
            },
        };

        window.__wireuiOutlineIcons = {
            'check-circle': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
            'exclamation-triangle': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>',
            'exclamation-circle': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>',
            'information-circle': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>',
            'question-mark-circle': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" /></svg>',
        };

        function normalizarPayload(detail) {
            if (! detail) {
                return null;
            }

            return Array.isArray(detail) ? detail[0] : detail;
        }

        window.addEventListener('wireui:notification', function (event) {
            var payload = normalizarPayload(event.detail);

            if (! payload?.options) {
                return;
            }

            if (typeof payload.options.icon === 'string') {
                payload.options.iconColor = payload.options.iconColor || null;
            }
        }, true);

        window.__patchWireuiNotifications = function () {
            var alpine = window.Alpine;

            if (! alpine || typeof alpine.data !== 'function') {
                return false;
            }

            var factory = alpine.data('wireui_notifications');

            if (typeof factory !== 'function' || factory.__iconsPatched) {
                return !! factory.__iconsPatched;
            }

            alpine.data('wireui_notifications', function () {
                var data = factory();

                data.fillNotificationIcon = function (notification) {
                    if (! notification?.icon?.name) {
                        return;
                    }

                    var host = document
                        .getElementById('notification.' + notification.id)
                        ?.querySelector('.notification-icon');

                    if (! host) {
                        return;
                    }

                    var markup = window.__wireuiOutlineIcons[notification.icon.name];

                    if (! markup) {
                        return;
                    }

                    var wrap = document.createElement('div');
                    wrap.innerHTML = markup.trim();
                    var svg = wrap.firstElementChild;

                    if (! svg) {
                        return;
                    }

                    ('w-6 h-6 ' + (notification.icon.color || 'text-slate-500'))
                        .trim()
                        .split(/\s+/)
                        .filter(Boolean)
                        .forEach(function (className) {
                            svg.classList.add(className);
                        });

                    host.replaceChildren(svg);
                };

                return data;
            });

            factory.__iconsPatched = true;

            return true;
        };

        window.Wireui.hook('notifications:load', function () {
            window.__patchWireuiNotifications();
        });

        document.addEventListener('alpine:init', function () {
            window.__patchWireuiNotifications();

            var tries = 0;
            var timer = setInterval(function () {
                if (window.__patchWireuiNotifications() || ++tries > 60) {
                    clearInterval(timer);
                }
            }, 50);
        });
    })();
</script>
